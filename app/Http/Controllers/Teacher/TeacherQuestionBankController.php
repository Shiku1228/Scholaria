<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\QuestionBank;
use App\Models\BankQuestion;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherQuestionBankController extends Controller
{
    public function index(Request $request): View
    {
        $teacherId = (int) $request->user()->id;
        $banks = QuestionBank::where('teacher_id', $teacherId)
            ->withCount('questions')
            ->orderBy('name')
            ->paginate(15);

        return view('teacher.question_banks.index', compact('banks'));
    }

    public function create(): View
    {
        return view('teacher.question_banks.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        QuestionBank::create([
            'teacher_id' => (int) $request->user()->id,
            'name' => $validated['name'],
            'description' => $validated['description'],
        ]);

        return redirect()->route('teacher.question-banks.index')->with('success', 'Question bank created successfully.');
    }

    public function show(Request $request, QuestionBank $question_bank): View
    {
        if ((int) $question_bank->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        $questions = $question_bank->questions()->orderByDesc('id')->get();

        return view('teacher.question_banks.show', [
            'bank' => $question_bank,
            'questions' => $questions,
        ]);
    }

    public function edit(Request $request, QuestionBank $question_bank): View
    {
        if ((int) $question_bank->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        return view('teacher.question_banks.edit', ['bank' => $question_bank]);
    }

    public function update(Request $request, QuestionBank $question_bank)
    {
        if ((int) $question_bank->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $question_bank->update($validated);

        return redirect()->route('teacher.question-banks.show', $question_bank)->with('success', 'Question bank updated successfully.');
    }

    public function destroy(Request $request, QuestionBank $question_bank)
    {
        if ((int) $question_bank->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        $question_bank->delete();

        return redirect()->route('teacher.question-banks.index')->with('success', 'Question bank deleted successfully.');
    }

    public function addQuestion(Request $request, QuestionBank $bank)
    {
        if ((int) $bank->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'question_text' => ['required', 'string'],
            'question_type' => ['required', 'in:multiple_choice,true_false,short_answer,essay'],
            'points' => ['required', 'integer', 'min:1', 'max:100'],
            'options' => ['nullable', 'array'],
            'correct_answer' => ['nullable', 'string'],
            'explanation' => ['nullable', 'string'],
        ]);

        // Build options array for multiple choice
        $options = null;
        if ($validated['question_type'] === 'multiple_choice' && !empty($validated['options'])) {
            $optionArray = [];
            foreach ($validated['options'] as $index => $value) {
                if (!empty($value)) {
                    $letter = chr(65 + $index); // A, B, C, D
                    $optionArray[$letter] = $value;
                }
            }
            $options = !empty($optionArray) ? $optionArray : null;
        }

        $bank->questions()->create([
            'question_text' => $validated['question_text'],
            'question_type' => $validated['question_type'],
            'options' => $options,
            'correct_answer' => $validated['correct_answer'] ?? null,
            'explanation' => $validated['explanation'] ?? null,
            'points' => $validated['points'],
        ]);

        return redirect()->route('teacher.question-banks.show', $bank)->with('success', 'Question added to bank.');
    }

    public function removeQuestion(Request $request, QuestionBank $bank, BankQuestion $question)
    {
        if ((int) $bank->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ((int) $question->question_bank_id !== (int) $bank->id) {
            abort(404);
        }

        $question->delete();

        return redirect()->route('teacher.question-banks.show', $bank)->with('success', 'Question removed from bank.');
    }
}
