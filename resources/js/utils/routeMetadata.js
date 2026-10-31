/**
 * Utility for resolving route titles, module sections, icons, colors, and sibling links.
 */
import i18n from '@/plugins/i18n';

export const MODULE_DEFINITIONS = [
  {
    key: 'billing_supplier',
    sectionKey: 'supplier_invoices',
    fallbackSection: 'Supplier Invoices',
    color: '#E65100',
    icon: 'mdi-file-document-multiple',
    routes: [
      { name: 'list_supplier_invoice', titleKey: 'list_supplier_invoice', fallbackTitle: 'Invoices List', icon: 'mdi-file-document-multiple' },
      { name: 'supplier_settlement', titleKey: 'supplier_settlement', fallbackTitle: 'Supplier Settlement', icon: 'mdi-cash-register' },
    ]
  },
  {
    key: 'billing_client',
    sectionKey: 'client_invoics',
    fallbackSection: 'Client Invoices',
    color: '#00838F',
    icon: 'mdi-file-document-multiple',
    routes: [
      { name: 'list_client_invoice', titleKey: 'list_client_invoice', fallbackTitle: 'Invoices List', icon: 'mdi-file-document-multiple' },
      { name: 'client_settlement', titleKey: 'client_settlement', fallbackTitle: 'Client Settlement', icon: 'mdi-cash-register' },
      { name: 'order_transfers_page', titleKey: 'order_transfers', fallbackTitle: 'Order Transfers', icon: 'mdi-bank-transfer' },
    ]
  },
  {
    key: 'charges',
    sectionKey: 'charges',
    fallbackSection: 'Charges',
    color: '#42A5F5',
    icon: 'mdi-truck-cargo-container',
    routes: [
      { name: 'list_charge', titleKey: 'list_charge', fallbackTitle: 'Charges List', icon: 'mdi-truck-cargo-container' },
      { name: 'add_charge', titleKey: 'add_charge', fallbackTitle: 'Add Charge', icon: 'mdi-plus-circle-outline' },
      { name: 'edit_charge', titleKey: 'edit', fallbackTitle: 'Edit Charge', icon: 'mdi-pencil-outline' },
    ]
  },
  {
    key: 'production',
    sectionKey: 'production',
    fallbackSection: 'Production',
    color: '#00897B',
    icon: 'mdi-factory',
    routes: [
      { name: 'production_list', titleKey: 'production_list', fallbackTitle: 'Production List', icon: 'mdi-factory' },
      { name: 'add_production', titleKey: 'add_production', fallbackTitle: 'Add Production', icon: 'mdi-plus-circle-outline' },
      { name: 'charge_production_management', titleKey: 'charge_production_management', fallbackTitle: 'Linked Charges', icon: 'mdi-link-variant' },
    ]
  },
  {
    key: 'shippings',
    sectionKey: 'shippings',
    fallbackSection: 'Shippings',
    color: '#4643a0',
    icon: 'mdi-package-variant-closed',
    routes: [
      { name: 'list_shipping', titleKey: 'list_shipping', fallbackTitle: 'Shippings List', icon: 'mdi-package-variant-closed' },
      { name: 'add_shipping', titleKey: 'add_shipping', fallbackTitle: 'Add Shipping', icon: 'mdi-plus-circle-outline' },
      { name: 'edit_shipping', titleKey: 'edit', fallbackTitle: 'Edit Shipping', icon: 'mdi-pencil-outline' },
    ]
  },
  {
    key: 'warehouses',
    sectionKey: 'warehouses',
    fallbackSection: 'Warehouses',
    color: '#a21ee5',
    icon: 'mdi-warehouse',
    routes: [
      { name: 'warehouses', titleKey: 'dashboard', fallbackTitle: 'Warehouse Dashboard', icon: 'mdi-warehouse' },
      { name: 'storage_invoices', titleKey: 'storage_invoices', fallbackTitle: 'Storage Invoices', icon: 'mdi-receipt-text-outline' },
    ]
  },
  {
    key: 'inventory',
    sectionKey: 'inventory',
    fallbackSection: 'Inventory',
    color: '#6D4C41',
    icon: 'mdi-clipboard-text-clock-outline',
    routes: [
      { name: 'list_inventory', titleKey: 'list_inventory', fallbackTitle: 'Inventory List', icon: 'mdi-clipboard-text-clock-outline' },
      { name: 'add_inventory', titleKey: 'add_inventory', fallbackTitle: 'Add Inventory', icon: 'mdi-sticker-plus-outline' },
      { name: 'edit_inventory', titleKey: 'edit', fallbackTitle: 'Edit Inventory', icon: 'mdi-pencil-outline' },
    ]
  },
  {
    key: 'warehouse_transfers',
    sectionKey: 'warehouse_transfers',
    fallbackSection: 'Warehouse Transfers',
    color: '#F9A825',
    icon: 'mdi-swap-horizontal',
    routes: [
      { name: 'list_warehouse_transfer', titleKey: 'list_warehouse_transfer', fallbackTitle: 'Transfers List', icon: 'mdi-swap-horizontal' },
      { name: 'add_warehouse_transfer', titleKey: 'add_warehouse_transfer', fallbackTitle: 'Add Transfer', icon: 'mdi-plus-circle-outline' },
      { name: 'edit_warehouse_transfer', titleKey: 'edit', fallbackTitle: 'Edit Transfer', icon: 'mdi-pencil-outline' },
    ]
  },
  {
    key: 'workforce',
    sectionKey: 'workforce',
    fallbackSection: 'Workforce',
    color: '#1565C0',
    icon: 'mdi-account-multiple',
    routes: [
      { name: 'workforce_management', titleKey: 'workforce_management', fallbackTitle: 'Workforce', icon: 'mdi-account-multiple' },
      { name: 'fortnight_management', titleKey: 'fortnight_management', fallbackTitle: 'Fortnight Management', icon: 'mdi-calendar-month' },
      { name: 'corporal_management', titleKey: 'corporal_management', fallbackTitle: 'Corporals', icon: 'mdi-account-tie' },
      { name: 'team_management', titleKey: 'team_management', fallbackTitle: 'Teams', icon: 'mdi-account-group' },
      { name: 'section_management', titleKey: 'section_management', fallbackTitle: 'Sections', icon: 'mdi-sitemap-outline' },
      { name: 'workforce_absence_movement', titleKey: 'workforce_absence_movement_title', fallbackTitle: 'Absence & Movements', icon: 'mdi-account-clock-outline' },
      { name: 'workforce_statistics', titleKey: 'workforce_statistics', fallbackTitle: 'Statistics', icon: 'mdi-chart-bar' },
    ]
  },
  {
    key: 'core_analytics',
    sectionKey: 'core_analytics',
    fallbackSection: 'Analytics & Core',
    color: '#1E88E5',
    icon: 'mdi-chart-box-outline',
    routes: [
      { name: 'index', titleKey: 'home', fallbackTitle: 'Home', icon: 'mdi-home-variant-outline' },
      { name: 'main_dashboard', titleKey: 'main_dashboard', fallbackTitle: 'Main Dashboard', icon: 'mdi-chart-box-outline' },
      { name: 'jupiter_dashboard', titleKey: 'jupiter_dashboard', fallbackTitle: 'Jupiter Dashboard', icon: 'mdi-orbit-variant' },
      { name: 'user_statistics', titleKey: 'user_statistics', fallbackTitle: 'User Statistics', icon: 'mdi-chart-timeline-variant-shimmer' },
      { name: 'ai_prompt', titleKey: 'ai_prompt', fallbackTitle: 'AI Prompt', icon: 'mdi-robot' },
    ]
  },
  {
    key: 'system_administration',
    sectionKey: 'system_administration',
    fallbackSection: 'System & Administration',
    color: '#5C6BC0',
    icon: 'mdi-cog-outline',
    routes: [
      { name: 'admin_users', titleKey: 'user_management', fallbackTitle: 'User Management', icon: 'mdi-shield-account' },
      { name: 'profile_settings', titleKey: 'my_profile', fallbackTitle: 'My Profile', icon: 'mdi-account-cog-outline' },
      { name: 'warehouse_management', titleKey: 'warehouse_management', fallbackTitle: 'Warehouses Setting', icon: 'mdi-warehouse' },
      { name: 'fish_management', titleKey: 'fish_management', fallbackTitle: 'Fish Setting', icon: 'mdi-fish' },
      { name: 'charge_management', titleKey: 'charge_management', fallbackTitle: 'Charges Setting', icon: 'mdi-shape-outline' },
      { name: 'shipping_management', titleKey: 'shipping_management', fallbackTitle: 'Shipping Setting', icon: 'mdi-tag-multiple-outline' },
      { name: 'production_management', titleKey: 'production_management', fallbackTitle: 'Production Setting', icon: 'mdi-cog-outline' },
      { name: 'detail_inventory_category_management', titleKey: 'detail_inventory_category_management', fallbackTitle: 'Inventory Categories', icon: 'mdi-clipboard-text-clock-outline' },
      { name: 'warehouse_transfer_category_management', titleKey: 'warehouse_transfer_category_management', fallbackTitle: 'Transfer Categories', icon: 'mdi-swap-horizontal' },
      { name: 'guano_management', titleKey: 'guano_management', fallbackTitle: 'Guano Setting', icon: 'mdi-leaf' },
      { name: 'package_management', titleKey: 'package_management', fallbackTitle: 'Package Setting', icon: 'mdi-package-variant-closed' },
      { name: 'bank_name_management', titleKey: 'bank_name_management', fallbackTitle: 'Bank Names', icon: 'mdi-bank' },
      { name: 'company_settings', titleKey: 'company_settings', fallbackTitle: 'Company Settings', icon: 'mdi-domain' },
      { name: 'fortnight_settings', titleKey: 'fortnight_settings', fallbackTitle: 'Fortnight Settings', icon: 'mdi-calendar-clock' },
      { name: 'chart_management', titleKey: 'chart_management', fallbackTitle: 'Custom Charts', icon: 'mdi-chart-areaspline' },
      { name: 'static_charge_management', titleKey: 'static_charge_management', fallbackTitle: 'Static Charges', icon: 'mdi-cash-lock-open' },
      { name: 'cost_configuration_management', titleKey: 'cost_configuration_management', fallbackTitle: 'Cost Configuration', icon: 'mdi-calculator-variant-outline' },
      { name: 'client_management', titleKey: 'client_management', fallbackTitle: 'Client Management', icon: 'mdi-account-tie' },
      { name: 'supplier_management', titleKey: 'supplier_management', fallbackTitle: 'Supplier Management', icon: 'mdi-truck-delivery' },
    ]
  }
];

