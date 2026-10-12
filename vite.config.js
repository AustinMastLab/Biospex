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
                'resources/js/amChartMap-entry.js',
                'resources/js/amChartTranscript-entry.js',
                'resources/js/amChartStat-entry.js',
                'resources/js/amChartEventRate-entry.js',
                'resources/js/amChartWeDigBioRate-entry.js',
                'resources/js/amChartBingo-entry.js',
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
        // The admin bundle carries jqGrid, jQuery UI and Summernote.
        chunkSizeWarningLimit: 1000,
    },
});
