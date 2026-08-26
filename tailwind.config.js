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
                sans: ['Manrope', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#eef1ff',
                    100: '#e0e4ff',
                    200: '#c6ccff',
                    300: '#a5aeff',
                    400: '#8288ff',
                    500: '#4f6bff',
                    600: '#3d52e0',
                    700: '#3040b8',
                    800: '#293593',
                    900: '#252f75',
                },
            },
        },
    },

    plugins: [forms],
};
