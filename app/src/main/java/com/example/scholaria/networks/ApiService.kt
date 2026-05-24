package com.example.scholaria.networks

import com.google.gson.annotations.SerializedName
import okhttp3.MultipartBody
import okhttp3.RequestBody
import retrofit2.Call
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.Multipart
import retrofit2.http.Part
import retrofit2.http.Path
import retrofit2.http.POST
import retrofit2.http.Query

interface ApiService {
    @GET("test")
    suspend fun testConnection(): Response<TestResponse>

    @POST("login")
    suspend fun login(@Body loginRequest: LoginRequest): Response<AuthEnvelope>

    @POST("login-test")
    suspend fun loginTest(@Body loginRequest: LoginRequest): Response<AuthEnvelope>

    @POST("logout")
    suspend fun logout(): Response<ApiMessageResponse>

    @POST("refresh")
    suspend fun refresh(): Response<AuthEnvelope>

    @GET("validate")
    suspend fun validateToken(): Response<ApiMessageResponse>

    @GET("me")
    suspend fun getCurrentUser(): Response<UserResponse>

    @GET("student/dashboard")
    suspend fun getStudentDashboard(): Response<StudentApiResponse<StudentDashboardDataDto>>

    @GET("student/dashboard")
    fun getStudentDashboardCall(): Call<StudentApiResponse<StudentDashboardDataDto>>

    @GET("student/courses")
    suspend fun getStudentCourses(): Response<StudentApiResponse<List<StudentCourseDto>>>

    @GET("student/courses")
    fun getStudentCoursesCall(): Call<StudentApiResponse<List<StudentCourseDto>>>

    @GET("student/courses/{course}")
    suspend fun getStudentCourse(
        @Path("course") courseId: Int
    ): Response<StudentApiResponse<StudentCourseDetailDto>>

    @GET("student/courses/{course}")
    fun getStudentCourseCall(
        @Path("course") courseId: Int
    ): Call<StudentApiResponse<StudentCourseDetailDto>>

    @GET("student/tasks")
    suspend fun getStudentTasks(
        @Query("course_id") courseId: Int? = null,
        @Query("tab") tab: String? = null
    ): Response<StudentApiResponse<StudentTaskHubDataDto>>

    @GET("student/tasks")
    fun getStudentTasksCall(
        @Query("course_id") courseId: Int? = null,
        @Query("tab") tab: String? = null
    ): Call<StudentApiResponse<StudentTaskHubDataDto>>

    @GET("student/assignments")
    suspend fun getStudentAssignments(): Response<StudentApiResponse<StudentAssignmentListDataDto>>

    @GET("student/assignments")
    fun getStudentAssignmentsCall(): Call<StudentApiResponse<StudentAssignmentListDataDto>>

    @GET("student/assignments/{assignment}")
    suspend fun getStudentAssignment(
        @Path("assignment") assignmentId: Int
    ): Response<StudentApiResponse<StudentAssignmentDetailDataDto>>

    @GET("student/assignments/{assignment}")
    fun getStudentAssignmentCall(
        @Path("assignment") assignmentId: Int
    ): Call<StudentApiResponse<StudentAssignmentDetailDataDto>>

    @GET("student/assignments/{assignment}/submit")
    suspend fun getAssignmentSubmissionContext(
        @Path("assignment") assignmentId: Int
    ): Response<StudentApiResponse<StudentSubmissionFormDto>>

    @GET("student/assignments/{assignment}/submit")
    fun getAssignmentSubmissionContextCall(
        @Path("assignment") assignmentId: Int
    ): Call<StudentApiResponse<StudentSubmissionFormDto>>

    @Multipart
    @POST("student/assignments/{assignment}/submit")
    suspend fun submitAssignment(
        @Path("assignment") assignmentId: Int,
        @Part("submission_type") submissionType: RequestBody,
        @Part("text_content") textContent: RequestBody? = null,
        @Part file: MultipartBody.Part? = null,
        @Part("link_content") linkContent: RequestBody? = null
    ): Response<StudentApiResponse<StudentSubmissionActionDataDto>>

