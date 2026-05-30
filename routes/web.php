<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\SecurityDashboardController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminCourseController;
use App\Http\Controllers\Admin\AdminEnrollmentController;
use App\Http\Controllers\Admin\AdminRecordsController;
use App\Http\Controllers\Admin\RoleManagementController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Teacher\TeacherDashboardController;
use App\Http\Controllers\Teacher\TeacherCourseController;
use App\Http\Controllers\Teacher\TeacherStudentController;
use App\Http\Controllers\Teacher\TeacherEnrollmentController;
use App\Http\Controllers\Teacher\TeacherAssignmentController;
use App\Http\Controllers\Teacher\TeacherTaskController;
use App\Http\Controllers\Teacher\TeacherAnnouncementController;
use App\Http\Controllers\Teacher\TeacherSubmissionController;
use App\Http\Controllers\Teacher\TeacherNotificationController;
use App\Http\Controllers\Teacher\TeacherQuizController;
use App\Http\Controllers\Teacher\TeacherExamController;
use App\Http\Controllers\Teacher\TeacherQuestionBankController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Http\Controllers\Student\StudentSubmissionController;
use App\Http\Controllers\Student\StudentCourseController;
use App\Http\Controllers\Student\StudentAssignmentController;
use App\Http\Controllers\Student\StudentTaskController;
use App\Http\Controllers\Student\StudentGradeController;
use App\Http\Controllers\Student\StudentExamController;
use App\Http\Controllers\Student\StudentQuizController;
use App\Http\Controllers\Student\StudentAnnouncementController;
use App\Http\Controllers\Student\StudentNotificationController;
use App\Http\Controllers\TwoFactorAuthController;
use App\Http\Controllers\Messaging\CourseMessagingController;
use App\Http\Controllers\JwtTestController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\Calendar\TeacherCalendarController;
use App\Http\Controllers\Calendar\StudentCalendarController;
use App\Http\Controllers\Teacher\TeacherAttendanceController;
use App\Http\Controllers\Student\StudentAttendanceController;
use App\Models\User;
use Illuminate\Http\Request;


Route::get('/jwt-test', [JwtTestController::class, 'index'])->name('jwt.test');

Route::get('/', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }

    $user = auth()->user();

    if (method_exists($user, 'hasRole')) {
        if ($user->hasRole('Admin') || $user->hasRole('Super Admin') || 
            $user->hasRole('Content Admin') || $user->hasRole('User Admin') || 
            $user->hasRole('Report Admin') || $user->hasRole('Settings Admin') ||
            $user->hasRole('Catalog Admin')) {
            return redirect()->route('admin.dashboard');
        }

        // Fallback: Catalog Admin may not be recognized as a role at redirect-time,
        // but it should still go to admin dashboard if it has catalog permissions.
        if ($user->hasPermissionTo('colleges.view') || $user->hasPermissionTo('programs.view')) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasRole('Teacher')) {
            return redirect()->route('teacher.dashboard');
        }
    }

    return redirect()->route('student.dashboard');
});

