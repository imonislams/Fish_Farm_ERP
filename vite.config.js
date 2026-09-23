import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

/*
 | Fish Farm ERP — Vite config.
 |
 | TWO entry points, on purpose (controlled migration):
 |   resources/js/app.js    → legacy vanilla-JS boot for the NOT-YET-migrated Blade pages
 |   resources/js/app.jsx   → Inertia + React SPA entry for the migrated pages
 |
 | Both share resources/css/app.css (Tailwind 4 design tokens). A page includes only
 | the entry it needs, so migrating a module never breaks the pages still on Blade.
 */
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/app.jsx',
            ],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
