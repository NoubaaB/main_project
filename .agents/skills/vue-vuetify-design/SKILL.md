---
name: vue-vuetify-design
description: Design beautiful web application interfaces with Vue 3 and Vuetify 3, with French, English, and Spanish language support and light and dark themes. Use when creating or redesigning pages, dashboards, layouts, themes, or components in a Vue + Vuetify app, and especially when redesigning a whole application from A to Z.
---

# Vue + Vuetify Design

Work WITH Vuetify, never against it. Use Vuetify components natively, customize through its theme system, and let the design system do its job. Build real working code, not mockups. The result must look beautiful and intentional, never like a default template.

## 1. Discover the stack first

Before writing any code, inspect the project and state your findings.

- Read `package.json` and confirm the Vue version (Vue 3, Composition API with `<script setup>`) and the Vuetify version (Vuetify 3).
- Find the Vuetify setup file, usually `src/plugins/vuetify.ts`, and read the current theme, defaults, icons, and locale configuration.
- Find the existing layout files, router setup, and any design tokens already in use.
- State the findings in one line before proceeding, for example: "Detected: Vue 3 + Vuetify 3.9, custom light theme, MDI icons, French locale. I will use Vuetify components natively and customize through the theme."

## 2. Define the design direction before coding

Never start from a blank page with default styling. Decide these first and write them down.

- Purpose and audience: who uses this screen and what task must they finish fastest.
- One primary color, one semantic set (success, warning, error, info), and neutrals for surfaces and text. Pick colors with a real identity for the product, not the default blue or a purple-blue gradient.
- Typography: one font family for headings and one for body, or a single family with clear weight and size steps. Set it through the theme, not per component.
- Density: `default` for marketing pages, `comfortable` or `compact` for data-dense dashboards and admin screens.
- Light theme plus a dark theme variant whenever the app is used for long sessions.
- Beautiful means intentional: generous whitespace, one accent color used sparingly, consistent radius and elevation, no more than two font families, no stock gradients, no generic hero sections, no decorative clutter that serves no task.

## 3. Build the layout with Vuetify structure

- App shell: `v-app` containing `v-app-bar`, `v-navigation-drawer` (use `rail` on desktop, temporary drawer on mobile), and `v-main`.
- Page content: `v-container` with `v-row` and `v-col`, using breakpoint props (`cols`, `sm`, `md`, `lg`) so the layout is mobile-first and reflows at 360px, 768px, and 1280px.
- Never build a layout with raw CSS grid or flex when Vuetify's grid already covers it.

## 4. Use Vuetify components natively

- Data: `v-data-table` with search, filters above the table, a sticky header, an actions column, and server-side pagination for large lists. Format numbers, dates, and money; show status with `v-chip` colors that match the semantic palette.
- Forms: `v-form` with `v-text-field`, `v-select`, `v-autocomplete`, `v-textarea`, and validation rules. Show errors inline and confirm destructive actions with `v-dialog`.
- Feedback: `v-snackbar` for success and error messages, `v-skeleton-loader` while data loads, `v-empty-state` when a list is empty.
- Navigation: `v-tabs` for sections, `v-breadcrumbs` for depth, `v-menu` for contextual actions.
- Icons: use Material Design Icons through Vuetify's icon system, never inline SVGs copied from elsewhere, unless the icon does not exist in MDI.
- If a component already exists in Vuetify, use it. Never rebuild a date picker, autocomplete, table, or dialog from scratch.

## 5. Customize through the theme, never through overrides

- Change colors, fonts, and radius in `createVuetify({ theme: { themes: { light: { colors, variables } } } })`.
- Set component defaults in `createVuetify({ defaults: { VBtn: {...}, VCard: {...} } })` so repeated props live in one place.
- For dynamic colors at runtime, use `useTheme()` from Vuetify.
- Never target Vuetify internal class names (like `.v-btn__content`) with CSS, and never hardcode a hex color in a template when a theme color exists.
- Spacing and layout use Vuetify utility classes (`pa-4`, `mb-6`, `d-flex`), not one-off CSS classes.

## 6. Data-dense screens (dashboards, admin, stock)

