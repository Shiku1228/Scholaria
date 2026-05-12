<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\SecurityAudit;
use Exception;

class DataIntegrityService
{
    /**
     * Run all data integrity checks.
     *
     * @return array Results of all checks
     */
    public function runAllChecks(): array
    {
        $results = [
            'timestamp' => now()->toDateTimeString(),
            'checks' => [],
            'total_violations' => 0,
            'critical_violations' => 0,
        ];

        $checks = [
            'referential_integrity' => $this->checkReferentialIntegrity(),
            'orphaned_records' => $this->checkOrphanedRecords(),
            'duplicate_records' => $this->checkDuplicateRecords(),
            'foreign_key_constraints' => $this->checkForeignKeyConstraints(),
            'unique_constraints' => $this->checkUniqueConstraints(),
            'data_consistency' => $this->checkDataConsistency(),
        ];

        foreach ($checks as $checkName => $checkResult) {
            $results['checks'][$checkName] = $checkResult;
            $results['total_violations'] += $checkResult['violations_count'];
            
            if ($checkResult['severity'] === 'critical') {
                $results['critical_violations'] += $checkResult['violations_count'];
            }
        }

        // Log summary
        Log::info('Data integrity check completed', [
            'total_violations' => $results['total_violations'],
            'critical_violations' => $results['critical_violations'],
        ]);

        // Create security audit if critical violations found
        if ($results['critical_violations'] > 0) {
            SecurityAudit::logSecurityBreach([
                'description' => "Critical data integrity violations detected: {$results['critical_violations']} violations",
                'event_data' => $results,
            ]);
        }

        return $results;
    }

    /**
     * Check referential integrity across all tables.
     *
     * @return array
     */
    protected function checkReferentialIntegrity(): array
    {
        $violations = [];
        $tables = ['enrollments', 'grades', 'assignments', 'announcements', 'course_resources', 'course_discussions'];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $tableViolations = $this->checkTableReferentialIntegrity($table);
            if (!empty($tableViolations)) {
                $violations[$table] = $tableViolations;
            }
        }

