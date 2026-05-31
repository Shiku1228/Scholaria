# Scholaria Database Schema

## Purpose
This document groups the Scholaria database by module, summarizes each table's role, and notes the main foreign-key relationships used by the LMS.

## Audit Notes
- `courses` originally had no `program_id`, so the academic hierarchy stopped at `Program -> College` and did not continue to `Course`.
- `student_exam_attempts` originally had no built-in `graded_at` or `graded_by`, unlike `quiz_attempts` and `submissions`.
- Several tables already had foreign keys, but many high-traffic filters lacked explicit secondary indexes for dashboard and reporting queries.
- Profile tables (`students`, `teachers`) still store legacy text fields like `program` and `college`. These are currently treated as denormalized profile data, while the canonical academic structure is in `colleges`, `programs`, and `courses`.

## Module Layout

### User Management

#### `users`
- Purpose: Core authenticated account table for all roles.
- Important columns: `id`, `name`, `email`, `student_number`, `profile_type`, `profile_id`, `deleted_at`.
- Relationships:
  - hasOne `students`
  - hasOne `teachers`
  - hasOne `admins`
  - hasMany `courses` through `teacher_id`
  - hasMany `enrollments`, `attendances`, `submissions`, `quiz_attempts`, `student_exam_attempts`

#### `students`
- Purpose: Student profile details linked to `users`.
- Important columns: `user_id`, `student_number`, `year_level`, `program`, `college`.
- Relationships:
  - belongsTo `users`

#### `teachers`
- Purpose: Teacher profile details linked to `users`.
- Important columns: `user_id`, `employee_id`, `specialization`, `program`, `college`.
- Relationships:
  - belongsTo `users`

#### `admins`
- Purpose: Admin profile details linked to `users`.
- Important columns: `user_id`, `admin_level`, `access_scope`.
- Relationships:
  - belongsTo `users`

#### `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`
- Purpose: Authorization layer via Spatie permissions.

### Academic Structure

#### `colleges`
- Purpose: Top-level academic grouping.
- Important columns: `id`, `name`.
- Relationships:
  - hasMany `programs`

#### `programs`
- Purpose: Programs under a college.
- Important columns: `college_id`, `name`.
- Relationships:
  - belongsTo `colleges`
  - hasMany `courses`

#### `courses`
- Purpose: Central LMS course container.
- Important columns: `teacher_id`, `program_id`, `course_number`, `course_code`, `title`, `semester`, `school_year`.
- Relationships:
  - belongsTo `users` as teacher
  - belongsTo `programs`
  - hasMany `enrollments`
  - hasMany `assignments`
  - hasMany `quizzes`
  - hasMany `exams`
  - hasMany `announcements`
  - hasMany `course_resources`
  - hasMany `course_discussions`
  - hasOne `chat_groups`

### Enrollment

#### `enrollments`
- Purpose: Links students to courses and tracks status lifecycle.
- Important columns: `student_id`, `course_id`, `teacher_id`, `status`, `enrolled_at`, `completed_at`, `dropped_at`, `unenrolled_at`.
- Relationships:
  - belongsTo `users` as student
  - belongsTo `users` as teacher
  - belongsTo `courses`

### Attendance

#### `attendances`
- Purpose: Daily attendance records by course/student.
- Important columns: `course_id`, `student_id`, `teacher_id`, `date`, `status`, `remarks`.
- Relationships:
  - belongsTo `courses`
  - belongsTo `users` as student
  - belongsTo `users` as teacher

### Assignments

#### `assignments`
- Purpose: Assignment container for coursework.
- Important columns: `course_id`, `title`, `due_date`, `max_score`, `type`, `assignment_format`.
- Relationships:
  - belongsTo `courses`
  - hasMany `submissions`
  - hasMany `assignment_questions`