- Put the most important numbers first as summary cards with a label, a formatted value, and a small trend or comparison.
- Keep tables scannable: left-aligned text, right-aligned numbers, compact density, row hover, and no more than one primary action per row plus a menu for the rest.
- Filters live above the table in one row and reset with a single button. Every filter must visibly change the result count.
- Never paginate away the user's context: keep the active filter state visible while scrolling.

## 7. Accessibility, language, and direction

- Every input has a visible label. Interactive elements are keyboard reachable and show a focus state. Text contrast meets WCAG AA against its background.
- Set the Vuetify locale to the app's language and load the matching messages. For Arabic interfaces, enable Vuetify's `rtl` option and mirror the drawer and navigation.
- Dates, numbers, and currency follow the user's locale, not the developer's.
- For applications in French, English, and Spanish, follow section 9 for the complete multilingual setup.

## 8. Quality checks before finishing

- The app builds without errors and the new screen renders at 360px, 768px, and 1280px without horizontal scrolling.
- Light and dark themes both look intentional, with no unreadable text.
- No hardcoded hex colors outside the theme file, no CSS overrides of Vuetify internals, and no component rebuilt that Vuetify already provides.
- Loading, empty, and error states all exist for every data-driven screen.

## 9. Languages: French, English, Spanish

Every screen ships in all three languages. No screen is finished until it reads correctly in French, English, and Spanish.

- Use `vue-i18n` with one file per language: `src/locales/fr.json`, `src/locales/en.json`, `src/locales/es.json`. Wire Vuetify's built-in locale messages (`fr`, `en`, `es` from `vuetify/locale`) into `createVuetify` so Vuetify's own strings (pagination, table labels, date pickers) translate too.
- Add one language switcher in the app bar, a `v-menu` listing Français, English, and Español. Switching sets the `vue-i18n` locale and the Vuetify locale together, and persists the choice in `localStorage`. Default locale is French, fallback locale is French.
- No hardcoded UI string anywhere: every label, button, title, placeholder, validation message, and notification comes from the locale files. A missing key is a defect, not a detail.
- Dates, numbers, and currency use `Intl` formatters with the active locale, never a fixed format.
- Verify every screen in all three languages: no truncated text, no overflow (Spanish strings run longer than French and English), no missing keys, no layout breakage from longer words.

## 10. Themes: light and dark

Both themes are designed deliberately. A dark theme is never an automatic inversion of the light one.

- Define a full color set for `light` and a full color set for `dark` in `createVuetify({ theme: { themes: { light: {...}, dark: {...} } } })`, including surfaces, surface variants, borders, and muted text colors for each.
- Put a theme toggle in the app bar (sun and moon icons). Persist the choice in `localStorage`. On first visit, respect the operating system's `prefers-color-scheme`.
- Dark theme rules: elevated surfaces are lighter than the background, borders stay subtle, never use pure black or pure white, and muted text keeps WCAG AA contrast against its surface.
- Verify every screen in both themes: no unreadable text, no invisible borders, no accent color that only works on one background.

## 11. Full-application redesign, from A to Z

When the user asks to redesign the whole application, follow this workflow in order. Do not jump straight to individual screens.

- Step 1, audit: list every route and screen, inventory the components in use, and record the current theme, hardcoded colors, hardcoded strings, and one-off CSS. Describe the current state before changing anything.
- Step 2, freeze functionality: the redesign is visual only. Keep the same routes, data flow, permissions, and behavior, unless the user explicitly asks for a functional change.
- Step 3, design system first: define the palette for light and dark themes, typography, radius, density, and component defaults in `src/plugins/vuetify.ts`, plus the locale files for French, English, and Spanish. Build this foundation before touching any screen.
- Step 4, shell: rebuild the app shell first: app bar, navigation drawer, layout, language switcher, and theme toggle.
- Step 5, screens one by one, most used first: apply the design system, replace hardcoded colors with theme tokens, replace custom-built components with Vuetify natives where one exists, extract every hardcoded string into the locale files, and delete one-off CSS.
- Step 6, verification passes: a language pass (every screen in French, English, and Spanish), a theme pass (every screen in light and dark), and a responsive pass (360px, 768px, 1280px).
- Step 7, final check: the app builds without errors, the main flows still work end to end with no regression, and loading, empty, and error states exist on every data-driven screen.

