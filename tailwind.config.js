import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', '-apple-system', 'BlinkMacSystemFont', '"SF Pro Text"', 'sans-serif'],
                mono: ['"JetBrains Mono"', 'SFMono-Regular', 'Menlo', 'monospace'],
            },
            colors: {
                apple: {
                    canvas: '#0D0D0E',
                    surface: '#18181B',
                    elevated: '#1C1C1E',
                    subtle: '#242426',
                    border: '#26262A',
                    borderLight: '#323236',
                    text: '#F5F5F7',
                    secondary: '#8E8E93',
                    tertiary: '#636366',
                    blue: '#0A84FF',
                    'blue-hover': '#0077ED',
                    green: '#30D158',
                    orange: '#FF9F0A',
                    red: '#FF453A',
                    purple: '#BF5AF2',
                }
            },
            boxShadow: {
                'apple-card': '0 2px 12px rgba(0, 0, 0, 0.4), 0 1px 3px rgba(0, 0, 0, 0.3)',
                'apple-modal': '0 24px 48px rgba(0, 0, 0, 0.7), 0 1px 4px rgba(255, 255, 255, 0.05)',
                'apple-btn': '0 1px 2px rgba(0, 0, 0, 0.3)',
            }
        },
    },
    plugins: [],
};