function translate(key, fallback) {
  if (i18n?.global?.te && i18n.global.te(key)) {
    const val = i18n.global.t(key);
    if (val && val !== key) return val;
  }
  return fallback || key;
}

/**
 * Returns metadata for a route: { title, section, icon, color, moduleKey, siblings }
 */
export function getRouteMetadata(route) {
  const routeName = typeof route === 'string' ? route : route?.name;
  if (!routeName) {
    return {
      title: 'Page',
      section: 'App',
      icon: 'mdi-file-document-outline',
      color: '#1E88E5',
      moduleKey: null,
      siblings: []
    };
  }

  // Look for route inside MODULE_DEFINITIONS
  for (const mod of MODULE_DEFINITIONS) {
    const match = mod.routes.find(r => r.name === routeName);
    if (match) {
      const section = translate(mod.sectionKey, mod.fallbackSection);
      const title = translate(match.titleKey, match.fallbackTitle);
      const siblings = mod.routes
        .filter(r => r.name !== routeName)
        .map(r => ({
          name: r.name,
          title: translate(r.titleKey, r.fallbackTitle),
          icon: r.icon,
          color: mod.color,
        }));

      return {
        title,
        section,
        icon: match.icon,
        color: mod.color,
        moduleKey: mod.key,
        siblings
      };
    }
  }

  // Fallback for unmapped routes
  const formatted = String(routeName)
    .replace(/[_-]/g, ' ')
    .replace(/\b\w/g, l => l.toUpperCase());

  return {
    title: formatted,
    section: 'Jupiter',
    icon: 'mdi-file-document-outline',
    color: '#1E88E5',
    moduleKey: null,
    siblings: []
  };
}
