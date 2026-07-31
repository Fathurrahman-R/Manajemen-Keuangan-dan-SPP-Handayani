import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/filament/admin/theme.css',
                'resources/css/public.css',
                'resources/js/public.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            host: 'localhost',
        },
        // Bind mount Windows -> Docker (WSL2) tidak meneruskan native filesystem
        // events ke chokidar, jadi watcher default diam saja terhadap perubahan
        // file. Polling paksa Vite cek mtime tiap interval, jadi tetap kedeteksi.
        watch: {
            usePolling: true,
            interval: 300,
        },
    },
    build: {
        cssCodeSplit: true,
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        return 'vendor';
                    }
                }
            }
        },
        minify: 'esbuild',
        sourcemap: false,
    }
});
