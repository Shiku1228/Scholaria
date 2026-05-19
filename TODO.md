# TODO - Learning flow enhancements

## Phase 1 (immediate, low-risk UI improvements)
- [ ] Update `StudentDashboardController@index` to compute richer “Next up” categories (Due soon / Not started / Needs review / Recommended) using existing DB: `assignments`, `submissions`, `quiz_attempts`, `student_exam_attempts`, `course_discussions`.
- [ ] Update `resources/views/student/dashboard.blade.php` to display the “Next up” widget with the above categories.
- [ ] Add a lightweight student calendar view for due items (can be list-based at first) using the same due_date sources.



## Phase 2 (rubrics & feedback workflow)
- [ ] Add DB migrations/tables for rubric + rubric criteria + scoring breakdown storage.

- [ ] Extend teacher assignment grading UI to save rubric results + criterion scores + feedback.
- [ ] Extend student assignment/graded view to show rubric breakdown + downloadable feedback/rubric.

## Phase 3 (module/unit structure)
- [ ] Add DB migrations/tables for course modules/units with week/module ordering.
- [ ] Update teacher planning UI to create/organize module content.
- [ ] Update assignment/quiz/exam/announcement creation screens to attach items to modules.

## Phase 4 (progress tracking enrichment)
- [ ] Add tracking for resource views/downloads and discussion activity.
- [ ] Update progress aggregation to incorporate: resources (optional), assignments, quizzes/exams attempts, discussions.

## Phase 5 (performance & correctness)
- [ ] Refactor dashboard queries to avoid N+1 and add indexes where needed.
- [ ] Add tests (feature/unit) for progress and next-up categorization.

