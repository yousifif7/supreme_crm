import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/custom/toastr-helpers.js', 'resources/js/custom/ajax.js'],
            refresh: true,
        }),
    ],
});
