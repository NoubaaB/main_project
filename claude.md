# BluePrintFish — Module Color Design System

> **MANDATORY**: These colors MUST be respected whenever creating or modifying any page, parameter, or component associated with a module. This applies to Hero Headers, Add/Create buttons, Save/Update/Submit buttons, and any primary accent throughout the module UI.

---

## Module Color Map

### 🔵 Charge Module
| Element | Value |
|---|---|
| **Hero Header** | `background: linear-gradient(135deg, #0D47A1 0%, #1565C0 40%, #42A5F5 100%)` |
| **Button Color** | `#42A5F5` |
| **Vuetify color prop** | Use inline style or custom class (not a built-in Vuetify color) |
| **Applies to** | ChargeCategoryManagement, DetailChargeCategoryManagement, AddCharge, EditCharge, ListCharge |

---

### 🟢 Production Module
| Element | Value |
|---|---|
| **Hero Header** | `background: linear-gradient(135deg, rgb(0, 105, 92) 0%, rgb(0, 137, 123) 40%, rgb(38, 166, 154) 100%)` |
| **Button Color** | `#00897B` |
| **Vuetify color prop** | `teal-darken-1` or `#00897B` |
| **Applies to** | ProductionCategoryManagement, DetailProductionCategoryManagement, AddProduction, EditProduction, ListProduction |

---

### 🟣 Shipping Module
| Element | Value |
|---|---|
| **Hero Header** | `background: linear-gradient(135deg, #372e7d 0%, #4643a0 40%, #6c66bb 100%)` |
| **Button Color** | `#4643a0` |
| **Vuetify color prop** | Use inline style or custom class |
| **Applies to** | ShippingCategoryManagement, DetailShippingCategoryManagement, AddShipping, EditShipping, ListShipping |

---

### 🤖 AI Prompt Module
| Element | Value |
|---|---|
| **Hero Header** | `background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%)` |
| **Button Color** | `#42A5F5` |
| **Vuetify color prop** | `blue` or `#42A5F5` |
| **Applies to** | AI Query, AI Chat components |

---

### 👷 Workforce Module
| Element | Value |
|---|---|
| **Hero Header** | `background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%)` |
| **Button Color** | `#42A5F5` |
| **Vuetify color prop** | `blue` or `#42A5F5` |
| **Applies to** | FortnightManagement, Workforce, Corporals, Sections |

---

### 🟠 Supplier Invoice / Settlement Module
| Element | Value |
|---|---|
| **Hero Header** | `background: linear-gradient(135deg, #E65100 0%, #F57C00 40%, #FFB74D 100%)` |
| **Button Color** | `#E65100` |
| **Vuetify color prop** | `orange-darken-4` or `#E65100` |
| **Applies to** | SupplierInvoice, SupplierSettlement, Supplier Invoice-related pages |

---

### 🩵 Client Invoice / Settlement Module
| Element | Value |
|---|---|
| **Hero Header** | `background: linear-gradient(135deg, #006064 0%, #00838F 40%, #26C6DA 100%)` |
| **Button Color** | `#00838F` |
| **Vuetify color prop** | `cyan-darken-3` or `#00838F` |
| **Applies to** | ClientInvoice, ClientSettlement, Client Invoice-related pages |

---

### 💜 Warehouse Module
| Element | Value |
|---|---|
| **Hero Header** | `background: linear-gradient(135deg, #c015b4 0%, #a21ee5 40%, #9742f5 100%)` |
| **Button Color** | `#a21ee5` |
| **Vuetify color prop** | Use inline style or custom class |
| **Applies to** | WarehouseManagement, WarehouseCategoryManagement, StorageTariff, StockManagement |

---

### 🟤 Inventory Module
| Element | Value |
|---|---|
| **Hero Header** | `background: linear-gradient(135deg, #4E342E 0%, #6D4C41 40%, #8D6E63 100%)` |
| **Button Color** | `#6D4C41` |
| **Vuetify color prop** | `brown-darken-1` or `#6D4C41` |
| **Applies to** | DetailInventoryCategoryManagement, AddInventory, ListInventory |

---

### 🟡 Warehouse Transfer Module
| Element | Value |
|---|---|
| **Hero Header** | `background: linear-gradient(135deg, #F57F17 0%, #F9A825 40%, #FDD835 100%)` |
| **Button Color** | `#F9A825` |
| **Vuetify color prop** | Use inline style or custom class |
| **Applies to** | WarehouseTransferCategoryManagement, AddWarehouseTransfer, ListWarehouseTransfer |

