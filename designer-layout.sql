-- =============================================================
-- Scholaria – phpMyAdmin Designer Layout  (phpMyAdmin 5.x)
-- Run this from ANY database SQL tab in phpMyAdmin.
--
-- phpMyAdmin 5.x Designer uses pma__pdf_pages + pma__table_coords,
-- NOT pma__designer_coords.  This script creates a named page
-- called "Scholaria Layout" and sets every table's x/y position.
--
-- Does NOT touch any scholaria data, columns, or relationships.
-- =============================================================

-- Step 1: Remove old coords for any existing "Scholaria Layout" page.
DELETE c
FROM `phpmyadmin`.`pma__table_coords`  c
JOIN `phpmyadmin`.`pma__pdf_pages`     p ON c.`pdf_page_number` = p.`page_nr`
WHERE p.`db_name` = 'scholaria'
  AND p.`page_descr` = 'Scholaria Layout';

DELETE FROM `phpmyadmin`.`pma__pdf_pages`
WHERE `db_name` = 'scholaria'
  AND `page_descr` = 'Scholaria Layout';

-- Step 2: Create the layout page and capture its auto-increment id.
INSERT INTO `phpmyadmin`.`pma__pdf_pages` (`db_name`, `page_descr`)
VALUES ('scholaria', 'Scholaria Layout');

SET @pg = LAST_INSERT_ID();

-- Step 3: Insert table positions.
-- Layout groups (x = column, y = row):
--   Col 1 (x~20)    : User core + profiles + permissions
--   Col 2 (x~1500)  : Academic hierarchy, enrollment, attendance, office hours
--   Col 3 (x~1500+) : Assignments + subtables
--   Col 4 (x~2660)  : Quizzes
--   Col 5 (x~2950)  : Exams + question banks
--   Col 6 (x~3530)  : Communication, chat
--   Bottom row      : Logs, security, sessions, queue, cache

INSERT INTO `phpmyadmin`.`pma__table_coords`
  (`db_name`, `table_name`, `pdf_page_number`, `x`, `y`)
VALUES
-- -------------------------------------------------------
-- SECTION 1 · User Management  (top-left)
-- -------------------------------------------------------
('scholaria', 'users',                    @pg,   20,   20),

('scholaria', 'students',                 @pg,   20,  400),
('scholaria', 'teachers',                 @pg,  310,  400),
('scholaria', 'admins',                   @pg,  600,  400),

('scholaria', 'roles',                    @pg,   20,  720),
('scholaria', 'permissions',              @pg,  310,  720),
('scholaria', 'model_has_roles',          @pg,  600,  720),
('scholaria', 'model_has_permissions',    @pg,  890,  720),
('scholaria', 'role_has_permissions',     @pg, 1180,  720),

-- -------------------------------------------------------
-- SECTION 2 · Academic Hierarchy  (top-center)
-- -------------------------------------------------------
('scholaria', 'colleges',                 @pg, 1500,   20),
('scholaria', 'programs',                 @pg, 1790,   20),
('scholaria', 'courses',                  @pg, 2080,   20),

-- -------------------------------------------------------
-- SECTION 3 · Enrollment & Attendance
-- -------------------------------------------------------
('scholaria', 'enrollments',              @pg, 1500,  400),
('scholaria', 'attendances',              @pg, 1790,  400),

-- -------------------------------------------------------
-- SECTION 4 · Office Hours
-- -------------------------------------------------------
('scholaria', 'office_hours',             @pg, 2080,  400),
('scholaria', 'office_hour_questions',    @pg, 2370,  400),

-- -------------------------------------------------------
-- SECTION 5 · Assignments
-- -------------------------------------------------------
('scholaria', 'assignments',              @pg, 1500,  720),
('scholaria', 'submissions',              @pg, 1790,  720),
('scholaria', 'grades',                   @pg, 2080,  720),

('scholaria', 'assignment_questions',     @pg, 1500, 1060),
('scholaria', 'assignment_choices',       @pg, 1790, 1060),
('scholaria', 'assignment_answers',       @pg, 2080, 1060),

-- -------------------------------------------------------
-- SECTION 6 · Quizzes
-- -------------------------------------------------------
('scholaria', 'quizzes',                  @pg, 2660,   20),
('scholaria', 'quiz_questions',           @pg, 2660,  400),
('scholaria', 'quiz_attempts',            @pg, 2660,  720),
('scholaria', 'quiz_answers',             @pg, 2660, 1060),

-- -------------------------------------------------------
-- SECTION 7 · Exams & Question Banks
-- -------------------------------------------------------
('scholaria', 'exams',                    @pg, 2950,   20),
('scholaria', 'exam_questions',           @pg, 2950,  400),
('scholaria', 'student_exam_attempts',    @pg, 2950,  720),
('scholaria', 'exam_answers',             @pg, 2950, 1060),

('scholaria', 'question_banks',           @pg, 3240,   20),
('scholaria', 'bank_questions',           @pg, 3240,  400),

-- -------------------------------------------------------
-- SECTION 8 · Communication & Content
-- -------------------------------------------------------
('scholaria', 'announcements',            @pg, 3530,   20),
('scholaria', 'announcement_reads',       @pg, 3530,  400),
('scholaria', 'course_resources',         @pg, 3530,  720),

('scholaria', 'course_discussions',       @pg, 3820,   20),
('scholaria', 'discussion_subscriptions', @pg, 3820,  400),
('scholaria', 'notifications',            @pg, 3820,  720),

-- -------------------------------------------------------
-- SECTION 9 · Chat
-- -------------------------------------------------------
('scholaria', 'chat_groups',              @pg, 4110,   20),
('scholaria', 'chat_group_user',          @pg, 4110,  400),
('scholaria', 'chat_conversations',       @pg, 4110,  720),
('scholaria', 'chat_conversation_user',   @pg, 4110, 1060),
('scholaria', 'chat_messages',            @pg, 4110, 1380),
('scholaria', 'chat_message_reactions',   @pg, 4400, 1380),

-- -------------------------------------------------------
-- SECTION 10 · Logs, Security & System  (bottom row)
-- -------------------------------------------------------
('scholaria', 'activity_logs',            @pg,   20, 1380),
('scholaria', 'security_audits',          @pg,  310, 1380),
('scholaria', 'user_sessions',            @pg,  600, 1380),
('scholaria', 'sessions',                 @pg,  890, 1380),
('scholaria', 'transactions',             @pg, 1180, 1380),
('scholaria', 'password_reset_tokens',    @pg, 1470, 1380),

-- -------------------------------------------------------
-- SECTION 11 · Queue & Cache  (bottom row, continued)
-- -------------------------------------------------------
('scholaria', 'cache',                    @pg, 1760, 1380),
('scholaria', 'cache_locks',              @pg, 2050, 1380),
('scholaria', 'failed_jobs',              @pg, 2340, 1380),
('scholaria', 'jobs',                     @pg, 2630, 1380),
('scholaria', 'job_batches',              @pg, 2920, 1380);