Route::get('/dashboard', function (Request $request) {
    /** @var User $user */
    $user = $request->user();

    if (method_exists($user, 'getRoleNames')) {
        $roles = $user->getRoleNames();

        if ($roles->isNotEmpty()) {
            if ($roles->diff(['Teacher', 'Student'])->isNotEmpty()) {
                return redirect()->route('admin.dashboard');
            }

            if ($roles->contains('Teacher')) {
                return redirect()->route('teacher.dashboard');
            }
        }
    }

    if (strtolower((string) data_get($user, 'role', '')) === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    if (strtolower((string) data_get($user, 'role', '')) === 'teacher') {
        return redirect()->route('teacher.dashboard');
    }

    return redirect()->route('student.dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::get('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('web.logout.get');
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('web.logout');

Route::middleware(['auth'])->prefix('2fa')->name('2fa.')->group(function () {
    Route::get('/setup', [TwoFactorAuthController::class, 'showSetup'])->name('setup');
    Route::get('/qr-code', [TwoFactorAuthController::class, 'getQrCode'])->name('qr-code');
    Route::post('/enable', [TwoFactorAuthController::class, 'enable'])->name('enable');
    Route::post('/disable', [TwoFactorAuthController::class, 'disable'])->name('disable');
    Route::get('/verify/show', [TwoFactorAuthController::class, 'showVerification'])->name('verify.show');
    Route::post('/verify', [TwoFactorAuthController::class, 'verify'])->name('verify');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'is_admin', 'session.tracking'])
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::post('/users/{user}/restore', [AdminUserController::class, 'restore'])->name('users.restore');
        Route::delete('/users/{user}/force-delete', [AdminUserController::class, 'forceDestroy'])->name('users.force-destroy');
        Route::resource('/users', AdminUserController::class);

        // Security Dashboard Routes
        Route::prefix('security-dashboard')->name('security-dashboard.')->group(function () {
            Route::get('/', [SecurityDashboardController::class, 'index'])->name('index');
            Route::get('/metrics', [SecurityDashboardController::class, 'metrics'])->name('metrics');
            Route::get('/alerts', [SecurityDashboardController::class, 'alerts'])->name('alerts');
            Route::get('/trends', [SecurityDashboardController::class, 'trends'])->name('trends');
            Route::get('/report', [SecurityDashboardController::class, 'report'])->name('report');
            Route::get('/report/download', [SecurityDashboardController::class, 'downloadReport'])->name('report-download');
            Route::get('/event/{id}', [SecurityDashboardController::class, 'event'])->name('event');
            Route::post('/event/{id}/resolve', [SecurityDashboardController::class, 'resolveEvent'])->name('event-resolve');
            Route::get('/failed-logins', [SecurityDashboardController::class, 'failedLogins'])->name('failed-logins');
            Route::get('/sessions', [SecurityDashboardController::class, 'sessions'])->name('sessions');
            Route::get('/anomalies', [SecurityDashboardController::class, 'anomalies'])->name('anomalies');
            Route::get('/activity-log', [SecurityDashboardController::class, 'activityLog'])->name('activity-log');
            
            // Test alert endpoint
            Route::post('/test-alert', [SecurityDashboardController::class, 'testAlert'])->name('test-alert');
        });

        Route::get('/courses/check-number', [AdminCourseController::class, 'checkNumber'])->name('courses.check-number');
        Route::resource('/courses', AdminCourseController::class)->except(['show']);

        Route::resource('/enrollments', AdminEnrollmentController::class)->except(['show']);

        // Colleges & Programs Management
        Route::resource('/colleges', \App\Http\Controllers\Admin\AdminCollegeController::class)->except(['show']);
        Route::resource('/programs', \App\Http\Controllers\Admin\AdminProgramController::class)->except(['show']);

        // Records Management
        Route::get('/records', [AdminRecordsController::class, 'index'])->name('records.index');

        // Role Management Routes
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [RoleManagementController::class, 'index'])->name('index');
            Route::get('/create', [RoleManagementController::class, 'create'])->name('create');
            Route::post('/', [RoleManagementController::class, 'store'])->name('store');
            Route::get('/{role}/edit', [RoleManagementController::class, 'edit'])->name('edit');
            Route::put('/{role}', [RoleManagementController::class, 'update'])->name('update');
            Route::delete('/{role}', [RoleManagementController::class, 'destroy'])->name('destroy');
            Route::post('/assign', [RoleManagementController::class, 'assignRole'])->name('assign');
            Route::post('/{user}/revoke', [RoleManagementController::class, 'revokeRole'])->name('revoke');
        });
    });

Route::middleware(['auth', 'session.tracking'])->prefix('messages')->name('messages.')->group(function () {
    Route::get('/', [CourseMessagingController::class, 'index'])->name('index');
    Route::get('/courses/{course}', [CourseMessagingController::class, 'course'])->name('courses.show');
    Route::get('/courses/{course}/members', [CourseMessagingController::class, 'members'])->name('courses.members');
    Route::get('/courses/{course}/conversations', [CourseMessagingController::class, 'conversations'])->name('courses.conversations');
    Route::get('/courses/{course}/conversations/{conversation}', [CourseMessagingController::class, 'showConversation'])->name('courses.conversations.show');
    Route::post('/courses/{course}/private-chat/{user}', [CourseMessagingController::class, 'startPrivate'])->name('courses.private.start');
    Route::get('/conversations/{conversation}/messages', [CourseMessagingController::class, 'conversationMessages'])->name('conversations.messages');
    Route::post('/conversations/{conversation}/messages', [CourseMessagingController::class, 'storeMessage'])->middleware('throttle:chat-messages')->name('conversations.messages.store');
    Route::patch('/messages/{message}', [CourseMessagingController::class, 'updateMessage'])->middleware('throttle:chat-messages')->name('messages.update');
    Route::delete('/messages/{message}', [CourseMessagingController::class, 'deleteMessage'])->middleware('throttle:chat-messages')->name('messages.delete');
    Route::post('/messages/{message}/reactions', [CourseMessagingController::class, 'reactMessage'])->middleware('throttle:chat-messages')->name('messages.react');
    Route::delete('/messages/{message}/reactions', [CourseMessagingController::class, 'unreactMessage'])->middleware('throttle:chat-messages')->name('messages.unreact');
    Route::get('/attachments/{message}', [CourseMessagingController::class, 'attachment'])->name('attachment');
});

