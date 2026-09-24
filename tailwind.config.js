import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Baloo Da 2"', '"Hind Siliguri"', '"Source Sans 3"', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                brand: {
                    50: '#F5F3FF',
                    100: '#EDE9FE',
                    200: '#DDD6FE',
                    300: '#C4B5FD',
                    400: '#A78BFA',
                    500: '#8B5CF6',
                    600: '#673DE6', // Hostinger Signature Primary
                    700: '#5428D8', // Hostinger Primary Hover
                    800: '#431DB5',
                    900: '#32158E',
                    950: '#1E0C58',
                },
                hslate: {
                    50: '#F8FAFC',  // Hostinger Canvas Background
                    100: '#F1F5F9', // Secondary Surface
                    200: '#E2E8F0', // Border subtle
                    300: '#CBD5E1', // Border strong
                    400: '#94A3B8', // Muted text
                    500: '#64748B', // Secondary text
                    600: '#475569', // Body text
                    700: '#334155', // Dark label
                    800: '#1E293B', // Headings dark
                    900: '#0F172A', // Pure title graphite
                    950: '#020617', // Pitch black
                }
            },
            boxShadow: {
                'hcard': '0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.05)',
                'hcard-hover': '0 10px 25px -5px rgba(103, 61, 230, 0.08), 0 8px 10px -6px rgba(103, 61, 230, 0.04)',
                'hmodal': '0 25px 50px -12px rgba(15, 23, 42, 0.25)',
                'hbtn-primary': '0 4px 14px 0 rgba(103, 61, 230, 0.35)',
            },
            borderRadius: {
                '2xl': '1rem',
                '3xl': '1.5rem',
            }
        },
    },

    plugins: [forms],
};
