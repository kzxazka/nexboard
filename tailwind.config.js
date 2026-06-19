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
                    'base': 'rgb(var(--color-base) / <alpha-value>)',
                    'surface': 'rgb(var(--color-surface) / <alpha-value>)',
                    'surface-dim': 'rgb(var(--color-surface-dim) / <alpha-value>)',
                    'surface-bright': 'rgb(var(--color-surface-bright) / <alpha-value>)',
                    'surface-container-lowest': 'rgb(var(--color-surface-container-lowest) / <alpha-value>)',
                    'surface-container-low': 'rgb(var(--color-surface-container-low) / <alpha-value>)',
                    'surface-container': 'rgb(var(--color-surface-container) / <alpha-value>)',
                    'surface-container-high': 'rgb(var(--color-surface-container-high) / <alpha-value>)',
                    'surface-container-highest': 'rgb(var(--color-surface-container-highest) / <alpha-value>)',

                    // Primary
                    'primary': 'rgb(var(--color-primary) / <alpha-value>)',
                    'primary-container': 'rgb(var(--color-primary-container) / <alpha-value>)',
                    'on-primary': 'rgb(var(--color-on-primary) / <alpha-value>)',
                    'on-primary-container': 'rgb(var(--color-on-primary-container) / <alpha-value>)',

                    // Secondary
                    'secondary': 'rgb(var(--color-secondary) / <alpha-value>)',
                    'secondary-container': 'rgb(var(--color-secondary-container) / <alpha-value>)',
                    'on-secondary': 'rgb(var(--color-on-secondary) / <alpha-value>)',

                    // Text
                    'on-surface': 'rgb(var(--color-on-surface) / <alpha-value>)',
                    'on-surface-variant': 'rgb(var(--color-on-surface-variant) / <alpha-value>)',

                    // Outline
                    'outline': 'rgb(var(--color-outline) / <alpha-value>)',
                    'outline-variant': 'rgb(var(--color-outline-variant) / <alpha-value>)',

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
                'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                'gradient': 'gradient 8s ease infinite',
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
                gradient: {
                    '0%, 100%': { backgroundPosition: '0% 50%' },
                    '50%': { backgroundPosition: '100% 50%' },
                },
            },
        },
    },

    plugins: [forms],
};