    @Multipart
    @POST("student/assignments/{assignment}/submit")
    fun submitAssignmentCall(
        @Path("assignment") assignmentId: Int,
        @Part("submission_type") submissionType: RequestBody,
        @Part("text_content") textContent: RequestBody? = null,
        @Part file: MultipartBody.Part? = null,
        @Part("link_content") linkContent: RequestBody? = null
    ): Call<StudentApiResponse<StudentSubmissionActionDataDto>>

    @GET("student/exams")
    suspend fun getStudentExams(): Response<StudentApiResponse<StudentExamListDataDto>>

    @GET("student/exams")
    fun getStudentExamsCall(): Call<StudentApiResponse<StudentExamListDataDto>>

    @GET("student/exams/{exam}")
    suspend fun getStudentExam(
        @Path("exam") examId: Int
    ): Response<StudentApiResponse<StudentExamDetailDataDto>>

    @GET("student/exams/{exam}")
    fun getStudentExamCall(
        @Path("exam") examId: Int
    ): Call<StudentApiResponse<StudentExamDetailDataDto>>

    @POST("student/exams/{exam}/start")
    suspend fun startExam(
        @Path("exam") examId: Int
    ): Response<StudentApiResponse<StudentAttemptActionDataDto>>

    @POST("student/exams/{exam}/start")
    fun startExamCall(
        @Path("exam") examId: Int
    ): Call<StudentApiResponse<StudentAttemptActionDataDto>>

    @POST("student/exams/{exam}/submit")
    suspend fun submitExam(
        @Path("exam") examId: Int,
        @Body request: AttemptSubmitRequestDto
    ): Response<StudentApiResponse<StudentAttemptActionDataDto>>

    @POST("student/exams/{exam}/submit")
    fun submitExamCall(
        @Path("exam") examId: Int,
        @Body request: AttemptSubmitRequestDto
    ): Call<StudentApiResponse<StudentAttemptActionDataDto>>

    @GET("student/quizzes")
    suspend fun getStudentQuizzes(): Response<StudentApiResponse<StudentQuizListDataDto>>

    @GET("student/quizzes")
    fun getStudentQuizzesCall(): Call<StudentApiResponse<StudentQuizListDataDto>>

    @GET("student/quizzes/{quiz}")
    suspend fun getStudentQuiz(
        @Path("quiz") quizId: Int
    ): Response<StudentApiResponse<StudentQuizDetailDataDto>>

    @GET("student/quizzes/{quiz}")
    fun getStudentQuizCall(
        @Path("quiz") quizId: Int
    ): Call<StudentApiResponse<StudentQuizDetailDataDto>>

    @POST("student/quizzes/{quiz}/start")
    suspend fun startQuiz(
        @Path("quiz") quizId: Int
    ): Response<StudentApiResponse<StudentAttemptActionDataDto>>

    @POST("student/quizzes/{quiz}/start")
    fun startQuizCall(
        @Path("quiz") quizId: Int
    ): Call<StudentApiResponse<StudentAttemptActionDataDto>>

    @POST("student/quizzes/{quiz}/submit")
    suspend fun submitQuiz(
        @Path("quiz") quizId: Int,
        @Body request: AttemptSubmitRequestDto
    ): Response<StudentApiResponse<StudentAttemptActionDataDto>>

    @POST("student/quizzes/{quiz}/submit")
    fun submitQuizCall(
        @Path("quiz") quizId: Int,
        @Body request: AttemptSubmitRequestDto
    ): Call<StudentApiResponse<StudentAttemptActionDataDto>>
}

data class TestResponse(
    val status: String,
    val message: String,
    val timestamp: String
)

data class LoginRequest(val email: String, val password: String)

data class UserResponse(
    val success: Boolean = false,
    val message: String? = null,
    val user: User? = null,
    val data: UserData? = null
) {
    fun resolvedUser(): User? = user ?: data?.user
}

data class UserData(
    val user: User? = null
)

data class User(
    val id: Int,
    val name: String,
    val email: String,
    val roles: List<String> = emptyList()
)

data class StudentApiResponse<T>(
    val success: Boolean = false,
    val message: String? = null,
    val data: T? = null
)

