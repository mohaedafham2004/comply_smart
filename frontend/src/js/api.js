/**
 * ComplySmart Decoupled API Client
 *
 * Reads backend API base URL from VITE_API_URL environment variable.
 */
export const API_BASE_URL = (import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1').replace(/\/$/, '');

export async function api(method, endpoint, body = null, isMultipart = false) {
    const token = localStorage.getItem('cs_token');
    const cleanEndpoint = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
    const url = endpoint.startsWith('http') ? endpoint : `${API_BASE_URL}${cleanEndpoint}`;

    const headers = {
        'Accept': 'application/json',
    };

    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    let payload = body;
    if (body && !isMultipart && typeof body === 'object') {
        headers['Content-Type'] = 'application/json';
        payload = JSON.stringify(body);
    }

    try {
        const response = await fetch(url, {
            method,
            headers,
            body: payload,
        });

        const data = await response.json().catch(() => ({}));
        return {
            ok: response.ok,
            status: response.status,
            data: data
        };
    } catch (e) {
        return {
            ok: false,
            status: 0,
            data: { message: 'Network connection failed.' }
        };
    }
}

// Attach to window object for global Alpine.js access
if (typeof window !== 'undefined') {
    window.api = api;
    window.API_BASE_URL = API_BASE_URL;
}
