const SESSION_KEY = 'scholaria_session';

let memorySession = null;

function getStorage() {
  if (typeof window !== 'undefined' && window.localStorage) {
    return window.localStorage;
  }

  return null;
}

export async function saveSession(session) {
  memorySession = session;

  const storage = getStorage();
  if (storage) {
    storage.setItem(SESSION_KEY, JSON.stringify(session));
  }

  return session;
}

export async function loadSession() {
  const storage = getStorage();
  if (storage) {
    const raw = storage.getItem(SESSION_KEY);
    if (!raw) {
      return null;
    }

    try {
      const parsed = JSON.parse(raw);
      memorySession = parsed;
      return parsed;
    } catch {
      storage.removeItem(SESSION_KEY);
      return null;
    }
  }

  return memorySession;
}

export async function clearSession() {
  memorySession = null;

  const storage = getStorage();
  if (storage) {
    storage.removeItem(SESSION_KEY);
  }
}