data class StudentDashboardDataDto(
    val stats: StudentDashboardStatsDto = StudentDashboardStatsDto(),
    @SerializedName("myCourses")
    val myCourses: List<StudentCourseDto> = emptyList(),
    @SerializedName("upcomingAssignments")
    val upcomingAssignments: List<StudentUpcomingAssignmentDto> = emptyList(),
    @SerializedName("recentAnnouncements")
    val recentAnnouncements: List<StudentRecentAnnouncementDto> = emptyList(),
    @SerializedName("learningProgress")
    val learningProgress: List<StudentLearningProgressDto> = emptyList()
) {
    val courses: List<StudentCourseDto>
        get() = myCourses
}

data class StudentDashboardStatsDto(
    @SerializedName("total_courses")
    val totalCourses: Int = 0,
    @SerializedName("total_enrollments")
    val totalEnrollments: Int = 0,
    @SerializedName("total_students")
    val totalStudents: Int = 0,
    @SerializedName("total_teachers")
    val totalTeachers: Int = 0,
    @SerializedName("enrolled_courses")
    val enrolledCourses: Int = 0,
    @SerializedName("in_progress")
    val inProgress: Int = 0,
    val completed: Int = 0
)

data class StudentUpcomingAssignmentDto(
    @SerializedName("assignment_id")
    val assignmentId: Int = 0,
    @SerializedName("assignment_title")
    val assignmentTitle: String = "",
    @SerializedName("course_name")
    val courseName: String = "",
    @SerializedName("due_date")
    val dueDate: String? = null
)

data class StudentRecentAnnouncementDto(
    @SerializedName("course_name")
    val courseName: String = "",
    val title: String = "",
    @SerializedName("created_at")
    val createdAt: String? = null
)

data class StudentLearningProgressDto(
    @SerializedName("course_id")
    val courseId: Int = 0,
    @SerializedName("course_name")
    val courseName: String = "",
    val progress: Int = 0
)

data class StudentCourseDto(
    @SerializedName("course_id")
    val courseId: Int = 0,
    @SerializedName("course_name")
    val courseName: String = "",
    @SerializedName("course_number")
    val courseNumber: String = "",
    val semester: String? = null,
    @SerializedName("school_year")
    val schoolYear: String? = null,
    @SerializedName("cover_image")
    val coverImage: String? = null,
    @SerializedName("teacher_name")
    val teacherName: String? = null,
    @SerializedName("enrollment_status")
    val enrollmentStatus: String? = null,
    val progress: Int = 0,
    @SerializedName("assignments_total")
    val assignmentsTotal: Int = 0,
    @SerializedName("assignments_submitted")
    val assignmentsSubmitted: Int = 0
)

data class StudentCourseDetailDto(
    val course: StudentCourseDetailCourseDto? = null,
    val resources: List<StudentResourceDto> = emptyList(),
    val discussions: List<StudentDiscussionDto> = emptyList(),
    val assignments: List<StudentAssignmentDto> = emptyList(),
    @SerializedName("completedAssignments")
    val completedAssignments: Int = 0,
    val exams: List<StudentExamDto> = emptyList(),
    val quizzes: List<StudentQuizDto> = emptyList()
)

data class StudentCourseDetailCourseDto(
    val id: Int = 0,
    @SerializedName("course_number")
    val courseNumber: String = "",
    val title: String = "",
    val description: String? = null,
    val semester: String? = null,
    @SerializedName("school_year")
    val schoolYear: String? = null,
    @SerializedName("start_date")
    val startDate: String? = null,
    @SerializedName("end_date")
    val endDate: String? = null,
    @SerializedName("days_pattern")
    val daysPattern: String? = null,
    @SerializedName("start_time")
    val startTime: String? = null,
    @SerializedName("end_time")
    val endTime: String? = null,
    @SerializedName("teacher_id")
    val teacherId: Int? = null,
    @SerializedName("cover_image")
    val coverImage: String? = null,
    val overview: String? = null
)

data class StudentResourceDto(
    val id: Int = 0,
    @SerializedName("course_id")
    val courseId: Int = 0,
    @SerializedName("uploaded_by")
    val uploadedBy: Int = 0,
    val title: String = "",
    @SerializedName("file_path")
    val filePath: String? = null,
    @SerializedName("file_name")
    val fileName: String? = null,
    @SerializedName("mime_type")
    val mimeType: String? = null,
    @SerializedName("file_size")
    val fileSize: Long? = null,
    val uploader: StudentResourceUploaderDto? = null
)

