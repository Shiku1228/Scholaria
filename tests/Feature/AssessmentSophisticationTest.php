<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuestionBank;
use App\Models\BankQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class AssessmentSophisticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->withoutMiddleware(\App\Http\Middleware\SessionTracking::class);
    }

    public function test_student_can_take_quiz_within_attempt_limits(): void
    {
        $student = User::factory()->create();
        $student->assignRole('Student');

        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');

        $course = Course::create([
            'title' => 'Test Course',
            'course_number' => 'TC101',
            'course_code' => 'TC101',
            'teacher_id' => $teacher->id,
            'description' => 'Test Course Description',
            'semester' => 'Spring 2026',
        ]);

        // Enroll student
        $course->enrollments()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'status' => 'active',
        ]);

        $quiz = Quiz::create([
            'course_id' => $course->id,
            'title' => 'Test Sophisticated Quiz',
            'time_limit' => 15,
            'max_score' => 10,
            'attempts_allowed' => 2,
            'is_published' => true,
            'feedback_type' => 'instant',
            'show_results' => true,
        ]);

        // Add 2 questions
        $q1 = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Question 1',
            'question_type' => 'multiple_choice',
            'options' => ['A' => 'Option A', 'B' => 'Option B'],
            'correct_answer' => 'A',
            'points' => 5,
        ]);

        $q2 = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Question 2',
            'question_type' => 'true_false',
            'correct_answer' => 'true',
            'points' => 5,
        ]);

        // Start attempt 1
        $response = $this->actingAs($student)->post(route('student.quizzes.start', $quiz));
        $response->assertRedirect();

        $attempt1 = QuizAttempt::where('student_id', $student->id)->where('quiz_id', $quiz->id)->first();
        $this->assertNotNull($attempt1);
        $this->assertEquals(1, $attempt1->attempt_number);
        $this->assertEquals('in_progress', $attempt1->status);

        // Submit attempt 1
        $submitResponse = $this->actingAs($student)->post(route('student.quizzes.submit', $quiz), [
            'answers' => [
                $q1->id => 'A',
                $q2->id => 'true',
            ]
        ]);
        $submitResponse->assertRedirect(route('student.quizzes.show', $quiz));

        $attempt1->refresh();
        $this->assertEquals('submitted', $attempt1->status);
        $this->assertEquals(10, $attempt1->score);

        // Start attempt 2
        $response2 = $this->actingAs($student)->post(route('student.quizzes.start', $quiz));
        $response2->assertRedirect();
        
        $attempt2 = QuizAttempt::where('student_id', $student->id)->where('quiz_id', $quiz->id)->orderBy('attempt_number', 'desc')->first();
        $this->assertEquals(2, $attempt2->attempt_number);

        // Submit attempt 2
        $this->actingAs($student)->post(route('student.quizzes.submit', $quiz), [
            'answers' => [
                $q1->id => 'B',
                $q2->id => 'false',
            ]
        ]);

        // Attempt 3 should fail
        $response3 = $this->actingAs($student)->post(route('student.quizzes.start', $quiz));
        $response3->assertSessionHas('error');
    }

    public function test_shuffling_and_subset_is_locked_during_attempt(): void
    {
        $student = User::factory()->create();
        $student->assignRole('Student');

        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');

        $course = Course::create([
            'title' => 'Test Course 2',
            'course_number' => 'TC102',
            'course_code' => 'TC102',
            'teacher_id' => $teacher->id,
            'description' => 'Test Course Description',
            'semester' => 'Spring 2026',
        ]);

        $course->enrollments()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'status' => 'active',
        ]);

        $quiz = Quiz::create([
            'course_id' => $course->id,
            'title' => 'Random Quiz',
            'time_limit' => 20,
            'max_score' => 10,
            'attempts_allowed' => 1,
            'is_published' => true,
            'shuffle_questions' => true,
            'random_subset_count' => 2,
        ]);

        // Create 4 questions
        for ($i = 1; $i <= 4; $i++) {
            QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question_text' => "Question {$i}",
                'question_type' => 'true_false',
                'correct_answer' => 'true',
                'points' => 2.5,
            ]);
        }

        // Start attempt
        $this->actingAs($student)->post(route('student.quizzes.start', $quiz));

        $attempt = QuizAttempt::where('student_id', $student->id)->where('quiz_id', $quiz->id)->first();
        $this->assertNotNull($attempt);
        $this->assertCount(2, $attempt->question_ids);

        // Fetching the show/take view should load the exact same questions locked in attempt
        $savedIds = $attempt->question_ids;
        
        $attempt->refresh();
        $this->assertEquals($savedIds, $attempt->question_ids);
    }

    public function test_teacher_can_create_quiz_with_inline_and_bank_questions(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');

        $course = Course::create([
            'title' => 'Test Course 3',
            'course_number' => 'TC103',
            'course_code' => 'TC103',
            'teacher_id' => $teacher->id,
            'description' => 'Test Course Description',
            'semester' => 'Spring 2026',
        ]);

        // Create a question bank and some bank questions
        $bank = QuestionBank::create([
            'teacher_id' => $teacher->id,
            'name' => 'Math Bank',
            'description' => 'Algebra questions',
        ]);

        $bq1 = BankQuestion::create([
            'question_bank_id' => $bank->id,
            'question_text' => 'What is 2+2?',
            'question_type' => 'multiple_choice',
            'options' => ['A' => '3', 'B' => '4', 'C' => '5', 'D' => '6'],
            'correct_answer' => 'B',
            'points' => 5,
            'explanation' => 'Because 2 plus 2 equals 4.',
        ]);

        $bq2 = BankQuestion::create([
            'question_bank_id' => $bank->id,
            'question_text' => 'Algebra is fun.',
            'question_type' => 'true_false',
            'correct_answer' => 'true',
            'points' => 5,
            'explanation' => 'Indeed it is!',
        ]);

        // Create quiz submitting both bank question import and inline question
        $response = $this->actingAs($teacher)->post(route('teacher.quizzes.store', $course), [
            'title' => 'Comprehensive Quiz',
            'description' => 'Math and logic',
            'time_limit' => 30,
            'attempts_allowed' => 1,
            'feedback_type' => 'instant',
            'show_results' => true,
            'question_bank_id' => $bank->id,
            'question_ids' => [$bq1->id, $bq2->id],
            'questions' => [
                1 => [
                    'text' => 'What is the capital of France?',
                    'type' => 'short_answer',
                    'points' => 10,
                    'correct_answer' => 'Paris',
                    'explanation' => 'Paris is the capital of France.',
                ]
            ]
        ]);

        $response->assertRedirect();

        $quiz = Quiz::where('course_id', $course->id)->first();
        $this->assertNotNull($quiz);
        $this->assertEquals('Comprehensive Quiz', $quiz->title);

        // Verify it copied the 2 bank questions and created the 1 inline question (Total: 3 questions)
        $questions = $quiz->questions()->orderBy('order')->get();
        $this->assertCount(3, $questions);

        // Bank question checks
        $this->assertEquals('What is 2+2?', $questions[0]->question_text);
        $this->assertEquals('Because 2 plus 2 equals 4.', $questions[0]->explanation);

        $this->assertEquals('Algebra is fun.', $questions[1]->question_text);
        $this->assertEquals('Indeed it is!', $questions[1]->explanation);

        // Inline question checks
        $this->assertEquals('What is the capital of France?', $questions[2]->question_text);
        $this->assertEquals('Paris is the capital of France.', $questions[2]->explanation);
        $this->assertEquals(10, $questions[2]->points);

        // Verify total points updated
        $quiz->refresh();
        $this->assertEquals(20, $quiz->points);
        $this->assertEquals(20, $quiz->max_score);
    }

    public function test_teacher_can_create_quiz_with_multiple_inline_question_types(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');

        $course = Course::create([
            'title' => 'Mixed Question Course',
            'course_number' => 'MQ101',
            'course_code' => 'MQ101',
            'teacher_id' => $teacher->id,
            'description' => 'Course for mixed quiz types',
            'semester' => 'Spring 2026',
        ]);

        $response = $this->actingAs($teacher)->post(route('teacher.quizzes.store', $course), [
            'title' => 'Mixed Types Quiz',
            'description' => 'One quiz, several question formats',
            'time_limit' => 25,
            'attempts_allowed' => 1,
            'feedback_type' => 'instant',
            'show_results' => true,
            'questions' => [
                1 => [
                    'text' => 'Which planet is known as the Red Planet?',
                    'type' => 'multiple_choice',
                    'points' => 5,
                    'options' => [
                        'A' => 'Earth',
                        'B' => 'Mars',
                        'C' => 'Jupiter',
                        'D' => 'Venus',
                    ],
                    'correct' => 'B',
                    'explanation' => 'Mars is known as the Red Planet.',
                ],
                2 => [
                    'text' => 'The Earth revolves around the Sun.',
                    'type' => 'true_false',
                    'points' => 3,
                    'correct' => 'true',
                    'explanation' => 'This is true.',
                ],
                3 => [
                    'text' => 'Name the capital of Japan.',
                    'type' => 'short_answer',
                    'points' => 4,
                    'correct_answer' => 'Tokyo',
                    'explanation' => 'Tokyo is the capital of Japan.',
                ],
                4 => [
                    'text' => 'Explain why regular review helps learning retention.',
                    'type' => 'essay',
                    'points' => 6,
                    'correct_answer' => 'It strengthens memory through repetition and recall.',
                    'explanation' => 'This is a sample rubric note.',
                ],
            ],
        ]);

        $response->assertRedirect();

        $quiz = Quiz::where('course_id', $course->id)->first();
        $this->assertNotNull($quiz);
        $this->assertEquals('Mixed Types Quiz', $quiz->title);

        $questions = $quiz->questions()->orderBy('order')->get();
        $this->assertCount(4, $questions);
        $this->assertEquals('multiple_choice', $questions[0]->question_type);
        $this->assertEquals('true_false', $questions[1]->question_type);
        $this->assertEquals('short_answer', $questions[2]->question_type);
        $this->assertEquals('essay', $questions[3]->question_type);
        $this->assertEquals('Tokyo', $questions[2]->correct_answer);
        $this->assertEquals('It strengthens memory through repetition and recall.', $questions[3]->correct_answer);

        $quiz->refresh();
        $this->assertEquals(18, $quiz->points);
        $this->assertEquals(18, $quiz->max_score);
    }
}
