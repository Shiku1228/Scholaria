import { apiRequest } from './client';

const STUDENT_ANSWER_REVEAL_FIELDS = new Set([
  'answer_key',
  'answer_keys',
  'correct_answer',
  'correct_answers',
  'correct_choice',
  'correct_choice_id',
  'correct_choices',
  'correct_option',
  'correct_option_id',
  'correct_options',
  'correctchoice',
  'is_correct',
  'iscorrect',
  'solution',
  'teacher_answer',
]);

function sanitizeStudentAssessmentResponse(payload) {
  if (!payload || typeof payload !== 'object') {
    return payload;
  }

  const next = { ...payload };

  if (Array.isArray(next.questions)) {
    next.questions = next.questions.map(sanitizeStudentQuestion);
  }

  if (next.selected_attempt) {
    next.selected_attempt = sanitizeStudentAttempt(next.selected_attempt);
  }

  if (Array.isArray(next.attempts)) {
    next.attempts = next.attempts.map(sanitizeStudentAttempt);
  }

  if (next.attempt) {
    next.attempt = sanitizeStudentAttempt(next.attempt);
  }

  return next;
}

function sanitizeStudentAttempt(attempt) {
  if (!attempt || typeof attempt !== 'object') {
    return attempt;
  }

  const next = { ...attempt };
  if (Array.isArray(next.answers)) {
    next.answers = next.answers.map((answer) => {
      if (!answer || typeof answer !== 'object') {
        return answer;
      }

      return {
        ...answer,
        question: sanitizeStudentQuestion(answer.question),
      };
    });
  }

  return next;
}

function sanitizeStudentQuestion(question) {
  if (!question || typeof question !== 'object') {
    return question;
  }

  const next = Object.fromEntries(
    Object.entries(question).filter(([key]) => !STUDENT_ANSWER_REVEAL_FIELDS.has(String(key).toLowerCase()))
  );

  if (Array.isArray(next.choices)) {
    next.choices = next.choices.map(sanitizeStudentChoice);
  }

  if (Array.isArray(next.options)) {
    next.options = next.options.map(sanitizeStudentChoice);
  }

  if (next.question_options && typeof next.question_options === 'object') {
    next.question_options = sanitizeStudentOptionsContainer(next.question_options);
  }

  return next;
}

function sanitizeStudentChoice(choice) {
  if (!choice || typeof choice !== 'object') {
    return choice;
  }

  return Object.fromEntries(
    Object.entries(choice).filter(([key]) => !STUDENT_ANSWER_REVEAL_FIELDS.has(String(key).toLowerCase()))
  );
}

function sanitizeStudentOptionsContainer(options) {
  if (Array.isArray(options)) {
    return options.map(sanitizeStudentChoice);
  }

  if (!options || typeof options !== 'object') {
    return options;
  }

  return Object.fromEntries(
    Object.entries(options).map(([key, value]) => [key, sanitizeStudentChoice(value)])
  );
}

export function getStudentDashboard(token) {
  return apiRequest('/student/dashboard', {
    method: 'GET',
    token,
  });
}

export function getStudentCourses(token) {
  return apiRequest('/student/courses', {
    method: 'GET',
    token,
  });
}

export function getStudentCourse(token, courseId) {
  return apiRequest(`/student/courses/${courseId}`, {
    method: 'GET',
    token,
  });
}

export function getStudentTasks(token, filters = {}) {
  const params = new URLSearchParams();

  if (filters.tab) {
    params.set('tab', filters.tab);
  }

  if (filters.course_id) {
    params.set('course_id', String(filters.course_id));
  }

  const suffix = params.toString() ? `?${params.toString()}` : '';

  return apiRequest(`/student/tasks${suffix}`, {
    method: 'GET',
    token,
  });
}

export function getStudentAssignments(token, courseId = 0) {
  const suffix = courseId > 0 ? `?course_id=${courseId}` : '';

  return apiRequest(`/student/assignments${suffix}`, {
    method: 'GET',
    token,
  });
}