data class StudentResourceUploaderDto(
    val id: Int = 0,
    val name: String = ""
)

data class StudentDiscussionDto(
    val id: Int = 0,
    @SerializedName("course_id")
    val courseId: Int = 0,
    @SerializedName("user_id")
    val userId: Int = 0,
    @SerializedName("parent_id")
    val parentId: Int? = null,
    val content: String = "",
    @SerializedName("created_at")
    val createdAt: String? = null,
    @SerializedName("updated_at")
    val updatedAt: String? = null,
    val user: StudentDiscussionUserDto? = null,
    val replies: List<StudentDiscussionDto> = emptyList()
)

data class StudentDiscussionUserDto(
    val id: Int = 0,
    val name: String = ""
)

data class StudentTaskHubDataDto(
    val assignments: List<StudentTaskAssignmentDto> = emptyList(),
    val exams: List<StudentTaskExamDto> = emptyList(),
    val quizzes: List<StudentTaskQuizDto> = emptyList(),
    val courses: List<StudentTaskCourseDto> = emptyList(),
    @SerializedName("activeTab")
    val activeTab: String = "assignments",
    val filters: StudentTaskFiltersDto? = null
)

data class StudentTaskFiltersDto(
    @SerializedName("course_id")
    val courseId: Int = 0
)

data class StudentTaskAssignmentDto(
    @SerializedName("assignment_id")
    val assignmentId: Int = 0,
    val title: String = "",
    @SerializedName("course_title")
    val courseTitle: String = "",
    @SerializedName("course_number")
    val courseNumber: String = "",
    @SerializedName("submission_id")
    val submissionId: Int? = null,
    @SerializedName("submitted_at")
    val submittedAt: String? = null,
    val score: Int? = null,
    val status: String? = null,
    @SerializedName("is_overdue")
    val isOverdue: Boolean = false
)

data class StudentTaskExamDto(
    val id: Int = 0,
    val title: String = "",
    @SerializedName("course_title")
    val courseTitle: String = "",
    @SerializedName("course_number")
    val courseNumber: String = "",
    @SerializedName("attempt_id")
    val attemptId: Int? = null,
    @SerializedName("submitted_at")
    val submittedAt: String? = null,
    val score: Int? = null,
    val status: String? = null,
    @SerializedName("is_overdue")
    val isOverdue: Boolean = false
)

data class StudentTaskQuizDto(
    val id: Int = 0,
    val title: String = "",
    @SerializedName("course_title")
    val courseTitle: String = "",
    @SerializedName("course_number")
    val courseNumber: String = "",
    @SerializedName("attempt_id")
    val attemptId: Int? = null,
    @SerializedName("submitted_at")
    val submittedAt: String? = null,
    val score: Int? = null,
    val status: String? = null,
    @SerializedName("is_overdue")
    val isOverdue: Boolean = false
)

data class StudentTaskCourseDto(
    val id: Int = 0,
    val title: String = "",
    @SerializedName("course_number")
    val courseNumber: String = ""
)

data class StudentAssignmentListDataDto(
    val assignments: List<StudentAssignmentDto> = emptyList()
)

data class StudentAssignmentDto(
    @SerializedName("assignment_id")
    val assignmentId: Int = 0,
    val title: String = "",
    @SerializedName("course_id")
    val courseId: Int? = null,
    @SerializedName("course_name")
    val courseName: String? = null,
    @SerializedName("due_date")
    val dueDate: String? = null,
    @SerializedName("submission_id")
    val submissionId: Int? = null,
    @SerializedName("submitted_at")
    val submittedAt: String? = null,
    val score: Int? = null
)

data class StudentAssignmentDetailDataDto(
    val assignment: StudentAssignmentDetailDtoAssignment? = null,
    val course: StudentAssignmentDetailCourse? = null,
    val submission: StudentSubmissionDto? = null
)

