import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/front.scss',
                'resources/sass/admin.scss',
                'resources/js/front-app.js',
                'resources/js/admin-app.js',
                'resources/js/livewire/index.js',
                'resources/js/charts/digitizations-chart.js',
                'resources/js/charts/state-map.js',
                'resources/js/charts/stat-chart.js',
                'resources/js/charts/event-rate-chart.js',
                'resources/js/charts/wedigbio-rate-chart.js',
                'resources/js/bingo-entry.js',
                'resources/js/expertReview.js',
            ],
            refresh: true,
        }),
    ],
    css: {
        preprocessorOptions: {
            scss: {
                // Bootstrap 4's Sass predates the module system; don't repeat its deprecation warnings.
                quietDeps: true,
                silenceDeprecations: ['import'],
            },
        },
    },
    build: {
        // The admin bundle carries the rich text editor.
        chunkSizeWarningLimit: 1000,
    },
});