#### `assignment_questions`
- Purpose: Structured assignment questions.
- Important columns: `assignment_id`, `question_text`, `question_type`, `points`, `order`.
- Relationships:
  - belongsTo `assignments`
  - hasMany `assignment_choices`
  - hasMany `assignment_answers`

#### `assignment_choices`
- Purpose: Choice options for objective assignment questions.
- Important columns: `question_id`, `choice_text`, `is_correct`, `order`.
- Relationships:
  - belongsTo `assignment_questions`

#### `submissions`
- Purpose: Student assignment submissions.
- Important columns: `assignment_id`, `student_id`, `submission_type`, `submitted_at`, `score`, `feedback`, `graded_by`, `graded_at`.
- Relationships:
  - belongsTo `assignments`
  - belongsTo `users` as student
  - belongsTo `users` as grader
  - hasMany `assignment_answers`

#### `assignment_answers`
- Purpose: Per-question answers for structured assignments.
- Important columns: `submission_id`, `question_id`, `selected_choice_id`, `answer_text`, `points_earned`, `feedback`.
- Relationships:
  - belongsTo `submissions`
  - belongsTo `assignment_questions`
  - belongsTo `assignment_choices`

### Quizzes

#### `quizzes`
- Purpose: Online quiz container.
- Important columns: `course_id`, `title`, `start_date`, `due_date`, `time_limit`, `max_score`, `feedback_type`, `results_released`.
- Relationships:
  - belongsTo `courses`
  - hasMany `quiz_questions`
  - hasMany `quiz_attempts`

#### `quiz_questions`
- Purpose: Quiz question definitions.
- Important columns: `quiz_id`, `question_text`, `question_type`, `options`, `correct_answer`, `points`, `order`.
- Relationships:
  - belongsTo `quizzes`
  - hasMany `quiz_answers`

#### `quiz_attempts`
- Purpose: Student quiz submissions/attempts.
- Important columns: `quiz_id`, `student_id`, `attempt_number`, `started_at`, `submitted_at`, `graded_at`, `graded_by`, `score`, `status`.
- Relationships:
  - belongsTo `quizzes`
  - belongsTo `users` as student
  - hasMany `quiz_answers`

#### `quiz_answers`
- Purpose: Per-question quiz answers.
- Important columns: `attempt_id`, `question_id`, `answer`, `is_correct`, `points_earned`, `feedback`.
- Relationships:
  - belongsTo `quiz_attempts`
  - belongsTo `quiz_questions`

### Exams

#### `exams`
- Purpose: Exam container for online and face-to-face assessments.
- Important columns: `course_id`, `exam_type`, `title`, `exam_date`, `due_date`, `duration`, `max_score`, `feedback_type`, `results_released`.
- Relationships:
  - belongsTo `courses`
  - hasMany `exam_questions`
  - hasMany `student_exam_attempts`

#### `exam_questions`
- Purpose: Exam question definitions.
- Important columns: `exam_id`, `question_text`, `question_type`, `options`, `correct_answer`, `points`, `order`.
- Relationships:
  - belongsTo `exams`
  - hasMany `exam_answers`

#### `student_exam_attempts`
- Purpose: Student exam submissions/attempts.
- Important columns: `exam_id`, `student_id`, `attempt_number`, `started_at`, `submitted_at`, `graded_at`, `graded_by`, `score`, `status`.
- Relationships:
  - belongsTo `exams`
  - belongsTo `users` as student
  - belongsTo `users` as grader when `graded_by` is present
  - hasMany `exam_answers`

#### `exam_answers`
- Purpose: Per-question exam answers.
- Important columns: `attempt_id`, `question_id`, `answer`, `is_correct`, `points_earned`, `feedback`.
- Relationships:
  - belongsTo `student_exam_attempts`
  - belongsTo `exam_questions`

### Question Bank

#### `question_banks`
- Purpose: Teacher-owned reusable question collections.
- Important columns: `teacher_id`, `title`, `description`.
- Relationships:
  - belongsTo `users` as teacher
  - hasMany `bank_questions`

