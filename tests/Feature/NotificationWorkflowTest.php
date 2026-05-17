<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Http\Middleware\SessionTracking;
use App\Notifications\CourseEventNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withoutMiddleware(SessionTracking::class);
    }

    public function test_admin_course_creation_notifies_assigned_teacher(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $teacher = User::factory()->create(['name' => 'Ms. Santos']);
        $teacher->assignRole('Teacher');

        $this->actingAs($admin)
            ->withHeader('User-Agent', 'PHPUnit')
            ->post(route('admin.courses.store'), [
                'course_number' => 'MATH101',
                'course_title' => 'College Algebra',
                'course_description' => 'Introductory algebra course.',
                'semester' => 'first',
                'school_year' => '2026-2027',
                'teacher_id' => $teacher->id,
            ])
            ->assertRedirect();

        Notification::assertSentTo($teacher, CourseEventNotification::class, function (CourseEventNotification $notification) use ($teacher): bool {
            $data = $notification->toArray($teacher);

            return $data['title'] === 'New Course Assigned'
                && str_contains($data['message'], 'MATH101 - College Algebra')
                && str_contains($data['url'], '/teacher/courses/');
        });
    }

    public function test_teacher_enrollment_creation_notifies_student(): void
    {
        Notification::fake();

        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');

        $student = User::factory()->create();
        $student->assignRole('Student');

        $course = Course::query()->create([
            'course_number' => 'SCI201',
            'title' => 'Earth Science',
            'semester' => 'second',
            'teacher_id' => $teacher->id,
        ]);

        $this->actingAs($teacher)
            ->withHeader('User-Agent', 'PHPUnit')
            ->post(route('teacher.enrollments.store'), [
                'student_id' => $student->id,
                'course_id' => $course->id,
                'status' => 'active',
            ])
            ->assertRedirect(route('teacher.enrollments.index'));

        Notification::assertSentTo($student, CourseEventNotification::class, function (CourseEventNotification $notification) use ($student): bool {
            $data = $notification->toArray($student);

            return $data['title'] === 'Course Enrollment Updated'
                && str_contains($data['message'], 'SCI201 - Earth Science')
                && str_contains($data['message'], 'active status')
                && str_contains($data['url'], '/student/courses/');
        });
    }

    public function test_admin_enrollment_creation_notifies_student(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');

        $student = User::factory()->create();
        $student->assignRole('Student');

        $course = Course::query()->create([
            'course_number' => 'ENG301',
            'title' => 'Academic Writing',
            'semester' => 'summer',
            'teacher_id' => $teacher->id,
        ]);

        $this->actingAs($admin)
            ->withHeader('User-Agent', 'PHPUnit')
            ->post(route('admin.enrollments.store'), [
                'student_id' => $student->id,
                'course_id' => $course->id,
                'status' => 'active',
            ])
            ->assertRedirect();

        Notification::assertSentTo($student, CourseEventNotification::class, function (CourseEventNotification $notification) use ($student): bool {
            $data = $notification->toArray($student);

            return $data['title'] === 'Course Enrollment Added'
                && str_contains($data['message'], 'ENG301 - Academic Writing')
                && str_contains($data['message'], 'active status')
                && str_contains($data['url'], '/student/courses/');
        });
    }
}
