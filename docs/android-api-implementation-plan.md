# Android API Implementation Plan

## Goal

Connect the Laravel backend in this repository to the Android Studio app in a clean, maintainable way.

This plan focuses on:

- authenticating Android users with Laravel JWT
- loading server data into Android models
- storing offline copies in Room
- syncing Room with Laravel API endpoints later

## Current Backend Entry Points

### Authentication

- `POST /api/login`
- `POST /api/logout`
- `POST /api/refresh`
- `GET /api/me`
- `GET /api/validate`
- `GET /api/test`

Backend files:

- `routes/api.php`
- `app/Http/Controllers/JwtAuthController.php`
- `app/Http/Middleware/JwtMiddleware.php`
- `app/Http/Middleware/ApiMiddleware.php`

### Important Notes

- Use `http://192.168.1.117:8001/api/` as the base URL for a real phone on the same Wi-Fi network.
- Use `http://10.0.2.2:8001/api/` for the Android emulator.
- The `Authorization` header must be sent as `Bearer <token>`.

## Android Side Structure

### Network Layer

Use Retrofit + OkHttp:

- `ApiClient`
- `ApiService`
- DTO/request classes
- token interceptor

### Local Database

Use Room for offline storage:

- entity classes
- DAO interfaces
- Room database class
- repository layer

### App Layers

- UI screens
- ViewModel
- Repository
- Room cache
- Retrofit API service

## Suggested First Sync Scope

Start with the smallest useful set of data:

- user profile
- courses
- enrollments
- announcements
- assignments
- submissions
- grades

This gives the Android app enough data to feel “database-backed” without making the first sync too large.

## Recommended Backend Models to Expose

These backend models are the best candidates for Android sync first:

- `User`
- `Student`
- `Teacher`
- `Course`
- `Enrollment`
- `Announcement`
- `Assignment`
- `Submission`
- `Grade`

Later sync candidates:

- `Quiz`
- `QuizQuestion`
- `QuizAttempt`
- `Exam`
- `ExamQuestion`
- `ExamAnswer`
- `OfficeHour`
- `ChatGroup`
- `ChatConversation`
- `ChatMessage`

## Android Room Tables

Suggested Room tables:

- `users`
- `students`
- `teachers`
- `courses`
- `enrollments`
- `announcements`
- `assignments`
- `submissions`
- `grades`

Optional later tables:

- `quizzes`
- `quiz_questions`
- `quiz_attempts`
- `exams`
- `exam_questions`
- `exam_answers`
- `office_hours`
- `chat_groups`
- `chat_conversations`
- `chat_messages`

## Data Flow

### Login Flow

1. Android sends email and password to `POST /api/login`.
2. Laravel returns a JWT token and user profile.
3. Android saves the token in secure storage.
4. Android calls protected endpoints with `Authorization: Bearer <token>`.
5. Android stores returned data in Room for offline access.

### Fetch Flow

1. Android reads cached data from Room immediately.
2. Android refreshes the same data from Laravel in the background.
3. New API data overwrites old cached records in Room.
4. UI observes Room and updates automatically.

### Sync Flow

1. Pull latest server data.
2. Save it locally in Room.
3. If the app supports edits offline, queue local changes.
4. Push queued changes back to Laravel when connectivity returns.

## API Contract Recommendation

For each backend feature, create a JSON API endpoint that returns:

- `success`
- `message`
- `data`

Example:

```json
{
  "success": true,
  "message": "Courses loaded successfully",
  "data": []
}
```

## Suggested Endpoint Groups To Add Next

### User and Auth

- `GET /api/me`
- `GET /api/profile`
- `POST /api/login`
- `POST /api/refresh`

### Courses

- `GET /api/courses`
- `GET /api/courses/{id}`

### Enrollments

- `GET /api/enrollments`
- `GET /api/enrollments/{id}`

### Announcements

- `GET /api/announcements`
- `GET /api/courses/{id}/announcements`

### Messages

- `GET /api/student/messages`
- `GET /api/student/courses/{id}/messages`
- `GET /api/student/courses/{id}/messages/conversations`
- `GET /api/student/conversations/{id}/messages`
- `POST /api/student/conversations/{id}/messages`

### Assignments

- `GET /api/assignments`
- `GET /api/courses/{id}/assignments`

### Grades

- `GET /api/grades`

### Submissions

- `GET /api/submissions`
- `POST /api/assignments/{id}/submit`

## Android Model Mapping

Each Laravel model should be mapped to:

- one Kotlin data class for network responses
- one Room entity when offline storage is needed
- one repository method for reading and syncing

Example mapping:

- Laravel `Course` model
- Kotlin `CourseDto`
- Room `CourseEntity`
- `CourseRepository`

## Security Rules

- Do not store the JWT token in plain text.
- Always send the token in the `Authorization` header.
- Clear the token on logout.
- Reject protected calls if token is missing or expired.

## Minimal Implementation Order

1. Confirm login works from Android.
2. Add `GET /api/me` for user profile loading.
3. Add course endpoints.
4. Add Room tables for user and courses.
5. Add enrollments and announcements.
6. Add assignments and grades.
7. Add offline sync logic.

## What To Share Next

When updating this file later, include:

- the backend routes you want exposed
- the Android screen that needs the data
- the Room table you want to cache
- whether the data must work offline
- whether the data is read-only or editable

## Current Status

- JWT login is working
- Android can reach the Laravel backend over LAN
- API client is already set up in Android
- next step is exposing more Laravel data as JSON endpoints
- messaging endpoints are now available for Android API use
