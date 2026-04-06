import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Livewire/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Poppins', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50:  '#e6f0f3',
                    100: '#cce1e7',
                    200: '#99c3cf',
                    300: '#66a5b7',
                    400: '#33879f',
                    500: '#004B5F', // warna utama PCR
                    600: '#003d4f',
                    700: '#00303f',
                    800: '#00222f',
                    900: '#00151f',
                    DEFAULT: '#004B5F',
                },
                accent: {
                    50:  '#fce8ec',
                    100: '#f9d1d9',
                    200: '#f3a3b3',
                    300: '#ed758d',
                    400: '#e74767',
                    500: '#D2092F', // merah PCR — gunakan sparingly
                    600: '#a80726',
                    700: '#7e061c',
                    800: '#540413',
                    900: '#2a0209',
                    DEFAULT: '#D2092F',
                },
            },
        },
    },

    plugins: [forms],
};