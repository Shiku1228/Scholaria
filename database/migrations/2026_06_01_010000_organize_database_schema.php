<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addCourseProgramRelationship();
        $this->addExamAttemptGradingFields();
        $this->addPerformanceIndexes();
    }

    public function down(): void
    {
        if (Schema::hasTable('courses')) {
            Schema::table('courses', function (Blueprint $table) {
                if (Schema::hasColumn('courses', 'program_id')) {
                    try {
                        $table->dropForeign(['program_id']);
                    } catch (\Throwable) {
                    }

                    try {
                        $table->dropIndex('courses_program_id_index');
                    } catch (\Throwable) {
                    }

                    $table->dropColumn('program_id');
                }
            });
        }

        if (Schema::hasTable('student_exam_attempts')) {
            Schema::table('student_exam_attempts', function (Blueprint $table) {
                foreach (['graded_by', 'graded_at'] as $column) {
                    if (!Schema::hasColumn('student_exam_attempts', $column)) {
                        continue;
                    }

                    if ($column === 'graded_by') {
                        try {
                            $table->dropForeign(['graded_by']);
                        } catch (\Throwable) {
                        }
                    }

                    try {
                        $table->dropIndex('student_exam_attempts_' . $column . '_index');
                    } catch (\Throwable) {
                    }

                    $table->dropColumn($column);
                }
            });
        }

        $this->dropNamedIndexes();
    }

    private function addCourseProgramRelationship(): void
    {
        if (!Schema::hasTable('courses') || !Schema::hasTable('programs')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'program_id')) {
                $table->foreignId('program_id')
                    ->nullable()
                    ->after('teacher_id')
                    ->constrained('programs')
                    ->nullOnDelete();
            }
        });
    }

    private function addExamAttemptGradingFields(): void
    {
        if (!Schema::hasTable('student_exam_attempts')) {
            return;
        }

        Schema::table('student_exam_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('student_exam_attempts', 'graded_at')) {
                $table->timestamp('graded_at')->nullable()->after('submitted_at');
            }

            if (!Schema::hasColumn('student_exam_attempts', 'graded_by')) {
                $table->foreignId('graded_by')
                    ->nullable()
                    ->after('graded_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    private function addPerformanceIndexes(): void
    {
        $this->addIndexIfColumnsExist('courses', ['teacher_id'], 'courses_teacher_id_index');
        $this->addIndexIfColumnsExist('courses', ['program_id'], 'courses_program_id_index');
        $this->addIndexIfColumnsExist('courses', ['course_code'], 'courses_course_code_index');
        $this->addIndexIfColumnsExist('courses', ['semester'], 'courses_semester_index');

        $this->addIndexIfColumnsExist('enrollments', ['student_id', 'status'], 'enrollments_student_status_index');
        $this->addIndexIfColumnsExist('enrollments', ['course_id', 'status'], 'enrollments_course_status_index');
        $this->addIndexIfColumnsExist('enrollments', ['teacher_id'], 'enrollments_teacher_id_index');
        $this->addIndexIfColumnsExist('enrollments', ['created_at'], 'enrollments_created_at_index');

        $this->addIndexIfColumnsExist('attendances', ['course_id', 'date'], 'attendances_course_date_index');
        $this->addIndexIfColumnsExist('attendances', ['student_id', 'date'], 'attendances_student_date_index');
        $this->addIndexIfColumnsExist('attendances', ['status'], 'attendances_status_index');

        $this->addIndexIfColumnsExist('assignments', ['course_id', 'due_date'], 'assignments_course_due_date_index');
        $this->addIndexIfColumnsExist('assignments', ['type'], 'assignments_type_index');
        $this->addIndexIfColumnsExist('assignments', ['assignment_format'], 'assignments_assignment_format_index');

        $this->addIndexIfColumnsExist('submissions', ['assignment_id', 'student_id'], 'submissions_assignment_student_index');
        $this->addIndexIfColumnsExist('submissions', ['submitted_at'], 'submissions_submitted_at_index');
        $this->addIndexIfColumnsExist('submissions', ['graded_at'], 'submissions_graded_at_index');
        $this->addIndexIfColumnsExist('submissions', ['graded_by'], 'submissions_graded_by_index');

        $this->addIndexIfColumnsExist('quizzes', ['course_id', 'due_date'], 'quizzes_course_due_date_index');
        $this->addIndexIfColumnsExist('quizzes', ['start_date'], 'quizzes_start_date_index');
        $this->addIndexIfColumnsExist('quizzes', ['is_published'], 'quizzes_is_published_index');

        $this->addIndexIfColumnsExist('quiz_attempts', ['quiz_id', 'student_id'], 'quiz_attempts_quiz_student_index');
        $this->addIndexIfColumnsExist('quiz_attempts', ['status'], 'quiz_attempts_status_index');
        $this->addIndexIfColumnsExist('quiz_attempts', ['submitted_at'], 'quiz_attempts_submitted_at_index');
        $this->addIndexIfColumnsExist('quiz_attempts', ['graded_at'], 'quiz_attempts_graded_at_index');

        $this->addIndexIfColumnsExist('exams', ['course_id', 'due_date'], 'exams_course_due_date_index');
        $this->addIndexIfColumnsExist('exams', ['exam_date'], 'exams_exam_date_index');
        $this->addIndexIfColumnsExist('exams', ['exam_type'], 'exams_exam_type_index');
        $this->addIndexIfColumnsExist('exams', ['is_published'], 'exams_is_published_index');

        $this->addIndexIfColumnsExist('student_exam_attempts', ['exam_id', 'student_id'], 'student_exam_attempts_exam_student_index');
        $this->addIndexIfColumnsExist('student_exam_attempts', ['status'], 'student_exam_attempts_status_index');
        $this->addIndexIfColumnsExist('student_exam_attempts', ['submitted_at'], 'student_exam_attempts_submitted_at_index');
        $this->addIndexIfColumnsExist('student_exam_attempts', ['graded_at'], 'student_exam_attempts_graded_at_index');
        $this->addIndexIfColumnsExist('student_exam_attempts', ['graded_by'], 'student_exam_attempts_graded_by_index');

        $this->addIndexIfColumnsExist('question_banks', ['teacher_id'], 'question_banks_teacher_id_index');
        $this->addIndexIfColumnsExist('bank_questions', ['question_bank_id', 'question_type'], 'bank_questions_bank_type_index');

        $this->addIndexIfColumnsExist('course_resources', ['course_id', 'created_at'], 'course_resources_course_created_index');
        $this->addIndexIfColumnsExist('announcements', ['course_id', 'created_at'], 'announcements_course_created_index');
        $this->addIndexIfColumnsExist('chat_messages', ['chat_group_id', 'created_at'], 'chat_messages_group_created_index');
        $this->addIndexIfColumnsExist('chat_messages', ['chat_conversation_id', 'created_at'], 'chat_messages_conversation_created_index');
        $this->addIndexIfColumnsExist('notifications', ['notifiable_type', 'notifiable_id'], 'notifications_notifiable_lookup_index');
        $this->addIndexIfColumnsExist('activity_logs', ['user_id', 'created_at'], 'activity_logs_user_created_index');
        $this->addIndexIfColumnsExist('security_audits', ['user_id', 'created_at'], 'security_audits_user_created_index');
        $this->addIndexIfColumnsExist('user_sessions', ['user_id', 'is_active'], 'user_sessions_user_active_index');
    }

    private function addIndexIfColumnsExist(string $table, array $columns, string $indexName): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return;
            }
        }

        try {
            Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        } catch (\Throwable) {
        }
    }

    private function dropNamedIndexes(): void
    {
        $indexes = [
            'courses' => ['courses_teacher_id_index', 'courses_course_code_index', 'courses_semester_index'],
            'enrollments' => ['enrollments_student_status_index', 'enrollments_course_status_index', 'enrollments_teacher_id_index', 'enrollments_created_at_index'],
            'attendances' => ['attendances_course_date_index', 'attendances_student_date_index', 'attendances_status_index'],
            'assignments' => ['assignments_course_due_date_index', 'assignments_type_index', 'assignments_assignment_format_index'],
            'submissions' => ['submissions_assignment_student_index', 'submissions_submitted_at_index', 'submissions_graded_at_index', 'submissions_graded_by_index'],
            'quizzes' => ['quizzes_course_due_date_index', 'quizzes_start_date_index', 'quizzes_is_published_index'],
            'quiz_attempts' => ['quiz_attempts_quiz_student_index', 'quiz_attempts_status_index', 'quiz_attempts_submitted_at_index', 'quiz_attempts_graded_at_index'],
            'exams' => ['exams_course_due_date_index', 'exams_exam_date_index', 'exams_exam_type_index', 'exams_is_published_index'],
            'student_exam_attempts' => ['student_exam_attempts_exam_student_index', 'student_exam_attempts_status_index', 'student_exam_attempts_submitted_at_index'],
            'question_banks' => ['question_banks_teacher_id_index'],
            'bank_questions' => ['bank_questions_bank_type_index'],
            'course_resources' => ['course_resources_course_created_index'],
            'announcements' => ['announcements_course_created_index'],
            'chat_messages' => ['chat_messages_group_created_index', 'chat_messages_conversation_created_index'],
            'notifications' => ['notifications_notifiable_lookup_index'],
            'activity_logs' => ['activity_logs_user_created_index'],
            'security_audits' => ['security_audits_user_created_index'],
            'user_sessions' => ['user_sessions_user_active_index'],
        ];

        foreach ($indexes as $table => $tableIndexes) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) use ($tableIndexes) {
                foreach ($tableIndexes as $indexName) {
                    try {
                        $table->dropIndex($indexName);
                    } catch (\Throwable) {
                    }
                }
            });
        }
    }
};