export function getStudentAnnouncements(token, courseId = 0) {
  const suffix = courseId > 0 ? `?course_id=${courseId}` : '';

  return apiRequest(`/student/announcements${suffix}`, {
    method: 'GET',
    token,
  });
}

export function getStudentGrades(token) {
  return apiRequest('/student/grades', {
    method: 'GET',
    token,
  });
}

export function getStudentNotifications(token) {
  return apiRequest('/student/notifications', {
    method: 'GET',
    token,
  });
}

export function markAllStudentNotificationsRead(token) {
  return apiRequest('/student/notifications/read-all', {
    method: 'POST',
    token,
  });
}

export function getStudentAssignment(token, assignmentId) {
  return apiRequest(`/student/assignments/${assignmentId}`, {
    method: 'GET',
    token,
  });
}

export function getStudentAssignmentReview(token, assignmentId) {
  return apiRequest(`/student/assignments/${assignmentId}/submission`, {
    method: 'GET',
    token,
  });
}

export function getStudentAssignmentSubmission(token, assignmentId) {
  return apiRequest(`/student/assignments/${assignmentId}/submit`, {
    method: 'GET',
    token,
  });
}

export function submitStudentAssignment(token, assignmentId, payload) {
  return apiRequest(`/student/assignments/${assignmentId}/submit`, {
    method: 'POST',
    token,
    body: payload,
  });
}

export function getStudentExam(token, examId) {
  return apiRequest(`/student/exams/${examId}`, {
    method: 'GET',
    token,
  }).then((response) => ({
    ...response,
    data: sanitizeStudentAssessmentResponse(response?.data),
  }));
}

export function startStudentExam(token, examId) {
  return apiRequest(`/student/exams/${examId}/start`, {
    method: 'POST',
    token,
  }).then((response) => ({
    ...response,
    data: sanitizeStudentAssessmentResponse(response?.data),
  }));
}

export function submitStudentExam(token, examId, payload) {
  return apiRequest(`/student/exams/${examId}/submit`, {
    method: 'POST',
    token,
    body: payload,
  }).then((response) => ({
    ...response,
    data: sanitizeStudentAssessmentResponse(response?.data),
  }));
}

export function getStudentQuiz(token, quizId) {
  return apiRequest(`/student/quizzes/${quizId}`, {
    method: 'GET',
    token,
  }).then((response) => ({
    ...response,
    data: sanitizeStudentAssessmentResponse(response?.data),
  }));
}

export function startStudentQuiz(token, quizId) {
  return apiRequest(`/student/quizzes/${quizId}/start`, {
    method: 'POST',
    token,
  }).then((response) => ({
    ...response,
    data: sanitizeStudentAssessmentResponse(response?.data),
  }));
}

export function submitStudentQuiz(token, quizId, payload) {
  return apiRequest(`/student/quizzes/${quizId}/submit`, {
    method: 'POST',
    token,
    body: payload,
  }).then((response) => ({
    ...response,
    data: sanitizeStudentAssessmentResponse(response?.data),
  }));
}

export function getStudentMessages(token) {
  return apiRequest('/student/messages', {
    method: 'GET',
    token,
  });
}

export function getStudentCourseMessages(token, courseId) {
  return apiRequest(`/student/courses/${courseId}/messages`, {
    method: 'GET',
    token,
  });
}

export function getStudentCourseConversations(token, courseId) {
  return apiRequest(`/student/courses/${courseId}/messages/conversations`, {
    method: 'GET',
    token,
  });
}

export function getStudentConversationMessages(token, conversationId) {
  return apiRequest(`/student/conversations/${conversationId}/messages`, {
    method: 'GET',
    token,
  });
}

export function sendStudentConversationMessage(token, conversationId, message) {
  return apiRequest(`/student/conversations/${conversationId}/messages`, {
    method: 'POST',
    token,
    body: { message },
  });
}

export function sendStudentConversationMessageWithAttachment(token, conversationId, payload) {
  return apiRequest(`/student/conversations/${conversationId}/messages`, {
    method: 'POST',
    token,
    body: payload,
  });
}