Route::prefix('teacher')
    ->name('teacher.')
    ->middleware(['auth', 'role:Teacher', 'session.tracking'])
    ->group(function () {
        Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');

        Route::get('/calendar', [TeacherCalendarController::class, 'index'])->name('calendar');


        Route::get('/tasks', [TeacherTaskController::class, 'overview'])->name('tasks.overview');
        Route::get('/assignments', [TeacherAssignmentController::class, 'overview'])->name('assignments.overview'); // Keep for backward compatibility
        
        // Quiz routes - same pattern as exams
        Route::get('/quizzes', [TeacherQuizController::class, 'index'])->name('quizzes.index');
        Route::get('/courses/{course}/quizzes/create', [TeacherQuizController::class, 'create'])->name('quizzes.create');
        Route::post('/courses/{course}/quizzes', [TeacherQuizController::class, 'store'])->name('quizzes.store');
        Route::get('/quizzes/{quiz}', [TeacherQuizController::class, 'show'])->name('quizzes.show');
        Route::get('/quizzes/{quiz}/edit', [TeacherQuizController::class, 'edit'])->name('quizzes.edit');
        Route::put('/quizzes/{quiz}', [TeacherQuizController::class, 'update'])->name('quizzes.update');
        Route::delete('/quizzes/{quiz}', [TeacherQuizController::class, 'destroy'])->name('quizzes.destroy');

        // Online Quiz Question Management (same as exams)
        Route::get('/quizzes/{quiz}/questions', [TeacherQuizController::class, 'questions'])->name('quizzes.questions');
        Route::post('/quizzes/{quiz}/questions', [TeacherQuizController::class, 'addQuestion'])->name('quizzes.questions.add');
        Route::delete('/quizzes/{quiz}/questions/{question}', [TeacherQuizController::class, 'removeQuestion'])->name('quizzes.questions.remove');

        // Quiz Publish/Unpublish & Settings
        Route::post('/quizzes/{quiz}/publish', [TeacherQuizController::class, 'publish'])->name('quizzes.publish');
        Route::post('/quizzes/{quiz}/unpublish', [TeacherQuizController::class, 'unpublish'])->name('quizzes.unpublish');
        Route::post('/quizzes/{quiz}/release-results', [TeacherQuizController::class, 'releaseResults'])->name('quizzes.release-results');
        Route::post('/quizzes/{quiz}/import-bank', [TeacherQuizController::class, 'importFromBank'])->name('quizzes.import-bank');

        // Exam routes
        Route::get('/exams', [TeacherExamController::class, 'index'])->name('exams.index');
        Route::get('/courses/{course}/exams/create', [TeacherExamController::class, 'create'])->name('exams.create');
        Route::post('/courses/{course}/exams', [TeacherExamController::class, 'store'])->name('exams.store');
        Route::get('/exams/{exam}', [TeacherExamController::class, 'show'])->name('exams.show');
        Route::get('/exams/{exam}/edit', [TeacherExamController::class, 'edit'])->name('exams.edit');
        Route::put('/exams/{exam}', [TeacherExamController::class, 'update'])->name('exams.update');
        Route::delete('/exams/{exam}', [TeacherExamController::class, 'destroy'])->name('exams.destroy');

        // Online Exam Question Management
        Route::get('/exams/{exam}/questions', [TeacherExamController::class, 'questions'])->name('exams.questions');
        Route::post('/exams/{exam}/questions', [TeacherExamController::class, 'addQuestion'])->name('exams.questions.add');
        Route::delete('/exams/{exam}/questions/{question}', [TeacherExamController::class, 'removeQuestion'])->name('exams.questions.remove');

        // Exam Publish/Unpublish & Settings
        Route::post('/exams/{exam}/publish', [TeacherExamController::class, 'publish'])->name('exams.publish');
        Route::post('/exams/{exam}/unpublish', [TeacherExamController::class, 'unpublish'])->name('exams.unpublish');
        Route::post('/exams/{exam}/release-results', [TeacherExamController::class, 'releaseResults'])->name('exams.release-results');
        Route::post('/exams/{exam}/import-bank', [TeacherExamController::class, 'importFromBank'])->name('exams.import-bank');

        Route::get('/courses', [TeacherCourseController::class, 'index'])->name('courses.index');
        Route::get('/courses/{course}', [TeacherCourseController::class, 'show'])->name('courses.show');
        Route::post('/courses/{course}/cover', [TeacherCourseController::class, 'updateCover'])->name('courses.cover.update');
        Route::post('/courses/{course}/overview', [TeacherCourseController::class, 'updateOverview'])->name('courses.overview.update');
        Route::post('/courses/{course}/resources', [TeacherCourseController::class, 'uploadResource'])->name('courses.resources.store');
        Route::post('/courses/{course}/discussions', [TeacherCourseController::class, 'storeDiscussion'])->name('courses.discussions.store');
        Route::patch('/courses/{course}/discussions/{discussion}', [TeacherCourseController::class, 'updateDiscussion'])->name('courses.discussions.update');
        Route::delete('/courses/{course}/discussions/{discussion}', [TeacherCourseController::class, 'destroyDiscussion'])->name('courses.discussions.destroy');
        Route::post('/courses/{course}/discussions/{discussion}/subscribe', [TeacherCourseController::class, 'toggleDiscussionSubscription'])->name('courses.discussions.subscribe');

        Route::prefix('/courses/{course}')
            ->group(function () {
                Route::resource('/assignments', TeacherAssignmentController::class)->except(['destroy']);
                Route::delete('/assignments/{assignment}', [TeacherAssignmentController::class, 'destroy'])->name('assignments.destroy');

                Route::resource('/announcements', TeacherAnnouncementController::class)->except(['destroy']);
                Route::delete('/announcements/{announcement}', [TeacherAnnouncementController::class, 'destroy'])->name('announcements.destroy');

                Route::patch('/assignments/{assignment}/submissions/{submission}', [TeacherSubmissionController::class, 'update'])->name('submissions.update');

                Route::get('/office-hours', [\App\Http\Controllers\Teacher\TeacherOfficeHourController::class, 'index'])->name('office-hours.index');
                Route::post('/office-hours', [\App\Http\Controllers\Teacher\TeacherOfficeHourController::class, 'store'])->name('office-hours.store');
                Route::post('/office-hours/answer/{question}', [\App\Http\Controllers\Teacher\TeacherOfficeHourController::class, 'answer'])->name('office-hours.answer');
            });

        Route::get('/students', [TeacherStudentController::class, 'index'])->name('students.index');
        Route::get('/enrollments', [TeacherEnrollmentController::class, 'index'])->name('enrollments.index');
        Route::post('/enrollments', [TeacherEnrollmentController::class, 'store'])->name('enrollments.store');
        Route::patch('/enrollments/{enrollment}/complete', [TeacherEnrollmentController::class, 'complete'])->name('enrollments.complete');
        Route::patch('/enrollments/{enrollment}/drop', [TeacherEnrollmentController::class, 'drop'])->name('enrollments.drop');
        Route::patch('/enrollments/{enrollment}/unenroll', [TeacherEnrollmentController::class, 'unenroll'])->name('enrollments.unenroll');
        Route::patch('/enrollments/{enrollment}/reenroll', [TeacherEnrollmentController::class, 'reenroll'])->name('enrollments.reenroll');
        Route::delete('/enrollments/{enrollment}', [TeacherEnrollmentController::class, 'destroy'])->name('enrollments.destroy');

        Route::get('/announcements', [TeacherAnnouncementController::class, 'overview'])->name('announcements');
        Route::redirect('/messages', '/messages')->name('messages');
        // Question Banks Routes
        Route::resource('/question-banks', TeacherQuestionBankController::class);
        Route::post('/question-banks/{bank}/questions', [TeacherQuestionBankController::class, 'addQuestion'])->name('question-banks.questions.add');
        Route::delete('/question-banks/{bank}/questions/{question}', [TeacherQuestionBankController::class, 'removeQuestion'])->name('question-banks.questions.remove');

        Route::get('/attendance', [TeacherAttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance', [TeacherAttendanceController::class, 'store'])->name('attendance.store');
        Route::post('/attendance/bulk', [TeacherAttendanceController::class, 'storeBulk'])->name('attendance.bulk');

        Route::view('/settings', 'teacher.settings')->name('settings');
        Route::post('/notifications/read-all', [TeacherNotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::get('/notifications/{notification}/open', [TeacherNotificationController::class, 'open'])->name('notifications.open');
    });

Route::prefix('student')
    ->name('student.')
    ->middleware(['auth', 'role:Student', 'session.tracking'])
    ->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
        Route::get('/calendar', [StudentCalendarController::class, 'index'])->name('calendar');

        Route::get('/next-up', [\App\Http\Controllers\Student\StudentNextUpController::class, 'index'])->name('next-up');

        Route::get('/courses', [StudentCourseController::class, 'index'])->name('courses.index');

        Route::get('/courses/{course}', [StudentCourseController::class, 'show'])->name('courses.show');
        Route::post('/courses/{course}/discussions', [StudentCourseController::class, 'storeDiscussion'])->name('courses.discussions.store');
        Route::patch('/courses/{course}/discussions/{discussion}', [StudentCourseController::class, 'updateDiscussion'])->name('courses.discussions.update');
        Route::delete('/courses/{course}/discussions/{discussion}', [StudentCourseController::class, 'destroyDiscussion'])->name('courses.discussions.destroy');
        Route::post('/courses/{course}/discussions/{discussion}/subscribe', [StudentCourseController::class, 'toggleDiscussionSubscription'])->name('courses.discussions.subscribe');
        
        Route::get('/courses/{course}/office-hours', [\App\Http\Controllers\Student\StudentOfficeHourController::class, 'index'])->name('courses.office-hours.index');
        Route::post('/courses/{course}/office-hours/{officeHour}/question', [\App\Http\Controllers\Student\StudentOfficeHourController::class, 'storeQuestion'])->name('courses.office-hours.question.store');
        
        Route::get('/tasks', [StudentTaskController::class, 'index'])->name('tasks.index');
        Route::get('/assignments', [StudentAssignmentController::class, 'index'])->name('assignments.index'); // Keep for backward compatibility
        Route::get('/assignments/{assignment}', [StudentAssignmentController::class, 'show'])->name('assignments.show');
        Route::get('/announcements', [StudentAnnouncementController::class, 'index'])->name('announcements.index');
        Route::get('/grades', [StudentGradeController::class, 'index'])->name('grades.index');

        Route::get('/assignments/{assignment}/submit', [StudentSubmissionController::class, 'create'])->name('assignments.submit');
        Route::post('/assignments/{assignment}/submit', [StudentSubmissionController::class, 'store'])->name('assignments.submit.store');
        
        // Student Exam Routes
        Route::get('/exams', [StudentExamController::class, 'index'])->name('exams.index');
        Route::get('/exams/{exam}', [StudentExamController::class, 'show'])->name('exams.show');
        Route::post('/exams/{exam}/start', [StudentExamController::class, 'start'])->name('exams.start');
        Route::post('/exams/{exam}/submit', [StudentExamController::class, 'submit'])->name('exams.submit');

        // Student Quiz Routes
        Route::get('/quizzes', [StudentQuizController::class, 'index'])->name('quizzes.index');
        Route::get('/quizzes/{quiz}', [StudentQuizController::class, 'show'])->name('quizzes.show');
        Route::post('/quizzes/{quiz}/start', [StudentQuizController::class, 'start'])->name('quizzes.start');
        Route::post('/quizzes/{quiz}/submit', [StudentQuizController::class, 'submit'])->name('quizzes.submit');

        Route::get('/attendance', [StudentAttendanceController::class, 'index'])->name('attendance.index');

        Route::post('/notifications/read-all', [StudentNotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::get('/notifications/{notification}/open', [StudentNotificationController::class, 'open'])->name('notifications.open');
        Route::redirect('/messages', '/messages')->name('messages');
    });

// OAuth Authentication Routes
Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('auth.redirect');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('auth.callback');
// Security and Session Management Routes
Route::middleware(['auth', 'session.tracking'])->prefix('security')->group(function () {
    Route::get('/sessions', [SessionController::class, 'index'])->name('security.sessions');
    Route::get('/sessions/stats', [SessionController::class, 'stats'])->name('security.sessions.stats');
    Route::get('/sessions/realtime', [SessionController::class, 'realtime'])->name('security.sessions.realtime');
    Route::post('/sessions/{sessionId}/end', [SessionController::class, 'end'])->name('security.sessions.end');
    Route::post('/sessions/end-others', [SessionController::class, 'endAllOthers'])->name('security.sessions.end-others');
    Route::get('/sessions/{sessionId}', [SessionController::class, 'show'])->name('security.sessions.show');
});