---

### 🌿 Guano Module
| Element | Value |
|---|---|
| **Hero Header** | `background: linear-gradient(135deg, #2E7D32 0%, #388E3C 40%, #66BB6A 100%)` |
| **Button Color** | `#388E3C` |
| **Vuetify color prop** | Use inline style or custom class |
| **Applies to** | GuanoManagement |

---

### 📦 Package Module
| Element | Value |
|---|---|
| **Hero Header** | `background: linear-gradient(135deg, #37474F 0%, #546E7A 40%, #78909C 100%)` |
| **Button Color** | `#546E7A` |
| **Vuetify color prop** | Use inline style or custom class |
| **Applies to** | PackageManagement |

---

## Implementation Rules

> **EVERY UI element** inside a module page MUST use the module's **Button Color**. Never use generic Vuetify named colors (`info`, `teal`, `purple`, `blue`, `green`) — always use the exact hex value from this file.

### Mandatory Elements

1. **Hero Header**: Always use the module's gradient as `background` on the `.xxx-hero` element.
2. **All Buttons (MANDATORY)**: Every primary action button (`Submit`, `Save`, `Update`, `Create`, `Add`, `Expand`, `Archive`, `Done`) MUST use the module's **Button Color**. Tonal, outlined, flat — all variants.
3. **All Input Fields (MANDATORY)**: Use the module's **Button Color** for the `color` prop on ALL input components: `v-text-field`, `v-autocomplete`, `v-select`, `v-textarea`, `v-combobox`, `v-switch`, `v-checkbox`. This applies everywhere: Add/Edit/Create forms, filter inputs, dialog forms.
4. **VCalendar / DatePicker (MANDATORY)**: The `<DatePicker>` (VCalendar) component's `color` prop MUST use the module's color name mapping: Charge → `"blue"`, Production → `"teal"`, Shipping → `"purple"`. Never use `"blue"` for non-Charge modules.
5. **v-divider (MANDATORY)**: All `v-divider` components with a `color` prop MUST use the module's **Button Color** hex. Also applies to the CSS class — use `class="text-xxx"` matching the module color, not `text-info`.
6. **v-icon (MANDATORY)**: All icon `color` props, including step circle icons (`mdi-numeric-X-circle-outline`), must use the module's **Button Color** hex. The `class="text-info"` on step icons must be replaced with inline `style="color: <module hex>"` or the appropriate CSS class.
7. **Chips & Toggles**: Active toggle border / chip color, including `color="info accent-4"` inside `#chip` templates, should match the module's **Button Color**.
8. **Borders & CSS Classes (MANDATORY)**: Replace all `class="text-info"` with a module-specific CSS class or inline style using the module's hex color. Replace all `border-color` or `border` CSS referencing info with the module color.
9. **v-progress-linear**: Snackbar progress bars should use the module's **Button Color**.

### Universal Exceptions (keep as-is)

10. **Filter Clear Button**: Keep `warning` color for filter-clear buttons.
11. **Hero Icon Background**: Use `rgba(255,255,255,.18)` with `backdrop-filter: blur(6px)`.
12. **Error/Delete Buttons**: Keep `color="error"` for destructive actions.
13. **Success indicators**: Keep `color="success"` for cash/price icons and success feedback.
14. **Archive button**: Keep `color="light-green-darken-1"` for archive buttons.
15. **Cancel button**: Keep `color="red-lighten-1"` for cancel/reset buttons.

## Quick Reference (Copy-Paste)

```
Charge:     header=#0D47A1→#1565C0→#42A5F5   btn=#42A5F5
Production: header=#00695C→#00897B→#26A69A    btn=#00897B
Shipping:    header=#372e7d→#4643a0→#6c66bb    btn=#4643a0
AI Prompt:  header=#1565C0→#1E88E5→#42A5F5    btn=#42A5F5
Workforce:  header=#1565C0→#1E88E5→#42A5F5    btn=#42A5F5
Invoice:    header=#E65100→#F57C00→#FFB74D     btn=#E65100
Warehouse:  header=#c015b4→#a21ee5→#9742f5     btn=#a21ee5
Inventory:  header=#4E342E→#6D4C41→#8D6E63     btn=#6D4C41
Transfer:   header=#F57F17→#F9A825→#FDD835      btn=#F9A825
Guano:      header=#2E7D32→#388E3C→#66BB6A     btn=#388E3C
Package:    header=#37474F→#546E7A→#78909C      btn=#546E7A
```

