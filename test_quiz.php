<?php
// Boot Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Teacher\TeacherTaskController;

echo "--- 1. Testing Quiz Model 'duration' attribute ---\n";
$quiz = Quiz::first();
if ($quiz) {
    echo "Quiz Title: " . $quiz->title . "\n";
    echo "Quiz time_limit: " . $quiz->time_limit . "\n";
    echo "Quiz duration attribute: " . $quiz->duration . "\n";
    if ($quiz->duration === $quiz->time_limit) {
        echo "SUCCESS: duration attribute correctly maps to time_limit!\n";
    } else {
        echo "FAILED: duration attribute does not match time_limit!\n";
    }
} else {
    echo "No quizzes found in DB to test model attribute, but Quiz model loaded successfully.\n";
}

echo "\n--- 2. Testing TeacherTaskController Overview Query ---\n";
// Find a teacher user
$teacher = User::find(10);
if (!$teacher) {
    $teacher = User::role('Teacher')->first();
}

if ($teacher) {
    echo "Found Teacher: " . $teacher->name . " (ID: " . $teacher->id . ")\n";
    
    // Bind the user to request
    $request = Request::create('/teacher/tasks', 'GET');
    $request->setUserResolver(function () use ($teacher) {
        return $teacher;
    });

    try {
        $controller = new TeacherTaskController();
        $response = $controller->overview($request);
        $data = $response->getData();
        
        echo "SUCCESS: TeacherTaskController@overview ran successfully!\n";
        echo "Quizzes Count: " . count($data['quizzes']) . "\n";
        if (count($data['quizzes']) > 0) {
            $firstQuiz = $data['quizzes']->first();
            echo "First Quiz Info: " . $firstQuiz->title . "\n";
            echo "First Quiz Duration: " . ($firstQuiz->duration ?? 'N/A') . "\n";
            echo "First Quiz Time Limit: " . ($firstQuiz->time_limit ?? 'N/A') . "\n";
            echo "First Quiz Course: " . $firstQuiz->course_title . " (" . $firstQuiz->course_number . ")\n";
            echo "First Quiz Completion Rate: " . $firstQuiz->completion_rate . "%\n";
        } else {
            echo "No quizzes matched this teacher.\n";
            echo "All Quizzes in DB:\n";
            foreach (Quiz::with('course')->get() as $q) {
                echo "  Quiz ID: {$q->id}, Title: {$q->title}, Course ID: {$q->course_id}, Course Teacher ID: " . ($q->course->teacher_id ?? 'N/A') . ", Published: " . ($q->is_published ? 'Yes' : 'No') . "\n";
            }
        }
    } catch (\Throwable $e) {
        echo "FAILED to run TeacherTaskController@overview: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
    }
} else {
    echo "No teacher user found in DB to test query.\n";
}
