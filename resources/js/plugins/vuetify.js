/**
 * plugins/vuetify.js
 *
 * Framework documentation: https://vuetifyjs.com
 */

// Styles
import '@mdi/font/css/materialdesignicons.css';
import 'vuetify/styles';

// Composables & Adapters
import { createVuetify } from "vuetify";
import * as components from "vuetify/components";
import * as directives from "vuetify/directives";
import { createVueI18nAdapter } from 'vuetify/locale/adapters/vue-i18n';
import _i18n, { useI18n } from './i18n';

export const i18n = _i18n;

// Detects initial theme from localStorage or OS preference
if (!localStorage.getItem("theme")) {
    localStorage.setItem("theme", window.matchMedia('(prefers-color-scheme: dark)').matches ? "dark" : "light");
}
const initialTheme = localStorage.getItem("theme") || 'light';

// ============================================================================
// THEME SYSTEM TOKENS (Conforming to claude.md and vue-vuetify-design Skill)
// ============================================================================

export const lightTheme = {
    dark: false,
    colors: {
        background: '#F8FAFC',
        surface: '#FFFFFF',
        'surface-bright': '#FFFFFF',
        'surface-light': '#F1F5F9',
        'surface-variant': '#E2E8F0',
        'on-surface-variant': '#475569',
        primary: '#1976D2',
        'primary-darken-1': '#1565C0',
        secondary: '#4F46E5',
        'secondary-darken-1': '#4338CA',
        error: '#DC2626',
        info: '#0284C7',
        success: '#059669',
        warning: '#D97706',
        // BlueprintFish Module Accents (from claude.md)
        charge: '#42A5F5',
        production: '#00897B',
        shipping: '#4643a0',
        ai: '#42A5F5',
        workforce: '#42A5F5',
        invoice: '#E65100',
        warehouse: '#a21ee5',
        inventory: '#6D4C41',
        transfer: '#F9A825',
        guano: '#388E3C',
        package: '#546E7A',
    },
    variables: {
        'border-color': '#0F172A',
        'border-opacity': 0.08,
        'high-emphasis-opacity': 0.90,
        'medium-emphasis-opacity': 0.65,
        'disabled-opacity': 0.38,
    }
};

export const darkTheme = {
    dark: true,
    colors: {
        background: '#0B0F19',
        surface: '#131B2E',
        'surface-bright': '#1A243D',
        'surface-light': '#1E293B',
        'surface-variant': '#1E293B',
        'on-surface-variant': '#94A3B8',
        primary: '#42A5F5',
        'primary-darken-1': '#1E88E5',
        secondary: '#818CF8',
        'secondary-darken-1': '#6366F1',
        error: '#EF5350',
        info: '#42A5F5',
        success: '#10B981',
        warning: '#F59E0B',
        // BlueprintFish Module Accents (from claude.md)
        charge: '#42A5F5',
        production: '#00897B',
        shipping: '#4643a0',
        ai: '#42A5F5',
        workforce: '#42A5F5',
        invoice: '#E65100',
        warehouse: '#a21ee5',
        inventory: '#6D4C41',
        transfer: '#F9A825',
        guano: '#388E3C',
        package: '#546E7A',
    },
    variables: {
        'border-color': '#FFFFFF',
        'border-opacity': 0.10,
        'high-emphasis-opacity': 0.95,
        'medium-emphasis-opacity': 0.70,
        'disabled-opacity': 0.40,
    }
};

export function crerate() {
    return createVuetify({
        theme: {
            defaultTheme: initialTheme,
            themes: {
                light: lightTheme,
                dark: darkTheme,
            },
        },
        components,
        directives,
        locale: {
            adapter: createVueI18nAdapter({ i18n: _i18n, useI18n }),
        },
        defaults: {
            VCard: {
                elevation: 0,
            },
            VBtn: {
                rounded: 'md',
            },
            VTextField: {
                variant: 'outlined',
                density: 'compact',
            },
            VAutocomplete: {
                variant: 'outlined',
                density: 'compact',
            },
            VSelect: {
                variant: 'outlined',
                density: 'compact',
            },
            VTextarea: {
                variant: 'outlined',
                density: 'compact',
            },
        },
    });
}

export const createVuetifyInstance = crerate;
export default crerate;


