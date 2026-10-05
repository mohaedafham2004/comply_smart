/**
 * ComplySmart — Global API Client (non-module, synchronous load)
 * Placed in /public so Vite copies it verbatim to /dist.
 * Load via <script src="/api-global.js"></script> (NO type="module")
 * so window.api is guaranteed to exist before Alpine fires any action.
 */
(function () {
    var API_BASE_URL = (
        typeof window._VITE_API_URL !== 'undefined'
            ? window._VITE_API_URL
            : 'http://localhost:8000/api/v1'
    ).replace(/\/$/, '');

    // Pages that do NOT redirect on 401 (auth pages themselves)
    var AUTH_PAGES = ['/login.html', '/register.html', '/index.html', '/clear_session.html'];

    function isAuthPage() {
        var path = window.location.pathname;
        return AUTH_PAGES.some(function (p) { return path.endsWith(p) || path === '/'; });
    }

    function clearAuthAndRedirect() {
        localStorage.removeItem('cs_token');
        localStorage.removeItem('cs_user');
        localStorage.removeItem('cs_business');
        if (!isAuthPage()) {
            window.location.href = '/login.html';
        }
    }

    async function api(method, endpoint, body, isMultipart) {
        var token = localStorage.getItem('cs_token');
        var cleanEndpoint = endpoint.startsWith('/') ? endpoint : '/' + endpoint;
        var url = endpoint.startsWith('http') ? endpoint : API_BASE_URL + cleanEndpoint;

        var headers = { 'Accept': 'application/json' };
        if (token) headers['Authorization'] = 'Bearer ' + token;

        var payload = body || null;
        if (body && !isMultipart && typeof body === 'object') {
            headers['Content-Type'] = 'application/json';
            payload = JSON.stringify(body);
        }

        try {
            var response = await fetch(url, { method: method, headers: headers, body: payload });
            var data = await response.json().catch(function () { return {}; });

            // Auto-handle expired/invalid token globally
            if (response.status === 401) {
                clearAuthAndRedirect();
            }

            return { ok: response.ok, status: response.status, data: data };
        } catch (e) {
            return { ok: false, status: 0, data: { message: 'Network connection failed. Ensure backend is running.' } };
        }
    }

    function logout() {
        api('POST', '/auth/logout').catch(function () {});
        clearAuthAndRedirect();
    }

    function themeManager() {
        return {
            theme: localStorage.getItem('cs_theme') || 'dark',
            init: function () {
                var t = localStorage.getItem('cs_theme') || 'dark';
                if (t === 'dark') {
                    document.documentElement.classList.add('dark');
                    document.documentElement.classList.remove('light');
                } else {
                    document.documentElement.classList.add('light');
                    document.documentElement.classList.remove('dark');
                }
            },
            toggleTheme: function () {
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

    // ── Auto-protect non-auth pages ───────────────────────────────────────────
    // If user has no token and they are NOT on an auth page, redirect to login.
    (function guardPage() {
        if (isAuthPage()) return;
        var token = localStorage.getItem('cs_token');
        if (!token) {
            window.location.href = '/login.html';
        }
    })();

    // Expose everything globally — synchronously, before DOM is even parsed
    window.api = api;
    window.API_BASE_URL = API_BASE_URL;
    window.logout = logout;
    window.themeManager = themeManager;
    window.getToken = function () { return localStorage.getItem('cs_token') || ''; };
    window.getUser = function () {
        try { return JSON.parse(localStorage.getItem('cs_user') || 'null'); } catch (e) { return null; }
    };
    window.getBusiness = function () {
        try { return JSON.parse(localStorage.getItem('cs_business') || 'null'); } catch (e) { return null; }
    };
})();
