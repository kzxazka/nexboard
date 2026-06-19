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

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // NexBoard Design System Colors
                nexboard: {
                    // Surfaces
                    'base': '#020617',
                    'surface': '#0b1326',
                    'surface-dim': '#0b1326',
                    'surface-bright': '#31394d',
                    'surface-container-lowest': '#060e20',
                    'surface-container-low': '#131b2e',
                    'surface-container': '#171f33',
                    'surface-container-high': '#222a3d',
                    'surface-container-highest': '#2d3449',

                    // Primary
                    'primary': '#c0c1ff',
                    'primary-container': '#8083ff',
                    'on-primary': '#1000a9',
                    'on-primary-container': '#0d0096',

                    // Secondary
                    'secondary': '#d0bcff',
                    'secondary-container': '#571bc1',
                    'on-secondary': '#3c0091',

                    // Text
                    'on-surface': '#dae2fd',
                    'on-surface-variant': '#c7c4d7',

                    // Outline
                    'outline': '#908fa0',
                    'outline-variant': '#464554',

                    // Status
                    'success': '#10B981',
                    'warning': '#F59E0B',
                    'info': '#3B82F6',
                    'error': '#ffb4ab',
                    'neutral-badge': '#64748B',
                },
                // Shortcut for primary indigo
                indigo: {
                    400: '#818CF8',
                    500: '#6366F1',
                    600: '#4F46E5',
                    700: '#4338CA',
                },
            },
            borderRadius: {
                'nex': '0.5rem',
                'nex-lg': '1rem',
                'nex-xl': '1.5rem',
            },
            backdropBlur: {
                'nex': '12px',
                'nex-lg': '20px',
            },
            animation: {
                'fade-in': 'fadeIn 0.3s ease-out',
                'slide-up': 'slideUp 0.4s ease-out',
                'slide-in-left': 'slideInLeft 0.3s ease-out',
                'pulse-glow': 'pulseGlow 2s ease-in-out infinite',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                slideUp: {
                    '0%': { opacity: '0', transform: 'translateY(20px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                slideInLeft: {
                    '0%': { opacity: '0', transform: 'translateX(-20px)' },
                    '100%': { opacity: '1', transform: 'translateX(0)' },
                },
                pulseGlow: {
                    '0%, 100%': { boxShadow: '0 0 5px rgba(99, 102, 241, 0.3)' },
                    '50%': { boxShadow: '0 0 20px rgba(99, 102, 241, 0.6)' },
                },
            },
        },
    },

    plugins: [forms],
};
