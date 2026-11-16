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
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },

            colors: {
                surface: '#f8f9ff',
                'surface-dim': '#cbdbf5',
                'surface-bright': '#f8f9ff',
                'surface-container-lowest': '#ffffff',
                'surface-container-low': '#eff4ff',
                'surface-container': '#e5eeff',
                'surface-container-high': '#dce9ff',
                'surface-container-highest': '#d3e4fe',
                'on-surface': '#0b1c30',
                'on-surface-variant': '#434655',
                outline: '#737686',
                'outline-variant': '#c3c6d7',
                'surface-tint': '#0053db',

                primary: '#2563eb',
                'on-primary': '#ffffff',
                'primary-container': '#2563eb',
                'on-primary-container': '#eeefff',
                'primary-fixed': '#dbe1ff',
                'on-primary-fixed': '#00174b',

                secondary: '#5c5e69',
                'secondary-container': '#e1e1ef',
                'secondary-fixed': '#e1e1ef',
                'on-secondary-fixed': '#191b24',

                tertiary: '#4338d9',
                'tertiary-container': '#5d55f3',
                'on-tertiary-container': '#f2eeff',

                error: '#ba1a1a',
                'error-container': '#ffdad6',
                'on-error-container': '#93000a',

                'sidebar-dark': '#0F111A',
                'sidebar-surface': '#161927',
                'sidebar-active': '#3B82F6',
                'canvas-bg': '#F8FAFC',
                'card-bg': '#FFFFFF',
                'card-border': '#E2E8F0',

                'banner-sky': '#38BDF8',
                'banner-indigo': '#4F46E5',
                'banner-deep': '#4338CA',

                'net-pay-emerald': '#10B981',
                'net-pay-surface': '#ECFDF5',
                'warning-amber': '#F59E0B',
                'warning-surface': '#FEF3C7',
                'danger-red': '#EF4444',
                'danger-surface': '#FEE2E2',

                'slate-text-primary': '#0F172A',
                'slate-text-secondary': '#64748B',
                'slate-text-muted': '#94A3B8',
            },

            fontSize: {
                'display-hero': ['2.25rem', { lineHeight: '2.75rem', fontWeight: '700', letterSpacing: '-0.025em' }],
                'display-hero-mobile': ['1.625rem', { lineHeight: '2.125rem', fontWeight: '700', letterSpacing: '-0.02em' }],
                'headline-lg': ['1.75rem', { lineHeight: '2.25rem', fontWeight: '700', letterSpacing: '-0.02em' }],
                'headline-lg-mobile': ['1.375rem', { lineHeight: '1.875rem', fontWeight: '700', letterSpacing: '-0.015em' }],
                'headline-md': ['1.25rem', { lineHeight: '1.75rem', fontWeight: '600', letterSpacing: '-0.015em' }],
                'headline-sm': ['1rem', { lineHeight: '1.5rem', fontWeight: '600', letterSpacing: '-0.01em' }],
                'body-lg': ['1rem', { lineHeight: '1.5rem' }],
                'body-md': ['0.875rem', { lineHeight: '1.25rem' }],
                'body-sm': ['0.75rem', { lineHeight: '1.125rem' }],
                'financial-total': ['1.5rem', { lineHeight: '2rem', fontWeight: '700', letterSpacing: '-0.02em' }],
                'financial-table': ['0.8125rem', { lineHeight: '1.125rem', fontWeight: '500' }],
                'label-md': ['0.75rem', { lineHeight: '1rem', fontWeight: '600', letterSpacing: '0.025em' }],
                'label-sm': ['0.6875rem', { lineHeight: '0.875rem', fontWeight: '600', letterSpacing: '0.05em' }],
            },

            spacing: {
                gutter: '1rem',
                'gutter-md': '1.5rem',
                'space-xs': '0.25rem',
                'space-sm': '0.5rem',
                'space-md': '0.75rem',
                'space-lg': '1rem',
                'space-xl': '1.5rem',
                'space-2xl': '2rem',
            },

            boxShadow: {
                card: '0 1px 3px 0 rgba(15, 23, 42, 0.05), 0 1px 2px -1px rgba(15, 23, 42, 0.05)',
                hero: '0 10px 15px -3px rgba(79, 70, 229, 0.15), 0 4px 6px -4px rgba(79, 70, 229, 0.10)',
                drawer: '0 20px 25px -5px rgba(15, 23, 42, 0.15), 0 8px 10px -6px rgba(15, 23, 42, 0.10)',
            },
        },
    },

    plugins: [forms],
};