data class StudentAssignmentDetailDtoAssignment(
    val id: Int = 0,
    @SerializedName("course_id")
    val courseId: Int = 0,
    val title: String = "",
    val description: String? = null,
    @SerializedName("due_date")
    val dueDate: String? = null,
    @SerializedName("max_score")
    val maxScore: Int? = null,
    val type: String = "assignment",
    @SerializedName("type_label")
    val typeLabel: String = "Assignment"
)

data class StudentAssignmentDetailCourse(
    val id: Int = 0,
    val title: String = "",
    @SerializedName("course_number")
    val courseNumber: String = ""
)

data class StudentSubmissionFormDto(
    val assignment: StudentSubmissionFormAssignment? = null,
    val submission: StudentSubmissionDto? = null
)

data class StudentSubmissionFormAssignment(
    val id: Int = 0,
    @SerializedName("course_id")
    val courseId: Int = 0,
    val title: String = "",
    val description: String? = null,
    @SerializedName("due_date")
    val dueDate: String? = null,
    @SerializedName("max_score")
    val maxScore: Int? = null,
    val type: String = "assignment"
)

data class StudentSubmissionActionDataDto(
    val submission: StudentSubmissionDto? = null
)

data class StudentSubmissionDto(
    val id: Int = 0,
    @SerializedName("assignment_id")
    val assignmentId: Int = 0,
    @SerializedName("student_id")
    val studentId: Int = 0,
    @SerializedName("submission_type")
    val submissionType: String = "",
    val content: String? = null,
    @SerializedName("file_path")
    val filePath: String? = null,
    @SerializedName("file_url")
    val fileUrl: String? = null,
    @SerializedName("submitted_at")
    val submittedAt: String? = null,
    val score: Int? = null,
    val feedback: String? = null
)

data class StudentExamListDataDto(
    val exams: List<StudentExamDto> = emptyList(),
    val meta: StudentPaginationMetaDto? = null
)

data class StudentPaginationMetaDto(
    @SerializedName("current_page")
    val currentPage: Int = 0,
    @SerializedName("last_page")
    val lastPage: Int = 0,
    @SerializedName("per_page")
    val perPage: Int = 0,
    val total: Int = 0
)

data class StudentExamDetailDataDto(
    val state: String = "available",
    val exam: StudentExamDto? = null,
    val attempts: List<StudentAttemptDto> = emptyList(),
    @SerializedName("selected_attempt")
    val selectedAttempt: StudentAttemptDetailDto? = null,
    val questions: List<StudentExamQuestionDto> = emptyList()
)

data class StudentExamDto(
    val id: Int = 0,
    @SerializedName("course_id")
    val courseId: Int = 0,
    @SerializedName("exam_type")
    val examType: String = "",
    val title: String = "",
    val description: String? = null,
    @SerializedName("exam_date")
    val examDate: String? = null,
    @SerializedName("due_date")
    val dueDate: String? = null,
    val duration: Int? = null,
    @SerializedName("attempts_allowed")
    val attemptsAllowed: Int? = null,
    @SerializedName("max_score")
    val maxScore: Int? = null,
    val location: String? = null,
    val instructions: String? = null,
    @SerializedName("feedback_type")
    val feedbackType: String? = null,
    @SerializedName("results_released")
    val resultsReleased: Boolean = false,
    @SerializedName("show_results")
    val showResults: Boolean = false,
    @SerializedName("shuffle_questions")
    val shuffleQuestions: Boolean = false,
    @SerializedName("random_subset_count")
    val randomSubsetCount: Int? = null,
    @SerializedName("is_published")
    val isPublished: Boolean = false,
    val course: StudentCourseRefDto? = null,
    val attempt: StudentAttemptDto? = null
)

data class StudentExamQuestionDto(
    val id: Int = 0,
    @SerializedName("exam_id")
    val examId: Int = 0,
    @SerializedName("question_text")
    val questionText: String = "",
    @SerializedName("question_type")
    val questionType: String = "",
    val options: List<String> = emptyList(),
    @SerializedName("correct_answer")
    val correctAnswer: String? = null,
    val explanation: String? = null,
    val points: Int = 0,
    val order: Int = 0
)

