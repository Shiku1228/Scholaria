export const STUDENT_ACCESS_DENIED_MESSAGE =
  'This app is for student accounts only. Please sign in with a student account or use the Scholaria website for other roles.';

function normalizeRole(value) {
  if (value == null) return '';
  if (typeof value === 'string') return value.trim().toLowerCase();
  if (typeof value === 'object') {
    return String(value.name || value.slug || value.role || value.type || '').trim().toLowerCase();
  }
  return String(value).trim().toLowerCase();
}

export function getUserRoles(user) {
  if (!user) return [];

  const roles = [];

  if (Array.isArray(user.roles)) {
    roles.push(...user.roles.map(normalizeRole).filter(Boolean));
  }

  const singles = [user.role, user.user_type, user.userType, user.type, user.account_type];
  singles.forEach((value) => {
    const normalized = normalizeRole(value);
    if (normalized) roles.push(normalized);
  });

  return [...new Set(roles)];
}

export function isStudentUser(user) {
  const roles = getUserRoles(user);
  return roles.includes('student');
}

export function assertStudentUser(user) {
  if (!isStudentUser(user)) {
    throw new Error(STUDENT_ACCESS_DENIED_MESSAGE);
  }
}
