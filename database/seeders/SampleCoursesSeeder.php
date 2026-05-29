<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleCoursesSeeder extends Seeder
{
    public function run(): void
    {
        $teacher1 = User::role('Teacher')->first();
        $teacher2 = User::role('Teacher')->skip(1)->first() ?? $teacher1;

        $courses = [
            [
                'course_number' => 'CS101',
                'title'         => 'Introduction to Programming',
                'description'   => 'Fundamentals of programming using Python.',
                'semester'      => 'first',
                'school_year'   => '2025-2026',
                'start_date'    => '2025-08-01',
                'end_date'      => '2025-12-15',
                'days_pattern'  => 'MWF',
                'start_time'    => '08:00:00',
                'end_time'      => '09:00:00',
                'teacher_id'    => $teacher1?->id,
            ],
            [
                'course_number' => 'CS102',
                'title'         => 'Data Structures and Algorithms',
                'description'   => 'Study of data structures and algorithmic problem solving.',
                'semester'      => 'first',
                'school_year'   => '2025-2026',
                'start_date'    => '2025-08-01',
                'end_date'      => '2025-12-15',
                'days_pattern'  => 'TTh',
                'start_time'    => '10:00:00',
                'end_time'      => '11:30:00',
                'teacher_id'    => $teacher1?->id,
            ],
            [
                'course_number' => 'IT201',
                'title'         => 'Web Development',
                'description'   => 'Building modern web applications with HTML, CSS, JavaScript, and PHP.',
                'semester'      => 'second',
                'school_year'   => '2025-2026',
                'start_date'    => '2026-01-05',
                'end_date'      => '2026-05-30',
                'days_pattern'  => 'MWF',
                'start_time'    => '13:00:00',
                'end_time'      => '14:00:00',
                'teacher_id'    => $teacher2?->id,
            ],
            [
                'course_number' => 'IT202',
                'title'         => 'Database Management Systems',
                'description'   => 'Relational database design, SQL, and database administration.',
                'semester'      => 'second',
                'school_year'   => '2025-2026',
                'start_date'    => '2026-01-05',
                'end_date'      => '2026-05-30',
                'days_pattern'  => 'TTh',
                'start_time'    => '14:00:00',
                'end_time'      => '15:30:00',
                'teacher_id'    => $teacher2?->id,
            ],
            [
                'course_number' => 'IT301',
                'title'         => 'Mobile Application Development',
                'description'   => 'Developing mobile applications using React Native and Expo.',
                'semester'      => 'first',
                'school_year'   => '2025-2026',
                'start_date'    => '2025-08-01',
                'end_date'      => '2025-12-15',
                'days_pattern'  => 'MWF',
                'start_time'    => '15:00:00',
                'end_time'      => '16:00:00',
                'teacher_id'    => $teacher2?->id,
            ],
        ];

        foreach ($courses as $data) {
            Course::updateOrCreate(
                ['course_number' => $data['course_number']],
                $data
            );
        }

        $this->command->info('Sample courses seeded: ' . count($courses) . ' courses created.');
    }
}
