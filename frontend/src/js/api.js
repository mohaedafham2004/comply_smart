/**
 * ComplySmart Decoupled API Client & Global Store
 *
 * Reads backend API base URL from VITE_API_URL environment variable.
 */
export const API_BASE_URL = (
    (typeof import.meta !== 'undefined' && import.meta.env && import.meta.env.VITE_API_URL) 
    || '/api/v1'
).replace(/\/$/, '');

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
            data: { message: 'Network connection failed. Ensure backend is running.' }
        };
    }
}

export function logout() {
    api('POST', '/auth/logout').catch(() => {});
    localStorage.removeItem('cs_token');
    localStorage.removeItem('cs_user');
    localStorage.removeItem('cs_business');
    window.location.href = '/login.html';
}

export function getToken() {
    return localStorage.getItem('cs_token') || '';
}

export function getUser() {
    try {
        return JSON.parse(localStorage.getItem('cs_user') || 'null');
    } catch {
        return null;
    }
}

export function getBusiness() {
    try {
        return JSON.parse(localStorage.getItem('cs_business') || 'null');
    } catch {
        return null;
    }
}

// Global Alpine Theme Manager
export function themeManager() {
    return {
        theme: localStorage.getItem('cs_theme') || 'light',
        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('cs_theme', this.theme);
            if (this.theme === 'dark') {
                document.documentElement.classList.add('dark');
                document.documentElement.classList.remove('light');
            } else {
                document.documentElement.classList.add('light');
                document.documentElement.classList.remove('dark');
            }
        }
    };
}

// Global Navigation Shell Store
export function globalShell(currentPath = '') {
    return {
        token: getToken(),
        user: getUser(),
        business: getBusiness(),
        unreadCount: 0,
        currentPath: currentPath || window.location.pathname,

        async initShell() {
            if (this.token) {
                await this.refreshUser();
                await this.refreshNotifications();
            }
        },

        async refreshUser() {
            const res = await api('GET', '/auth/me');
            if (res.ok) {
                this.user = res.data.data?.user || res.data.data;
                this.business = res.data.data?.business || this.business;
                localStorage.setItem('cs_user', JSON.stringify(this.user));
                if (this.business) localStorage.setItem('cs_business', JSON.stringify(this.business));
            } else if (res.status === 401) {
                logout();
            }
        },

        async refreshNotifications() {
            const res = await api('GET', '/notifications?unread_only=1');
            if (res.ok) {
                this.unreadCount = res.data.meta?.unread ?? (res.data.data ? res.data.data.length : 0);
            }
        },

        isActive(page) {
            const p = window.location.pathname;
            return p.includes(page) || (page === 'dashboard' && (p === '/' || p === '/dashboard' || p === '/dashboard.html'));
        },

        logout() {
            logout();
        }
    };
}

// Attach globally
if (typeof window !== 'undefined') {
    window.api = api;
    window.API_BASE_URL = API_BASE_URL;
    window.logout = logout;
    window.getToken = getToken;
    window.getUser = getUser;
    window.getBusiness = getBusiness;
    window.themeManager = themeManager;
    window.globalShell = globalShell;
}
