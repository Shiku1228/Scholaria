<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetSampleCourses extends Command
{
    protected $signature   = 'scholaria:reset-sample-courses {--force : Skip confirmation prompt}';
    protected $description = 'Reset all colleges, programs, courses, enrollments, and attendances, then seed sample data.';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This will delete ALL colleges, programs, courses, enrollments, and attendances. Continue?')) {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        DB::transaction(function () {
            $courseIds = DB::table('courses')->pluck('id');

            if ($courseIds->isNotEmpty()) {
                DB::table('attendances')->whereIn('course_id', $courseIds)->delete();
                DB::table('enrollments')->whereIn('course_id', $courseIds)->delete();
            }

            DB::table('courses')->delete();
            DB::table('programs')->delete();
            DB::table('colleges')->delete();

            // Create Scholaria College
            $collegeId = DB::table('colleges')->insertGetId([
                'name'       => 'Scholaria College',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $programs = ['BSIT', 'BSCS', 'BSIS', 'BSEMC'];
            foreach ($programs as $program) {
                DB::table('programs')->insert([
                    'college_id' => $collegeId,
                    'name'       => $program,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Find Teacher Sample
            $teacher = User::where('email', 'teacher@scholaria.com')
                ->orWhere(function ($q) {
                    $q->where('name', 'like', '%Teacher Sample%');
                })
                ->whereHas('roles', fn ($q) => $q->where('name', 'Teacher'))
                ->first();

            if (! $teacher) {
                $teacher = User::whereHas('roles', fn ($q) => $q->where('name', 'Teacher'))->first();
            }

            $teacherId = $teacher?->id;

            $courses = [
                [
                    'course_number' => '10101',
                    'course_code'   => 'ITE101',
                    'title'         => 'Introduction to Computing',
                    'description'   => 'Fundamentals of computers, computing systems, and information technology.',
                    'semester'      => 'first',
                    'school_year'   => '2025-2026',
                    'start_date'    => '2025-08-01',
                    'end_date'      => '2025-12-15',
                    'days_pattern'  => 'Mon,Wed,Fri',
                    'start_time'    => '08:00:00',
                    'end_time'      => '09:00:00',
                ],
                [
                    'course_number' => '10102',
                    'course_code'   => 'ITE102',
                    'title'         => 'Computer Programming 1',
                    'description'   => 'Introduction to programming logic and techniques using Python.',
                    'semester'      => 'first',
                    'school_year'   => '2025-2026',
                    'start_date'    => '2025-08-01',
                    'end_date'      => '2025-12-15',
                    'days_pattern'  => 'Tue,Thu',
                    'start_time'    => '10:00:00',
                    'end_time'      => '11:30:00',
                ],
                [
                    'course_number' => '10201',
                    'course_code'   => 'ITE201',
                    'title'         => 'Data Structures and Algorithms',
                    'description'   => 'Study of data structures and algorithmic problem solving.',
                    'semester'      => 'second',
                    'school_year'   => '2025-2026',
                    'start_date'    => '2026-01-05',
                    'end_date'      => '2026-05-30',
                    'days_pattern'  => 'Mon,Wed,Fri',
                    'start_time'    => '08:00:00',
                    'end_time'      => '09:00:00',
                ],
                [
                    'course_number' => '10202',
                    'course_code'   => 'ITE202',
                    'title'         => 'Web Development',
                    'description'   => 'Building modern web applications using HTML, CSS, JavaScript, and PHP.',
                    'semester'      => 'second',
                    'school_year'   => '2025-2026',
                    'start_date'    => '2026-01-05',
                    'end_date'      => '2026-05-30',
                    'days_pattern'  => 'Tue,Thu',
                    'start_time'    => '10:00:00',
                    'end_time'      => '11:30:00',
                ],
                [
                    'course_number' => '20101',
                    'course_code'   => 'CSC101',
                    'title'         => 'Object-Oriented Programming',
                    'description'   => 'Programming concepts using the object-oriented paradigm in Java.',
                    'semester'      => 'first',
                    'school_year'   => '2025-2026',
                    'start_date'    => '2025-08-01',
                    'end_date'      => '2025-12-15',
                    'days_pattern'  => 'Mon,Wed,Fri',
                    'start_time'    => '13:00:00',
                    'end_time'      => '14:00:00',
                ],
                [
                    'course_number' => '30101',
                    'course_code'   => 'IS101',
                    'title'         => 'Systems Analysis and Design',
                    'description'   => 'Principles and methodologies for analyzing and designing information systems.',
                    'semester'      => 'first',
                    'school_year'   => '2025-2026',
                    'start_date'    => '2025-08-01',
                    'end_date'      => '2025-12-15',
                    'days_pattern'  => 'Tue,Thu',
                    'start_time'    => '13:00:00',
                    'end_time'      => '14:30:00',
                ],
                [
                    'course_number' => '40101',
                    'course_code'   => 'EMC101',
                    'title'         => 'Digital Media Production',
                    'description'   => 'Techniques for producing digital media including audio, video, and graphics.',
                    'semester'      => 'second',
                    'school_year'   => '2025-2026',
                    'start_date'    => '2026-01-05',
                    'end_date'      => '2026-05-30',
                    'days_pattern'  => 'Mon,Wed,Fri',
                    'start_time'    => '15:00:00',
                    'end_time'      => '16:00:00',
                ],
            ];

            foreach ($courses as $data) {
                DB::table('courses')->insert(array_merge($data, [
                    'teacher_id' => $teacherId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        });

        $this->info('Done. Sample data has been reset.');
        $this->line('  - All existing colleges, programs, courses, enrollments, and attendances deleted');
        $this->line('  - Scholaria College created');
        $this->line('  - 4 programs created: BSIT, BSCS, BSIS, BSEMC');
        $this->line('  - 7 sample courses created and assigned to Teacher Sample');

        return self::SUCCESS;
    }
}
