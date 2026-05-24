# Student Course UI Blueprint

This file shows how the Student Course section is built on the web side so the Android version can mirror the same structure.

## Entry Points

### Course List

- View: `resources/views/student/courses/index.blade.php`
- Controller: `app/Http/Controllers/Student/StudentCourseController.php@index`

### Course Detail

- View: `resources/views/student/courses/show.blade.php`
- Controller: `app/Http/Controllers/Student/StudentCourseController.php@show`

### Navbar Tasks Hub

- View: `resources/views/student/tasks/index.blade.php`
- Controller: `app/Http/Controllers/Student/StudentTaskController.php@index`

This is the main `Tasks` page you reach from the student navbar/sidebar. It is separate from the Tasks tab inside a course.

## Screen Tree

```text
Student Home
└── My Courses
    ├── Course Card
    │   ├── Open Course
    │   └── View Assignments
    └── Course Detail
        ├── Overview
        ├── Tasks
        │   ├── Assignments
        │   ├── Exams
        │   └── Quizzes
        ├── Resources
        ├── Discussion
        ├── Live Q&A
        ├── Tasks Summary
        └── Upcoming Assignments

Student Home
└── Tasks
    ├── Header
    │   ├── Page title
    │   ├── Page subtitle
    │   └── Course filter
    ├── Task Type Tabs
    │   ├── Assignments
    │   ├── Exams
    │   └── Quizzes
    └── Active Table View
        ├── Assignment rows
        ├── Exam rows
        └── Quiz rows
```

## Navbar Tasks Page

This page acts like a global task dashboard for the student.

### What the web page shows

- page title: `Tasks`
- subtitle: `View and manage your assignments, exams, and quizzes.`
- course filter dropdown
- optional clear filter button
- three task tabs:
  - `Assignments`
  - `Exams`
  - `Quizzes`
- active tab count badge
- active filters chip when a course is selected
- a table-like list for the selected tab

### Main UI Layout

The navbar Tasks page has a very structured dashboard feel:

- top header area
- filter row
- horizontal tab bar
- one data table at a time
- empty state row when the selected tab has no records

### Android Match

For Android, this should become:

- a dedicated `Tasks` screen
- a header with title and subtitle
- a course dropdown or filter chip
- segmented tabs or top tabs for the three task types
- a `RecyclerView` or `LazyColumn` per tab
- an empty state view for no results

### Recommended Mobile Behavior

- keep the course filter above the task list
- show the active tab count in the tab label
- open assignment, exam, or quiz detail when a row is tapped
- preserve the `Clear` action when a course filter is active

### Data Flow

The page is built from these steps:

1. load enrolled courses
2. load assignments, exams, and quizzes for the student
3. apply optional `course_id` filter
4. compute status fields like submitted, pending, overdue, or completed
5. render the selected tab table

### Workflow

Use this exact flow for Android if you want the same student experience:

1. open the Tasks screen from the navbar
2. load the default tab: `Assignments`
3. show the course filter dropdown or chip list
4. allow the student to switch between `Assignments`, `Exams`, and `Quizzes`
5. refresh the list when the tab changes
6. refresh the list when the course filter changes
7. show the current counts on each tab
8. show an empty state when no rows match
9. open the matching detail screen when a row is tapped

### Required Functions

These are the main functions the web version already supports and Android should mirror:

- `loadTasks()`
  - fetch assignments, exams, and quizzes for the signed-in student
- `filterByCourse(courseId)`
  - limit the list to one course or show all courses
- `switchTab(tabType)`
  - change between `assignments`, `exams`, and `quizzes`
- `openAssignment(assignmentId)`
  - navigate to assignment details
- `openExam(examId)`
  - navigate to exam details
- `openQuiz(quizId)`
  - navigate to quiz details
- `clearTaskFilter()`
  - remove the selected course filter

### Task Module Flows

#### Assignments

Assignment flow on the web:

1. student opens the assignments tab
2. list shows title, course, due date, status, score, and action
3. student taps `View` or `Submit`
4. details screen opens
5. if not submitted, student taps `Submit Assignment`
6. student chooses submission type:
  - text
  - file
  - link
7. student submits the form
8. the backend saves the submission and returns to the assignment page