        return [
            'status' => empty($violations) ? 'passed' : 'failed',
            'severity' => empty($violations) ? 'info' : 'critical',
            'violations_count' => count($violations),
            'violations' => $violations,
        ];
    }

    /**
     * Check referential integrity for a specific table.
     *
     * @param string $table
     * @return array
     */
    protected function checkTableReferentialIntegrity(string $table): array
    {
        $violations = [];

        try {
            switch ($table) {
                case 'enrollments':
                    // Check for enrollments with non-existent students
                    $orphanedStudents = DB::table('enrollments')
                        ->leftJoin('users', 'enrollments.student_id', '=', 'users.id')
                        ->whereNull('users.id')
                        ->count();
                    
                    if ($orphanedStudents > 0) {
                        $violations[] = "Found {$orphanedStudents} enrollments with non-existent students";
                    }

                    // Check for enrollments with non-existent courses
                    $orphanedCourses = DB::table('enrollments')
                        ->leftJoin('courses', 'enrollments.course_id', '=', 'courses.id')
                        ->whereNull('courses.id')
                        ->count();
                    
                    if ($orphanedCourses > 0) {
                        $violations[] = "Found {$orphanedCourses} enrollments with non-existent courses";
                    }
                    break;

                case 'grades':
                    // Check for grades with non-existent students
                    $orphanedStudents = DB::table('grades')
                        ->leftJoin('users', 'grades.student_id', '=', 'users.id')
                        ->whereNull('users.id')
                        ->count();
                    
                    if ($orphanedStudents > 0) {
                        $violations[] = "Found {$orphanedStudents} grades with non-existent students";
                    }

                    // Check for grades with non-existent assignments
                    $orphanedAssignments = DB::table('grades')
                        ->leftJoin('assignments', 'grades.assignment_id', '=', 'assignments.id')
                        ->whereNull('assignments.id')
                        ->count();
                    
                    if ($orphanedAssignments > 0) {
                        $violations[] = "Found {$orphanedAssignments} grades with non-existent assignments";
                    }
                    break;

                case 'assignments':
                    // Check for assignments with non-existent courses
                    $orphanedCourses = DB::table('assignments')
                        ->leftJoin('courses', 'assignments.course_id', '=', 'courses.id')
                        ->whereNull('courses.id')
                        ->count();
                    
                    if ($orphanedCourses > 0) {
                        $violations[] = "Found {$orphanedCourses} assignments with non-existent courses";
                    }
                    break;

                case 'announcements':
                    // Check for announcements with non-existent courses
                    $orphanedCourses = DB::table('announcements')
                        ->leftJoin('courses', 'announcements.course_id', '=', 'courses.id')
                        ->whereNull('courses.id')
                        ->count();
                    
                    if ($orphanedCourses > 0) {
                        $violations[] = "Found {$orphanedCourses} announcements with non-existent courses";
                    }
                    break;

                case 'course_resources':
                    // Check for resources with non-existent courses
                    $orphanedCourses = DB::table('course_resources')
                        ->leftJoin('courses', 'course_resources.course_id', '=', 'courses.id')
                        ->whereNull('courses.id')
                        ->count();
                    
                    if ($orphanedCourses > 0) {
                        $violations[] = "Found {$orphanedCourses} course resources with non-existent courses";
                    }
                    break;

                case 'course_discussions':
                    // Check for discussions with non-existent courses
                    $orphanedCourses = DB::table('course_discussions')
                        ->leftJoin('courses', 'course_discussions.course_id', '=', 'courses.id')
                        ->whereNull('courses.id')
                        ->count();
                    
                    if ($orphanedCourses > 0) {
                        $violations[] = "Found {$orphanedCourses} course discussions with non-existent courses";
                    }
                    break;
            }
        } catch (Exception $e) {
            $violations[] = "Error checking referential integrity: " . $e->getMessage();
        }

        return $violations;
    }

    /**
     * Check for orphaned records (records with deleted parents).
     *
     * @return array
     */
    protected function checkOrphanedRecords(): array
    {
        $violations = [];

        try {
            // Check for orphaned chat messages
            $orphanedMessages = DB::table('chat_messages')
                ->leftJoin('chat_conversations', 'chat_messages.chat_conversation_id', '=', 'chat_conversations.id')
                ->whereNull('chat_conversations.id')
                ->count();
            
            if ($orphanedMessages > 0) {
                $violations[] = "Found {$orphanedMessages} orphaned chat messages";
            }

            // Check for orphaned chat group users
            $orphanedGroupUsers = DB::table('chat_group_user')
                ->leftJoin('chat_groups', 'chat_group_user.chat_group_id', '=', 'chat_groups.id')
                ->whereNull('chat_groups.id')
                ->count();
            
            if ($orphanedGroupUsers > 0) {
                $violations[] = "Found {$orphanedGroupUsers} orphaned chat group users";
            }

        } catch (Exception $e) {
            $violations[] = "Error checking orphaned records: " . $e->getMessage();
        }

        return [
            'status' => empty($violations) ? 'passed' : 'failed',
            'severity' => empty($violations) ? 'info' : 'medium',
            'violations_count' => count($violations),
            'violations' => $violations,
        ];
    }

    /**
     * Check for duplicate records.
     *
     * @return array
     */
    protected function checkDuplicateRecords(): array
    {
        $violations = [];

        try {
            // Check for duplicate enrollments (same student in same course)
            $duplicateEnrollments = DB::table('enrollments')
                ->select('student_id', 'course_id', DB::raw('COUNT(*) as count'))
                ->groupBy('student_id', 'course_id')
                ->having('count', '>', 1)
                ->get();
            
            if ($duplicateEnrollments->count() > 0) {
                $violations[] = "Found {$duplicateEnrollments->count()} duplicate enrollment records";
            }

            // Check for duplicate grades (same student for same assignment)
            $duplicateGrades = DB::table('grades')
                ->select('student_id', 'assignment_id', DB::raw('COUNT(*) as count'))
                ->groupBy('student_id', 'assignment_id')
                ->having('count', '>', 1)
                ->get();
            
            if ($duplicateGrades->count() > 0) {
                $violations[] = "Found {$duplicateGrades->count()} duplicate grade records";
            }

        } catch (Exception $e) {
            $violations[] = "Error checking duplicate records: " . $e->getMessage();
        }

        return [
            'status' => empty($violations) ? 'passed' : 'failed',
            'severity' => empty($violations) ? 'info' : 'medium',
            'violations_count' => count($violations),
            'violations' => $violations,
        ];
    }

    /**
     * Check foreign key constraints.
     *
     * @return array
     */
    protected function checkForeignKeyConstraints(): array
    {
        $violations = [];

        try {
            // Get all foreign key constraints from the database
            $constraints = DB::select("
                SELECT 
                    TABLE_NAME,
                    CONSTRAINT_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE REFERENCED_TABLE_NAME IS NOT NULL
                AND TABLE_SCHEMA = DATABASE()
            ");

            foreach ($constraints as $constraint) {
                $table = $constraint->TABLE_NAME;
                $refTable = $constraint->REFERENCED_TABLE_NAME;
                
                // Check if referenced table exists
                if (!Schema::hasTable($refTable)) {
                    $violations[] = "Foreign key {$constraint->CONSTRAINT_NAME} references non-existent table {$refTable}";
                }
            }

        } catch (Exception $e) {
            $violations[] = "Error checking foreign key constraints: " . $e->getMessage();
        }

        return [
            'status' => empty($violations) ? 'passed' : 'failed',
            'severity' => empty($violations) ? 'info' : 'critical',
            'violations_count' => count($violations),
            'violations' => $violations,
        ];
    }

    /**
     * Check unique constraints.
     *
     * @return array
     */
    protected function checkUniqueConstraints(): array
    {
        $violations = [];

        try {
            // Check for duplicate emails in users table
            $duplicateEmails = DB::table('users')
                ->select('email', DB::raw('COUNT(*) as count'))
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->groupBy('email')
                ->having('count', '>', 1)
                ->get();
            
            if ($duplicateEmails->count() > 0) {
                $violations[] = "Found {$duplicateEmails->count()} duplicate email addresses in users table";
            }

            // Check for duplicate student numbers
            $duplicateStudentNumbers = DB::table('users')
                ->select('student_number', DB::raw('COUNT(*) as count'))
                ->whereNotNull('student_number')
                ->where('student_number', '!=', '')
                ->groupBy('student_number')
                ->having('count', '>', 1)
                ->get();
            
            if ($duplicateStudentNumbers->count() > 0) {
                $violations[] = "Found {$duplicateStudentNumbers->count()} duplicate student numbers";
            }

        } catch (Exception $e) {
            $violations[] = "Error checking unique constraints: " . $e->getMessage();
        }

        return [
            'status' => empty($violations) ? 'passed' : 'failed',
            'severity' => empty($violations) ? 'info' : 'high',
            'violations_count' => count($violations),
            'violations' => $violations,
        ];
    }

    /**
     * Check data consistency across related tables.
     *
     * @return array
     */
    protected function checkDataConsistency(): array
    {
        $violations = [];

        try {
            // Check if students have enrollments
            $studentsWithoutEnrollments = DB::table('users')
                ->leftJoin('enrollments', 'users.id', '=', 'enrollments.student_id')
                ->whereNull('enrollments.id')
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'student');
                })
                ->count();
            
            // This is informational, not necessarily a violation
            if ($studentsWithoutEnrollments > 0) {
                $violations[] = "Found {$studentsWithoutEnrollments} students without any enrollments (informational)";
            }

            // Check if courses have enrollments
            $coursesWithoutEnrollments = DB::table('courses')
                ->leftJoin('enrollments', 'courses.id', '=', 'enrollments.course_id')
                ->whereNull('enrollments.id')
                ->count();
            
            if ($coursesWithoutEnrollments > 0) {
                $violations[] = "Found {$coursesWithoutEnrollments} courses without any enrollments (informational)";
            }

        } catch (Exception $e) {
            $violations[] = "Error checking data consistency: " . $e->getMessage();
        }

        return [
            'status' => 'passed', // These are informational
            'severity' => 'info',
            'violations_count' => count($violations),
            'violations' => $violations,
        ];
    }

    /**
     * Fix orphaned records (with confirmation).
     *
     * @param string $table The table to fix
     * @param array $orphanedIds The IDs of orphaned records
     * @return int Number of records deleted
     */
    public function fixOrphanedRecords(string $table, array $orphanedIds): int
    {
        try {
            $deleted = DB::table($table)->whereIn('id', $orphanedIds)->delete();
            
            Log::info("Fixed orphaned records in {$table}", [
                'deleted_count' => $deleted,
            ]);

            return $deleted;
        } catch (Exception $e) {
            Log::error("Failed to fix orphaned records in {$table}", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get data integrity report summary.
     *
     * @return array
     */
    public function getSummary(): array
    {
        $results = $this->runAllChecks();
        
        return [
            'timestamp' => $results['timestamp'],
            'total_violations' => $results['total_violations'],
            'critical_violations' => $results['critical_violations'],
            'status' => $results['critical_violations'] > 0 ? 'critical' : 
                       ($results['total_violations'] > 0 ? 'warning' : 'healthy'),
            'checks_passed' => collect($results['checks'])->where('status', 'passed')->count(),
            'checks_failed' => collect($results['checks'])->where('status', 'failed')->count(),
        ];
    }
}
