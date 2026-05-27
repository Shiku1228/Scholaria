<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\ChatGroup;
use App\Models\ChatMessage;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\CourseChatGroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseMessagingFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_user_only_sees_their_own_courses(): void
    {
        [$teacher, $student, $outsideStudent, $courseA, $courseB] = $this->seedBase();
        Enrollment::query()->create(['student_id' => $student->id, 'course_id' => $courseA->id, 'teacher_id' => $teacher->id, 'status' => 'active', 'enrolled_at' => now()]);
        Enrollment::query()->create(['student_id' => $outsideStudent->id, 'course_id' => $courseB->id, 'teacher_id' => $teacher->id, 'status' => 'active', 'enrolled_at' => now()]);

        app(CourseChatGroupService::class)->syncAllCourses();

        $this->actingAs($student)
            ->get('/messages')
            ->assertOk()
            ->assertSee((string) $courseA->title)
            ->assertDontSee((string) $courseB->title);
    }

    public function test_user_can_enter_only_authorized_course_messaging_space(): void
    {
        [$teacher, $student, $outsideStudent, $courseA, $courseB] = $this->seedBase();
        Enrollment::query()->create(['student_id' => $student->id, 'course_id' => $courseA->id, 'teacher_id' => $teacher->id, 'status' => 'active', 'enrolled_at' => now()]);

        app(CourseChatGroupService::class)->syncAllCourses();

        $this->actingAs($student)->get('/messages/courses/' . $courseA->id)->assertOk();
        $this->actingAs($student)->get('/messages/courses/' . $courseB->id)->assertForbidden();
    }

    public function test_user_can_access_course_group_chat_if_member(): void
    {
        [$teacher, $student, $outsideStudent, $courseA] = $this->seedBase();
        Enrollment::query()->create(['student_id' => $student->id, 'course_id' => $courseA->id, 'teacher_id' => $teacher->id, 'status' => 'active', 'enrolled_at' => now()]);
        $service = app(CourseChatGroupService::class);
        $service->syncAllCourses();
        $groupConversation = $service->resolveGroupConversation($courseA);

        $this->actingAs($student)
            ->getJson('/messages/conversations/' . $groupConversation->id . '/messages')
            ->assertOk();
    }

    public function test_user_can_start_private_chat_only_with_another_member_of_same_course(): void
    {
        [$teacher, $student, $outsideStudent, $courseA] = $this->seedBase();
        Enrollment::query()->create(['student_id' => $student->id, 'course_id' => $courseA->id, 'teacher_id' => $teacher->id, 'status' => 'active', 'enrolled_at' => now()]);

        app(CourseChatGroupService::class)->syncAllCourses();

        $this->actingAs($student)
            ->postJson('/messages/courses/' . $courseA->id . '/private-chat/' . $teacher->id)
            ->assertOk()
            ->assertJsonPath('data.conversation.type', 'private');
    }

    public function test_user_cannot_start_private_chat_with_someone_outside_course(): void
    {
        [$teacher, $student, $outsideStudent, $courseA] = $this->seedBase();
        Enrollment::query()->create(['student_id' => $student->id, 'course_id' => $courseA->id, 'teacher_id' => $teacher->id, 'status' => 'active', 'enrolled_at' => now()]);

        app(CourseChatGroupService::class)->syncAllCourses();

        $this->actingAs($student)
            ->postJson('/messages/courses/' . $courseA->id . '/private-chat/' . $outsideStudent->id)
            ->assertForbidden();
    }

    public function test_unauthorized_conversation_access_is_blocked(): void
    {
        [$teacher, $student, $outsideStudent, $courseA] = $this->seedBase();
        Enrollment::query()->create(['student_id' => $student->id, 'course_id' => $courseA->id, 'teacher_id' => $teacher->id, 'status' => 'active', 'enrolled_at' => now()]);
        $service = app(CourseChatGroupService::class);
        $service->syncAllCourses();
        $private = $service->resolvePrivateConversation($courseA, $student, $teacher);

        $this->actingAs($outsideStudent)
            ->getJson('/messages/conversations/' . $private->id . '/messages')
            ->assertForbidden();
    }

    public function test_sending_message_works_correctly(): void
    {
        [$teacher, $student, $outsideStudent, $courseA] = $this->seedBase();
        Enrollment::query()->create(['student_id' => $student->id, 'course_id' => $courseA->id, 'teacher_id' => $teacher->id, 'status' => 'active', 'enrolled_at' => now()]);
        $service = app(CourseChatGroupService::class);
        $service->syncAllCourses();
        $conversation = $service->resolveGroupConversation($courseA);

        $this->actingAs($student)
            ->postJson('/messages/conversations/' . $conversation->id . '/messages', ['message' => 'Hello group'])
            ->assertCreated()
            ->assertJsonPath('data.message.message', 'Hello group');
    }

    public function test_default_group_conversation_is_auto_created_per_course(): void
    {
        [$teacher, $student, $outsideStudent, $courseA, $courseB] = $this->seedBase();
        app(CourseChatGroupService::class)->syncAllCourses();

        $this->assertDatabaseHas('chat_groups', ['course_id' => $courseA->id]);
        $this->assertDatabaseHas('chat_groups', ['course_id' => $courseB->id]);

        $spaceA = ChatGroup::query()->where('course_id', $courseA->id)->firstOrFail();
        $spaceB = ChatGroup::query()->where('course_id', $courseB->id)->firstOrFail();

        $this->assertDatabaseHas('chat_conversations', ['chat_group_id' => $spaceA->id, 'type' => 'group']);
        $this->assertDatabaseHas('chat_conversations', ['chat_group_id' => $spaceB->id, 'type' => 'group']);
    }

    public function test_existing_conversation_is_reused_for_same_pair_in_same_course(): void
    {
        [$teacher, $student, $outsideStudent, $courseA] = $this->seedBase();
        Enrollment::query()->create(['student_id' => $student->id, 'course_id' => $courseA->id, 'teacher_id' => $teacher->id, 'status' => 'active', 'enrolled_at' => now()]);
        $service = app(CourseChatGroupService::class);
        $service->syncAllCourses();

        $one = $service->resolvePrivateConversation($courseA, $student, $teacher);
        $two = $service->resolvePrivateConversation($courseA, $teacher, $student);

        $this->assertNotNull($one);
        $this->assertNotNull($two);
        $this->assertSame((int) $one->id, (int) $two->id);
        $this->assertEquals(2, $one->participants()->count());
    }

    private function seedBase(): array
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');

        $student = User::factory()->create();
        $student->assignRole('Student');

        $outsideStudent = User::factory()->create();
        $outsideStudent->assignRole('Student');

        $courseA = $this->createCourse($teacher, 'CS-201', 'Software Engineering');
        $courseB = $this->createCourse($teacher, 'CS-202', 'Networks');

        return [$teacher, $student, $outsideStudent, $courseA, $courseB];
    }

    private function createCourse(User $teacher, string $number, string $title): Course
    {
        return Course::query()->create([
            'course_number' => $number,
            'title' => $title,
            'description' => null,
            'semester' => 'first',
            'school_year' => '2026-2027',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(4)->toDateString(),
            'days_pattern' => 'MWF',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'teacher_id' => $teacher->id,
        ]);
    }
}

