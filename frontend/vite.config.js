import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
  server: {
    port: 5173,
    host: true,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },
  build: {
    outDir: 'dist',
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'index.html'),
        login: resolve(__dirname, 'login.html'),
        register: resolve(__dirname, 'register.html'),
        dashboard: resolve(__dirname, 'dashboard.html'),
        profile: resolve(__dirname, 'profile.html'),
        documents: resolve(__dirname, 'documents.html'),
        upload_document: resolve(__dirname, 'upload_document.html'),
        document_details: resolve(__dirname, 'document_details.html'),
        tasks: resolve(__dirname, 'tasks.html'),
        renewals: resolve(__dirname, 'renewals.html'),
        ai: resolve(__dirname, 'ai.html'),
        reports: resolve(__dirname, 'reports.html'),
        notifications: resolve(__dirname, 'notifications.html'),
        clear_session: resolve(__dirname, 'clear_session.html'),
      },
    },
  },
});
