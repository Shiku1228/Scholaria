import { clearSession, loadSession, saveSession } from '@/utils/session';
import { assertStudentUser, isStudentUser, STUDENT_ACCESS_DENIED_MESSAGE } from '@/utils/userRole';
import { apiRequest } from './client';
import { getStudentDashboard } from './student';

async function resolveUserForAccess(token, responseUser) {
  if (isStudentUser(responseUser)) {
    return responseUser;
  }

  if (responseUser) {
    return null;
  }

  try {
    const me = await getMe(token);
    const user = me?.user || me?.data?.user || me?.data || me;
    return isStudentUser(user) ? user : null;
  } catch {
    return null;
  }
}

async function buildStudentSession(response) {
  const token = response.token || response.access_token;
  const user = await resolveUserForAccess(token, response.user || null);

  if (!user) {
    throw new Error(STUDENT_ACCESS_DENIED_MESSAGE);
  }

  const session = {
    token,
    tokenType: response.token_type || 'bearer',
    expiresIn: response.expires_in || null,
    user,
  };

  assertStudentUser(session.user);
  await saveSession(session);
  return session;
}

export async function login(email, password) {
  let response;
  try {
    response = await apiRequest('/login', {
      method: 'POST',
      body: { email, password },
    });
  } catch (err) {
    // Minsan ang server ay nagbabalik ng 401 status code kapag kailangan ng MFA.
    // Sine-save ng client.js ang response body sa err.data.
    if (err.data?.requires_mfa || err.data?.data?.requires_mfa) {
      response = err.data?.data || err.data;
    } else {
      throw err;
    }
  }

  // Check kung kailangan ng MFA (suportahan ang parehong direct at nested keys)
  const requiresMfa = response?.requires_mfa || response?.data?.requires_mfa;
  if (requiresMfa) {
    return {
      requiresMfa: true,
      tempToken: response.temp_token || response.token || response.data?.temp_token,
      message: response.message || response.data?.message || 'MFA Required'
    };
  }

  if (!response?.success || (!response?.token && !response?.access_token)) {
    throw new Error(response?.message || 'Login failed');
  }

  return buildStudentSession(response);
}

export async function verifyMfa(mfaCode, tempToken) {
  let response;
  try {
    response = await apiRequest('/verify-mfa', {
      method: 'POST',
      body: { mfa_code: mfaCode, temp_token: tempToken },
    });
  } catch (err) {
    // Re-throw the error so LoginScreen can catch and display it
    throw err;
  }

  if (!response?.access_token && !response?.token) {
    throw new Error(response?.error || response?.message || 'Verification failed');
  }

  return buildStudentSession(response);
}

export async function restoreSession() {
  const stored = await loadSession();
  if (!stored?.token) {
    return null;
  }

  let user = stored.user;
  if (!isStudentUser(user)) {
    user = await resolveUserForAccess(stored.token, user);
  }

  if (!isStudentUser(user)) {
    await clearSession();
    return null;
  }

  if (user !== stored.user) {
    const updated = { ...stored, user };
    await saveSession(updated);
    return updated;
  }

  return stored;
}

export async function logout(token) {
  try {
    if (token) {
      await apiRequest('/logout', {
        method: 'POST',
        token,
      });
    }
  } finally {
    await clearSession();
  }
}

export async function getMe(token) {
  return apiRequest('/me', {
    method: 'GET',
    token,
  });
}

export { getStudentDashboard };