Android should mirror this as:

- list screen
- detail screen
- submission screen
- success state after submit

#### Exams

Exam flow on the web:

1. student opens the exams tab
2. list shows title, course, exam date, status, score, and action
3. student taps `Take Exam` or `View`
4. detail screen checks availability and attempts remaining
5. student taps `Start Exam`
6. the app creates a new attempt and opens the timed question screen
7. timer counts down
8. student answers questions
9. student taps submit or time expires
10. answers are saved and the exam result page opens

Android should mirror this as:

- exam list
- exam detail
- timed exam-taking screen
- result/review screen

#### Quizzes

Quiz flow on the web:

1. student opens the quizzes tab
2. list shows title, course, due date, status, score, and action
3. student taps `Start Quiz`, `Retake Quiz`, or `View Results`
4. detail screen checks availability, start date, due date, and attempts remaining
5. student taps `Start Quiz`
6. the app creates a new attempt and opens the quiz form
7. timer counts down
8. student answers questions
9. student submits or the timer expires
10. answers are saved and the review page opens

Android should mirror this as:

- quiz list
- quiz detail
- timed quiz-taking screen
- result/review screen

### Action-Based UI States

To make the Android UI match the current web behavior, treat each task row as a state-driven card with one primary action and one secondary action when needed.

#### Assignment Actions

- `View Assignment`
  - opens the assignment details page
- `Submit Assignment`
  - opens the submission form
- `View Submission`
  - opens the submitted assignment details
- `Cancel`
  - returns to the assignment page without saving

Important:

- the current backend allows a student to submit an assignment, but it does not expose a separate post-submit edit screen
- if you want editing later, that needs a new backend flow and endpoint

#### Exam Actions

- `Take Exam`
  - starts a new timed attempt
- `Retake Exam`
  - starts another allowed attempt
- `View`
  - opens the exam detail and review page
- `Submit Exam`
  - finalizes the current attempt

#### Quiz Actions

- `Start Quiz`
  - starts a new timed attempt
- `Retake Quiz`
  - starts another allowed attempt
- `View Results`
  - opens the review page for a submitted attempt
- `Submit Quiz`
  - finalizes the current attempt

### Android UI Layout For Each Task Type

#### Assignments Screen

Use this screen structure:

- top app bar with `Assignments`
- course filter chip or dropdown
- assignment list cards
- each card shows:
  - title
  - course
  - due date
  - status chip
  - score if submitted
  - primary button:
    - `View Assignment`
    - `Submit Assignment`
- empty state when there are no assignments

#### Assignment Detail Screen

Use this screen structure:

- title header
- course name
- due date card
- max score card
- status card
- description card
- submission summary card if already submitted
- bottom action bar:
  - `Back`
  - `Submit Assignment` if not yet submitted

#### Assignment Submission Screen

Use this screen structure:

- top bar with assignment title
- submission type segmented control:
  - `Text`
  - `File`
  - `Link`
- input area that changes based on the selected type
- helper text for validation rules
- bottom buttons:
  - `Cancel`
  - `Send`

#### Exams Screen

Use this screen structure:

- top app bar with `My Exams`
- exam list cards
- each card shows:
  - title
  - course
  - exam date
  - duration
  - questions count
  - status chip
  - score if completed
  - primary button:
    - `Take Exam`
    - `View`

#### Exam Detail Screen

Use this screen structure:

- title header
- exam info grid
- instructions card
- attempts status card
- start button card
- attempt history list
- review panel when an attempt is selected

#### Exam Taking Screen

Use this screen structure:

- sticky timer header
- attempt number banner
- scrollable question cards
- each question card includes:
  - question number
  - question text
  - point value
  - answer controls
- bottom area:
  - answered count
  - `Submit Exam` button

#### Quiz Screen

Use this screen structure:

- top app bar with `My Quizzes`
- quiz list cards
- each card shows:
  - title
  - course
  - due date
  - time limit
  - status chip
  - score if completed
  - primary button:
    - `Start Quiz`
    - `Retake Quiz`
    - `View Results`

#### Quiz Detail Screen

Use this screen structure:

- title header
- quiz info grid
- instructions card
- attempts status card
- start button card
- attempt history list
- review panel when an attempt is selected