#### `bank_questions`
- Purpose: Reusable banked questions for quizzes and exams.
- Important columns: `question_bank_id`, `question_type`, `question_text`, `options`, `correct_answer`, `points`.
- Relationships:
  - belongsTo `question_banks`

### Learning Materials

#### `course_resources`
- Purpose: Uploaded course files and links.
- Important columns: `course_id`, `uploaded_by`, `title`, `file_path`, `file_type`.
- Relationships:
  - belongsTo `courses`
  - belongsTo `users` as uploader

### Communication

#### `announcements`
- Purpose: Course announcement posts.
- Important columns: `course_id`, `teacher_id`, `title`, `content`.
- Relationships:
  - belongsTo `courses`
  - belongsTo `users` as teacher

#### `announcement_reads`
- Purpose: Read-tracking for announcements.
- Important columns: `announcement_id`, `user_id`.

#### `course_discussions`
- Purpose: Course discussion threads and replies.
- Important columns: `course_id`, `user_id`, `parent_id`, `content`.

#### `discussion_subscriptions`
- Purpose: User subscriptions to discussion threads.

#### `chat_groups`
- Purpose: Per-course group chat container.
- Important columns: `course_id`, `name`.

#### `chat_conversations`
- Purpose: Private conversations inside course messaging.
- Important columns: `chat_group_id`, `type`.

#### `chat_messages`
- Purpose: Stored chat messages and attachments.
- Important columns: `chat_group_id`, `chat_conversation_id`, `user_id`, `message`, `attachment_path`, `edited_at`, `deleted_at`.

#### `chat_message_reactions`
- Purpose: Emoji reactions on chat messages.
- Important columns: `chat_message_id`, `user_id`, `emoji`.

#### `notifications`
- Purpose: Laravel notification store.
- Important columns: `id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`.

### Logs and Security

#### `activity_logs`
- Purpose: User activity trail.
- Important columns: `user_id`, `action`, `description`, `ip_address`, `created_at`.

#### `security_audits`
- Purpose: Security events and investigations.
- Important columns: `user_id`, `event_type`, `severity`, `resolved_by`, `created_at`.

#### `user_sessions`
- Purpose: Application-level active session tracking.
- Important columns: `user_id`, `session_id`, `is_active`, `last_activity_at`.

#### `sessions`
- Purpose: Laravel session storage.

#### `personal_access_tokens`
- Purpose: Token-based auth table if Sanctum is enabled.
- Note: not present in the current migration set.

## Recommended phpMyAdmin Designer Layout
- Left: `users`, `students`, `teachers`, `admins`, role/permission tables
- Upper middle: `colleges`, `programs`, `courses`
- Center: `enrollments`, `attendances`
- Lower middle: `assignments`, `submissions`, `assignment_questions`, `assignment_choices`, `assignment_answers`
- Right middle: `quizzes`, `quiz_questions`, `quiz_attempts`, `quiz_answers`
- Far right: `exams`, `exam_questions`, `student_exam_attempts`, `exam_answers`, `question_banks`, `bank_questions`
- Right edge: `announcements`, `course_resources`, `course_discussions`, chat tables, `notifications`
- Bottom: `activity_logs`, `security_audits`, `user_sessions`, `sessions`, queue tables

## Commands
- Apply schema cleanup: `php artisan migrate`
- Rebuild cached views after deployment: `php artisan view:clear && php artisan view:cache`

## Verification Checklist
- Confirm `courses.program_id` exists and can reference `programs.id`
- Confirm `student_exam_attempts` includes `graded_at` and `graded_by`
- Open teacher, student, and admin dashboards and load course lists
- Create or edit a course, enrollment, assignment, quiz, and exam
- Submit and grade an assignment, quiz, and exam
- Check `php artisan route:list`, `php artisan migrate:status`, and recent Laravel logs for regressions
