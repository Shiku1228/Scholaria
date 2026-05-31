# Student Web and Mobile Parity Map

This is the canonical student flow for both the web app and the mobile app.

Use this as the source of truth so the Android side does not drift or miss a feature.

## Canonical Flow

1. Login
2. Dashboard
3. Courses
4. Course Detail
5. Tasks
6. Assignments
7. Exams
8. Quizzes
9. Announcements
10. Grades
11. Messages
12. Notifications
13. Calendar
14. Next Up
15. Office Hours

## Web To Mobile Mapping

### Authentication

- Web route: `login`
- Mobile screen: `login`
- API: `POST /api/login`, `GET /api/me`, `GET /api/profile`

### Dashboard

- Web route: `student.dashboard`
- Mobile screen: `home`
- API: `GET /api/student/dashboard`
- Purpose: show summary cards, enrolled courses, upcoming assignments, recent announcements, and progress.

### Courses

- Web routes: `student.courses.index`, `student.courses.show`
- Mobile screens: `courses`, `course_detail`
- API: `GET /api/student/courses`, `GET /api/student/courses/{course}`
- Course detail subflows:
  - overview
  - tasks
  - resources
  - discussion
  - live Q&A

### Tasks

- Web route: `student.tasks.index`
- Mobile screen: `tasks`
- API: `GET /api/student/tasks`
- Task tabs:
  - assignments
  - exams
  - quizzes

### Assignments

- Web routes: `student.assignments.index`, `student.assignments.show`, `student.assignments.submit`
- Mobile screens: `assignments`, `assignment_detail`, `assignment_submit`
- API: `GET /api/student/assignments`, `GET /api/student/assignments/{assignment}`, `GET /api/student/assignments/{assignment}/submit`, `POST /api/student/assignments/{assignment}/submit`

### Exams

- Web routes: `student.exams.index`, `student.exams.show`, `student.exams.start`, `student.exams.submit`
- Mobile screens: `exams`, `exam_detail`, `exam_taking`
- API: `GET /api/student/exams`, `GET /api/student/exams/{exam}`, `POST /api/student/exams/{exam}/start`, `POST /api/student/exams/{exam}/submit`

### Quizzes

- Web routes: `student.quizzes.index`, `student.quizzes.show`, `student.quizzes.start`, `student.quizzes.submit`
- Mobile screens: `quizzes`, `quiz_detail`, `quiz_taking`
- API: `GET /api/student/quizzes`, `GET /api/student/quizzes/{quiz}`, `POST /api/student/quizzes/{quiz}/start`, `POST /api/student/quizzes/{quiz}/submit`

### Announcements

- Web route: `student.announcements.index`
- Mobile screen: `announcements`
- API: `GET /api/student/announcements`

### Grades

- Web route: `student.grades.index`
- Mobile screen: `grades`
- API: `GET /api/student/grades`

### Messages

- Web route: `messages.index`
- Mobile screens: `messages`, `conversation_list`, `conversation_detail`
- API: `GET /api/student/messages`
- Course message API:
  - `GET /api/student/courses/{course}/messages`
  - `GET /api/student/courses/{course}/messages/conversations`
  - `GET /api/student/courses/{course}/messages/conversations/{conversation}`
  - `GET /api/student/conversations/{conversation}/messages`
  - `POST /api/student/conversations/{conversation}/messages`

### Notifications

- Web routes: `student.notifications.read-all`, `student.notifications.open`
- Mobile screen: `notifications`
- API: `GET /api/student/notifications`, `POST /api/student/notifications/read-all`

### Calendar

- Web route: `student.calendar`
- Mobile screen: `calendar`
- API: use the same student dashboard/task/course data until a dedicated calendar endpoint is added.

### Next Up

- Web route: `student.next-up`
- Mobile screen: `next_up`
- API: use the dashboard payload and tasks payload for now.

### Office Hours

- Web route: `student.courses.office-hours.index`
- Mobile screen: `office_hours`
- API: `GET /api/student/courses/{course}/office-hours`

## Shared Rule

If a web student feature exists, the mobile app should ask first:

1. What screen on the web does this belong to?
2. What is the equivalent mobile screen?
3. What API response does the mobile screen need?
4. Does the feature need offline storage in Room?

## Recommended Mobile Order

Use this order in the app shell so the flow feels the same as the web:

1. Dashboard
2. Courses
3. Tasks
4. Announcements
5. Grades
6. Messages
7. Notifications
8. Calendar
9. Next Up

## Notes

- Course detail should remain the center of the student experience.
- Tasks should keep the same three-tab model: assignments, exams, quizzes.
- Exam and quiz screens should preserve the same start, take, submit, and review flow.
- Discussion and office hours should stay attached to the course detail screen.

## Internal Feature Parity

This is the part that usually gets forgotten, so keep it visible when building mobile.

| Feature | Web supports it? | Mobile API supports it now? | Internal actions to keep aligned |
|---|---:|---:|---|
| Assignments | Yes | Yes | view detail, submit text/file/link, see submission, see feedback |
| Exams | Yes | Yes | view detail, start attempt, answer questions, submit attempt, view results |
| Quizzes | Yes | Yes | view detail, start attempt, answer questions, submit attempt, view results |
| Course discussions | Yes | Partial | post comment, reply, edit own comment, delete own comment, subscribe/unsubscribe |
| Office hours | Yes | Partial | list sessions, ask question, view answers |
| Announcements | Yes | Yes | list announcements, open item, mark as read |
| Grades | Yes | Yes | list graded items, open feedback if available |
| Messages | Yes | Yes | list conversations, open thread, send message, open attachment |
| Notifications | Yes | Yes | list notifications, mark all as read, open linked item |
| Course resources | Yes | Partial | list resources, open/download resource |

## What This Means

- If the web page can do an action, the mobile app should either already support it or show it in the flow map as `missing`.
- Right now, the biggest gaps are `course discussions`, `office hours`, and `resource download/open` parity on mobile.
- The safest build order is:
  1. keep the list/detail screens uniform
  2. keep all submit/start/answer actions aligned
  3. add the missing course-discussion and office-hours APIs
  4. then add resource download/open parity
