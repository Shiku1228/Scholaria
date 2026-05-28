import { apiRequest } from './client';

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
  });
}

export function startStudentExam(token, examId) {
  return apiRequest(`/student/exams/${examId}/start`, {
    method: 'POST',
    token,
  });
}

export function submitStudentExam(token, examId, payload) {
  return apiRequest(`/student/exams/${examId}/submit`, {
    method: 'POST',
    token,
    body: payload,
  });
}

export function getStudentQuiz(token, quizId) {
  return apiRequest(`/student/quizzes/${quizId}`, {
    method: 'GET',
    token,
  });
}

export function startStudentQuiz(token, quizId) {
  return apiRequest(`/student/quizzes/${quizId}/start`, {
    method: 'POST',
    token,
  });
}

export function submitStudentQuiz(token, quizId, payload) {
  return apiRequest(`/student/quizzes/${quizId}/submit`, {
    method: 'POST',
    token,
    body: payload,
  });
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