#### Quiz Taking Screen

Use this screen structure:

- top header with countdown timer
- attempt number banner
- scrollable question cards
- each question card includes:
  - question number
  - question text
  - point value
  - answer controls
- bottom centered `Submit Quiz` button

### Recommended Android Navigation

Use this flow:

1. `TasksScreen`
2. `AssignmentListScreen` or `ExamListScreen` or `QuizListScreen`
3. detail screen
4. action screen if needed
5. review screen after submit

If you prefer a cleaner app structure, you can keep it as:

- one `TasksFragment` with tabs
- one detail screen per task type
- one taking/submission screen per task type
- one review screen per task type

## Course List Construction

### What the web page shows

- page title: `My Courses`
- subtitle: `Courses you are currently enrolled in`
- card grid
- one card per enrolled course
- course cover image or gradient fallback
- semester badge
- school year badge
- course title
- teacher name
- course number
- completion percentage
- progress bar
- action buttons:
  - `Open Course`
  - `View Assignments`

### Data used

From `StudentCourseController@index`, each course card includes:

- `course_id`
- `course_name`
- `course_number`
- `semester`
- `school_year`
- `cover_image`
- `teacher_name`
- `enrollment_status`
- `progress`
- `assignments_total`
- `assignments_submitted`

### Android match

For Android, this should become:

- a `RecyclerView` or lazy list
- a course card item layout
- a progress bar
- two buttons per card

## Course Detail Construction

The course detail page is the main hub for a single course.

### Top Area

- large course hero header
- cover image or gradient background
- course title
- course number

### Tab Bar

The page is split into 4 main tabs plus 1 direct link:

- `Overview`
- `Tasks`
- `Resources`
- `Discussion`
- `Live Q&A`

### Android match

This should become:

- `TabLayout + ViewPager`
- or bottom navigation inside the course screen
- or a single screen with section cards and anchors

## Overview Tab

### Web behavior

- shows course overview text
- shows empty state if no overview exists

### Android match

- plain detail card
- title
- multiline description

## Tasks Tab

This is the most important tab for student workflow.

### Task UI Structure

The web UI uses a very clear stacked-card layout that is easy to mirror on Android:

- a section title: `Course Tasks`
- three grouped task cards:
  - `Assignments`
  - `Exams`
  - `Quizzes`
- each task card has:
  - a colored header strip
  - a count badge on the right
  - a short status summary under the title
  - a vertical list of tappable rows
- if there are no tasks, a centered empty state card is shown

### Task Sections

The Tasks tab contains 3 grouped lists:

- Assignments
- Exams
- Quizzes

### Assignments Section

Each assignment item shows:

- title
- due date
- submitted or not
- done, overdue, or view state

### Assignment Row UI

Each assignment row in the web UI contains:

- a circular icon at the left
  - blue for pending
  - green for completed
- a bold title
- a small due-date line
- a right-side status pill
  - `Done`
  - `Overdue`
  - `View`

### Assignment States

- `Done`: submission exists, row turns green-tinted
- `Overdue`: due date is past and no submission exists, row turns red-tinted
- `Pending`: no submission yet, row stays blue/neutral

### Assignment Android Match

- `CardView` or `Material3 Card`
- `ListItem` style row
- left leading icon
- trailing status chip
- tap action opens assignment detail screen

### Exams Section

Each exam item shows:

- title
- exam date
- duration
- completion status
- score if already submitted

### Exam Row UI

Each exam row includes:

- a circular icon at the left
  - purple for available exams
  - gray for future exams
  - green for completed exams
- the exam title
- exam date
- duration
- a right-side status pill
  - `Upcoming`
  - `Take Exam`
  - score badge such as `18/20`

### Exam States

- `Upcoming`: exam date is still in the future
- `Available`: exam date has passed and no submission exists
- `Completed`: an attempt exists and is submitted

### Exam Android Match

- `RecyclerView` item or Compose `LazyColumn` row
- show date and duration in smaller text
- use a chip for result or availability
- tap action opens exam screen

### Quizzes Section

Each quiz item shows:

- title
- number of questions
- time limit
- completion status
- score if already submitted

### Quiz Row UI

Each quiz row includes:

- a circular icon at the left
  - amber for available quizzes
  - green for completed quizzes
- the quiz title
- a subtitle with:
  - number of questions
  - time limit
- a right-side status pill
  - `Take Quiz`
  - score badge such as `9/10`

### Quiz States

- `Available`: no submitted attempt exists
- `Completed`: quiz attempt status is `submitted`

### Quiz Android Match

- list item with two-line text
- trailing action chip
- tap action opens quiz screen

### Android match

This should become:

- one `Tasks` screen
- or 3 nested tabs/cards:
- Assignments
- Exams
- Quizzes

### Tasks Summary Widget

The right sidebar on the web becomes a compact summary card on Android:

- `Assignments`: completed over total
- `Exams`: completed over total
- `Quizzes`: total count
- a `View All Tasks` action

## Resources Tab

### Web behavior

- lists course files
- each item shows:
  - title
  - file name
  - file size
  - open/download action

### Android match

- file list
- download/open button
- local cache for offline access if needed

## Discussion Tab

### Web behavior

- shows discussion threads
- each post has:
  - author
  - time
  - content
  - edit/delete if owned by user
  - subscribe/unsubscribe
  - replies
  - reply form

### Android match

- discussion feed
- reply composer
- thread expand/collapse
- menu actions for own posts

## Live Q&A

### Web behavior

- direct route to office hours:
  - `student.courses.office-hours.index`

### Android match

- dedicated `Office Hours` screen
- list of open Q&A sessions
- question input
- thread or message-style answers

## Right Sidebar Widgets

The web page also has a right-side summary panel.

### Tasks Overview Widget

Shows:

- assignments completed
- exams completed
- total quizzes
- button to view all tasks

### Upcoming Assignments Widget

Shows:

- up to 5 upcoming assignments
- due date
- completion status

### Android match

These can become:

- a summary card at the top
- or collapsible cards below the tabs
- or dashboard widgets inside the course screen

## Data Flow

### Course List

1. Load enrolled courses
2. Calculate assignment totals
3. Calculate submitted totals
4. Compute progress percentage
5. Render cards

### Course Detail

1. Verify student enrollment
2. Load resources
3. Load discussions
4. Load assignments
5. Load exams
6. Load quizzes
7. Render tabs and sidebar widgets

## Suggested Android Screen Layout

### Option 1

- `CoursesFragment`
- `CourseDetailFragment`
- `TasksFragment`
- `ResourcesFragment`
- `DiscussionFragment`
- `OfficeHoursFragment`

### Option 2

- one `CourseDetailActivity`
- tabs inside the screen
- nested lists in each tab

## Recommended API Endpoints To Build Later

To make Android mirror the web UI cleanly, expose JSON endpoints like:

- `GET /api/student/courses`
- `GET /api/student/courses/{id}`
- `GET /api/student/courses/{id}/assignments`
- `GET /api/student/courses/{id}/exams`
- `GET /api/student/courses/{id}/quizzes`
- `GET /api/student/courses/{id}/resources`
- `GET /api/student/courses/{id}/discussions`
- `GET /api/student/courses/{id}/office-hours`

## Bottom Line

The Student Course section is basically:

- course list card grid
- one course detail hub
- four main content areas:
  - overview
  - tasks
  - resources
  - discussion
- one direct support area:
  - live Q&A

That is the structure to copy into Android.

## Android Task Screen Recommendation

If you want the closest Android match to the web UI, build the task screen like this:

1. top title section with `Course Tasks`
2. stacked cards for `Assignments`, `Exams`, and `Quizzes`
3. each card uses a header, count badge, and list rows
4. a summary card below the tabs or pinned at the top
5. empty state card when no items exist

## Suggested Task Data Model

For Android, the task screen can be backed by three simple list models:

- `AssignmentTaskUiModel`
  - `id`
  - `title`
  - `dueDate`
  - `isDone`
  - `isOverdue`
  - `actionLabel`
- `ExamTaskUiModel`
  - `id`
  - `title`
  - `examDate`
  - `durationMinutes`
  - `isCompleted`
  - `scoreText`
- `QuizTaskUiModel`
  - `id`
  - `title`
  - `questionCount`
  - `timeLimitMinutes`
  - `isCompleted`
  - `scoreText`
