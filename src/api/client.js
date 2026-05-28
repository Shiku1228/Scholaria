const DEFAULT_API_URL = 'http://192.168.1.117:8000/api';

export const API_BASE_URL = (process.env.EXPO_PUBLIC_API_URL || DEFAULT_API_URL).replace(/\/$/, '');

function normalizePath(path) {
  return path.startsWith('/') ? path : `/${path}`;
}

async function parseResponse(response) {
  const contentType = response.headers.get('content-type') || '';

  if (contentType.includes('application/json')) {
    return response.json();
  }

  const text = await response.text();
  return text ? { message: text } : {};
}

export async function apiRequest(path, options = {}) {
  const { token, headers, body, timeout = 15000, ...fetchOptions } = options;
  const isFormData =
    typeof FormData !== 'undefined' &&
    body instanceof FormData;

  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), timeout);

  try {
    const response = await fetch(`${API_BASE_URL}${normalizePath(path)}`, {
      ...fetchOptions,
      signal: controller.signal,
      headers: {
        Accept: 'application/json',
        ...(body && !isFormData ? { 'Content-Type': 'application/json' } : {}),
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...(headers || {}),
      },
      body: body ? (isFormData ? body : JSON.stringify(body)) : undefined,
    });
    clearTimeout(timeoutId);

    const data = await parseResponse(response);

    if (!response.ok) {
      const error = new Error(data?.message || 'Request failed');
      error.status = response.status;
      error.data = data;
      throw error;
    }

    return data;
  } catch (err) {
    if (err.name === 'AbortError') {
      const error = new Error('Request timeout');
      error.status = 408;
      throw error;
    }
    throw err;
  } finally {
    clearTimeout(timeoutId);
  }
}