data class StudentAttemptDto(
    val id: Int = 0,
    @SerializedName("exam_id")
    val examId: Int? = null,
    @SerializedName("quiz_id")
    val quizId: Int? = null,
    @SerializedName("student_id")
    val studentId: Int? = null,
    @SerializedName("attempt_number")
    val attemptNumber: Int? = null,
    @SerializedName("started_at")
    val startedAt: String? = null,
    @SerializedName("submitted_at")
    val submittedAt: String? = null,
    val score: Int? = null,
    @SerializedName("max_score")
    val maxScore: Int? = null,
    val status: String? = null,
    @SerializedName("question_ids")
    val questionIds: List<Int> = emptyList()
)

data class StudentAttemptDetailDto(
    val attempt: StudentAttemptDto = StudentAttemptDto(),
    val answers: List<StudentAttemptAnswerDto> = emptyList()
)

data class StudentAttemptAnswerDto(
    val id: Int = 0,
    @SerializedName("attempt_id")
    val attemptId: Int = 0,
    @SerializedName("question_id")
    val questionId: Int = 0,
    val answer: String? = null,
    val score: Int? = null,
    val feedback: String? = null,
    val question: StudentExamQuestionDto? = null,
    @SerializedName("is_correct")
    val isCorrect: Boolean? = null,
    @SerializedName("points_earned")
    val pointsEarned: Int? = null
)

data class StudentExamStartDataDto(
    val attempt: StudentAttemptDto? = null,
    val questions: List<StudentExamQuestionDto> = emptyList()
)

data class StudentAttemptActionDataDto(
    val attempt: StudentAttemptDto? = null,
    @SerializedName("total_score")
    val totalScore: Int? = null
)

data class StudentCourseRefDto(
    val id: Int = 0,
    val title: String = "",
    @SerializedName("course_number")
    val courseNumber: String? = null
)

data class StudentQuizListDataDto(
    val quizzes: List<StudentQuizDto> = emptyList(),
    val meta: StudentPaginationMetaDto? = null
)

data class StudentQuizDetailDataDto(
    val state: String = "available",
    val quiz: StudentQuizDto? = null,
    val attempts: List<StudentAttemptDto> = emptyList(),
    @SerializedName("selected_attempt")
    val selectedAttempt: StudentAttemptDetailDto? = null,
    val questions: List<StudentQuizQuestionDto> = emptyList()
)

data class StudentQuizDto(
    val id: Int = 0,
    @SerializedName("course_id")
    val courseId: Int = 0,
    val title: String = "",
    val description: String? = null,
    @SerializedName("start_date")
    val startDate: String? = null,
    @SerializedName("due_date")
    val dueDate: String? = null,
    @SerializedName("max_score")
    val maxScore: Int? = null,
    @SerializedName("time_limit")
    val timeLimit: Int? = null,
    @SerializedName("attempts_allowed")
    val attemptsAllowed: Int? = null,
    @SerializedName("shuffle_questions")
    val shuffleQuestions: Boolean = false,
    @SerializedName("random_subset_count")
    val randomSubsetCount: Int? = null,
    @SerializedName("show_results")
    val showResults: Boolean = false,
    @SerializedName("feedback_type")
    val feedbackType: String? = null,
    @SerializedName("results_released")
    val resultsReleased: Boolean = false,
    @SerializedName("is_published")
    val isPublished: Boolean = false,
    val points: Int? = null,
    val duration: Int? = null,
    val course: StudentCourseRefDto? = null,
    val attempt: StudentAttemptDto? = null
)

data class StudentQuizQuestionDto(
    val id: Int = 0,
    @SerializedName("quiz_id")
    val quizId: Int = 0,
    @SerializedName("question_text")
    val questionText: String = "",
    @SerializedName("question_type")
    val questionType: String = "",
    val options: List<String> = emptyList(),
    @SerializedName("correct_answer")
    val correctAnswer: String? = null,
    val explanation: String? = null,
    val points: Int = 0,
    val order: Int = 0
)

data class StudentQuizStartDataDto(
    val attempt: StudentAttemptDto? = null,
    val questions: List<StudentQuizQuestionDto> = emptyList()
)

data class AttemptSubmitRequestDto(
    @SerializedName("answers")
    val answers: Map<String, String?> = emptyMap()
)
