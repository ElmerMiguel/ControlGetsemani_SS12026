import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'system-ui', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    DEFAULT: '#2563EB',
                    50: '#eff6ff',
                    100: '#dbeafe',
                    500: '#2563EB',
                    600: '#1d4ed8',
                    700: '#1e40af',
                },
                accent: {
                    sky: '#7DD3FC',
                },
                success: {
                    DEFAULT: '#22C55E',
                    50: '#f0fdf4',
                    500: '#22C55E',
                    600: '#16a34a',
                },
                surface: '#F3F4F6',
                ink: '#1F2937',
                danger: {
                    DEFAULT: '#DC2626',
                    50: '#fef2f2',
                    500: '#DC2626',
                    600: '#b91c1c',
                },
            },
        },
    },

    plugins: [forms],
};
