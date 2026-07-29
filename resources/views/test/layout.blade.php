<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ComplySmart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .tab-active { @apply bg-indigo-600 text-white; }
    </style>
</head>
<body class="h-full text-gray-100 font-sans">

<div class="min-h-full flex flex-col">

    {{-- Top Nav --}}
    <nav class="bg-gray-900 border-b border-gray-800 px-6 py-3 flex items-center gap-6">
        <span class="text-indigo-400 font-bold text-lg tracking-tight">🛡 ComplySmart</span>
        <span class="text-xs text-gray-500 ml-2">Test Frontend — API v1</span>
        <div class="ml-auto flex items-center gap-4 text-sm">
            <a href="/test" class="text-gray-300 hover:text-white">Home</a>
            <a href="/test/auth" class="text-gray-300 hover:text-white">Auth</a>
            <a href="/test/businesses" class="text-gray-300 hover:text-white">Businesses</a>
            <a href="/test/documents" class="text-gray-300 hover:text-white">Documents</a>
            <a href="/test/tasks" class="text-gray-300 hover:text-white">Tasks</a>
            <a href="/test/renewals" class="text-gray-300 hover:text-white">Renewals</a>
            <a href="/test/notifications" class="text-gray-300 hover:text-white">🔔 Notifs</a>
            <a href="/test/ai" class="text-gray-300 hover:text-white">AI Chat</a>
            <a href="/test/reports" class="text-gray-300 hover:text-white">📊 Reports</a>
        </div>
    </nav>

    {{-- Page Content --}}
    <main class="flex-1 px-6 py-8 max-w-6xl mx-auto w-full">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-gray-900 border-t border-gray-800 px-6 py-3 text-center text-xs text-gray-600">
        ComplySmart Test Frontend · API Base: <code class="text-indigo-400">{{ config('app.url') }}/api/v1</code>
    </footer>
</div>

{{-- Global JS helpers --}}
<script>
    const API_BASE = '/api/v1';

    // Get token from localStorage
    function getToken() { return localStorage.getItem('cs_token'); }
    function setToken(t) { localStorage.setItem('cs_token', t); }
    function clearToken() { localStorage.removeItem('cs_token'); }

    // API fetch wrapper
    async function api(method, path, body = null, isFormData = false) {
        const headers = { 'Accept': 'application/json' };
        const token = getToken();
        if (token) headers['Authorization'] = `Bearer ${token}`;
        if (!isFormData && body) headers['Content-Type'] = 'application/json';

        const opts = { method, headers };
        if (body) opts.body = isFormData ? body : JSON.stringify(body);

        const res = await fetch(API_BASE + path, opts);
        const data = await res.json().catch(() => ({}));
        return { ok: res.ok, status: res.status, data };
    }

    function prettyJson(obj) {
        return JSON.stringify(obj, null, 2);
    }
</script>

@stack('scripts')
</body>
</html>
