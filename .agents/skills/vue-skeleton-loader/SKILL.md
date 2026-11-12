---
name: vue-skeleton-loader
description: View, analyze, and build pixel-perfect, theme-adaptive Vuetify 3 skeleton loaders that accurately mirror the real component layout, dimensions, margins, cards, tables, calendars, and headers across light and dark themes.
---

# Vue + Vuetify Skeleton Loader Engineering Guide

This skill guides the design, implementation, and audit of **Vuetify 3 (`v-skeleton-loader`)** skeleton loading states across modern Vue 3 applications.

The goal is **zero Cumulative Layout Shift (CLS)**, instant visual responsiveness, and seamless harmony with the active theme (light/dark mode) and module brand colors.

---

## 1. Core Principles

1. **Structural Mirroring**:
   - A skeleton loader must **never** be a generic indeterminate spinner or a monolithic gray block.
   - It must faithfully mirror the geometry, spacing, column divisions, padding (`pa-*`), margins (`ma-*`, `mb-*`), and element archetypes of the loaded component:
     - **Hero Header**: skeleton avatar + title + subtitle text bone.
     - **Calendar / Date Range**: skeleton button/pill toggles + calendar grid / card.
     - **Charts / Visualizations**: skeleton image or custom chart frame matching the exact rendered height (e.g. `height="260"` or `height="320"`).
     - **Summary / KPI Cards**: skeleton card with avatar, number heading, and micro label.
     - **Search / Action Toolbars**: skeleton input bar + action button chips.
     - **Tables**: `app-skeleton-table-row` inside `<tbody>` with per-column bones (`avatar` for expand, `chip` for badges/categories, `text` for strings/amounts, `button` for actions).
     - **Forms / Inputs**: skeleton text field / select inputs matching form rows.

2. **Zero Cumulative Layout Shift (CLS)**:
   - Match the exact rendered height and width of the final component. If a chart renders at `height="260px"`, the skeleton must be `height="260px"`.
   - Never cause content below the loader to jump or reflow when data arrives.

3. **Theme Adaptivity (Light & Dark)**:
   - Skeletons must respect the application's design system:
     - **Light Theme**: Subdued slate tints (`#E2E8F0` / `#F1F5F9` on `#FFFFFF` / `#F8FAFC`).
     - **Dark Theme**: Deep navy/slate tones (`#1E293B` / `#2A374D` on `#131B2E` / `#0B0F19`).
   - Skeletons must inherit or cleanly overlay the container's surface. Inside table `<td>` cells, skeletons **MUST** have a transparent container (`background: transparent !important`) so that cell boundaries and table striping remain intact.

4. **Module Color Harmony**:
   - When skeleton headers or accent borders are shown, they should match the module palette defined in `claude.md`:
     - Charge: `#42A5F5`
     - Production: `#00897B`
     - Shipping: `#4643a0`
     - Supplier Invoice: `#E65100`
     - Client Invoice: `#00838F`
     - Inventory: `#6D4C41`
     - Warehouse Transfer: `#F9A825`
     - Guano: `#388E3C`
     - Package: `#546E7A`

---

## 2. Standard Skeleton Patterns

### Pattern A: Data Table Skeleton (`app-skeleton-table-row`)
```html
<tbody>
  <template v-if="loading">
    <tr v-for="r in 8" :key="'skel-' + r" class="app-skeleton-table-row">
      <!-- Checkbox Column -->
      <td v-if="hasCheckbox" class="text-center">
        <v-skeleton-loader type="chip" width="20" height="20" class="mx-auto app-skel-cell" />
      </td>
      <!-- Dynamic Columns -->
      <td v-for="h in headers" :key="'skel-c-' + h.key + '-' + r">
        <v-skeleton-loader
          :type="h.key === 'expand' ? 'avatar' : (h.key.includes('status') || h.key.includes('category') || h.key.includes('number')) ? 'chip' : 'text'"
          class="app-skel-cell"
        />
      </td>
    </tr>
  </template>
  <template v-else-if="items.length === 0">
    <!-- Empty State -->
  </template>
  <template v-else>
    <!-- Real Data Rows -->
  </template>
</tbody>
```

### Pattern B: Chart & KPI Card Skeleton
```html
<v-card class="rounded-xl border pa-4" elevation="0">
  <div class="d-flex align-center justify-between mb-4">
    <v-skeleton-loader type="heading" width="40%" class="app-skel-cell" />
    <v-skeleton-loader type="button" width="100" class="app-skel-cell" />
  </div>
  <v-skeleton-loader type="image" height="260" class="rounded-xl app-skel-transparent" />
</v-card>
```

### Pattern C: Form / Parameter Edit Dialog Skeleton
```html
<v-row v-if="loading">
  <v-col cols="12" md="6" v-for="i in 6" :key="'form-skel-' + i">
    <v-skeleton-loader type="text" width="30%" class="mb-1 app-skel-cell" />
    <v-skeleton-loader type="heading" height="40" class="rounded-lg app-skel-input" />
  </v-col>
</v-row>
```

### Pattern D: Calendar & Date Presets Skeleton
```html
<div v-if="loading" class="date-picker-skeleton pa-3">
  <div class="d-flex gap-2 mb-3">
    <v-skeleton-loader v-for="p in 4" :key="p" type="button" width="23%" height="32" class="app-skel-cell" />
  </div>
  <v-skeleton-loader type="image" height="280" class="rounded-xl app-skel-transparent" />
</div>
```

---

## 3. Global CSS Specifications

Ensure the following styling rules are present in `resources/css/app.scss`:

```scss
/* Theme-Adaptive Bone Colors */
.v-theme--light .v-skeleton-loader__bone {
  background: rgba(15, 23, 42, 0.07) !important;
}
.v-theme--light .v-skeleton-loader__bone::after {
  background: linear-gradient(90deg, rgba(255, 255, 255, 0), rgba(255, 255, 255, 0.4), rgba(255, 255, 255, 0)) !important;
}

.v-theme--dark .v-skeleton-loader__bone {
  background: rgba(255, 255, 255, 0.08) !important;
}
.v-theme--dark .v-skeleton-loader__bone::after {
  background: linear-gradient(90deg, rgba(255, 255, 255, 0), rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0)) !important;
}

.app-skel-cell {
  background: transparent !important;
  .v-skeleton-loader__text,
  .v-skeleton-loader__bone,
  .v-skeleton-loader__avatar,
  .v-skeleton-loader__chip {
    margin: 0 !important;
  }
}

.app-skel-transparent {
  background: transparent !important;
}

.app-skel-input {
  border-radius: 8px !important;
  overflow: hidden !important;
  background: transparent !important;
}
```

---

## 4. Verification Checklist

Before finishing any component skeleton:
- [ ] Skeleton layout mirrors real loaded component layout.
- [ ] No Cumulative Layout Shift when loaded data replaces skeleton.
- [ ] Tested in **Light Mode**: crisp, subtle slate contrast, no stark blocks.
- [ ] Tested in **Dark Mode**: sleek dark slate shimmer, no glaring white flashes.
- [ ] Table cells maintain alignment and column pinning styles.
- [ ] Zero console warnings or CSS syntax errors.

