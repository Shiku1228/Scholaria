# Android UI Screen Map

This file lists the current Student and Teacher UI surfaces in the Laravel app so the Android version can match the same structure.

## Student UI

### Main Screens

- Dashboard
- Calendar
- Next Up
- Courses list
- Course detail
- Tasks overview
- Assignments list
- Assignment detail
- Announcements list
- Grades list
- Exams list
- Exam detail
- Quizzes list
- Quiz detail
- Office Hours list

### Student Flow Screens

- Assignment submission form
- Exam taking screen
- Exam upcoming screen
- Exam missed screen
- Exam result screen
- Quiz taking screen
- Submission create screen

### Student Feature Areas

- Course discussions
- Course office hour questions
- Assignment submissions
- Exam start and submit
- Quiz start and submit
- Notification center

## Teacher UI

### Main Screens

- Dashboard
- Calendar
- Tasks overview
- Assignments overview
- Courses list
- Course detail
- Students list
- Enrollments list
- Exams list
- Quizzes list
- Question banks list
- Announcements overview
- Office Hours list
- Messages
- Settings

### Teacher CRUD Screens

- Assignment create
- Assignment edit
- Assignment show
- Announcement create
- Announcement edit
- Announcement show
- Exam create
- Exam edit
- Exam show
- Exam questions manager
- Quiz create
- Quiz edit
- Quiz show
- Quiz questions manager
- Question bank create
- Question bank edit
- Question bank show

### Teacher Action Screens

- Publish exam
- Unpublish exam
- Release exam results
- Import exam questions from bank
- Publish quiz
- Unpublish quiz
- Release quiz results
- Import quiz questions from bank
- Add/remove quiz questions
- Add/remove exam questions
- Add/update/delete course discussions
- Upload course resource
- Update course overview
- Update course cover
- Manage enrollments
- Unenroll student
- Re-enroll student
- Resolve notifications
- Answer office hour questions

## Android Matching Suggestion

You do not need one Android screen for every Blade file.

Recommended Android grouping:

- Auth screens
- Student home
- Teacher home
- Courses
- Tasks
- Assignments
- Exams
- Quizzes
- Announcements
- Grades
- Office hours
- Messages
- Settings
- Notifications

## Notes

- Some Blade files are top-level pages.
- Some are detail pages or action pages.
- Some are flow screens that appear only during submission, taking exams, or editing content.
- For Android, these can be separate activities, fragments, or composable screens depending on your architecture.

