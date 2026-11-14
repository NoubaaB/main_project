/**
 * tableConfigMixin.js
 *
 * Universal mixin for rich datatables across Charge, Production, and Shipping.
 * Features:
 * - Interactive Column Resizing (with visual resizer handle and mouse drag).
 * - PIN / UNPIN Columns (left, right, none) with pixel-perfect sticky positioning & drop shadows.
 * - Multi-column Sorting (Shift+Click or dedicated toggle, priority badges, active sorts bar).
 * - Expand / Collapse All Details toggle.
 * - Expand View (Fullscreen Workspace mode with live KPIs, search, density switcher, exit on ESC).
 * - Show / Hide Columns manager with search, show all, hide all, persistence.
 * - Drag-and-drop & Click-to-reorder columns.
 * - Table Density controls (Comfortable / Compact / Dense).
 * - Full CSV & Excel export (All Filtered vs Selected).
 * - LocalStorage Persistence & Restoration of column order, widths, pin states, sorts, visibility, and density.
 */

import * as XLSX from 'xlsx';

export default {
  data() {
    return {
      isColResizing: false,
      activeResizeCol: null,
      resizeStartX: 0,
      resizeStartWidth: 0,
      tableConfigMenu: false,
      isExpandedView: false,
      hiddenColumns: [],
      tableDensity: 'compact',
      colSearchQuery: '',
      exportMenuOpen: false,
    };
  },
  computed: {
    storageConfigKey() {
      return `blueprintfish_table_config_${this.tableModuleKey || 'default'}`;
    },
    defaultColWidths() {
      return {
        __checkbox: 50,
        expand: 40,
        date: 125,
        charge_category_id: 160,
        production_category_id: 160,
        shipping_category_id: 160,
        _suppliers: 160,
        _clients: 160,
        _client_name: 160,
        _supplier_name: 160,
        _tracking: 150,
        _onp_tracking: 150,
        _origin: 130,
        _fishery: 130,
        _ship: 130,
        _trucks: 140,
        total_weight: 130,
        _total_weight_invoice: 140,
        total_price: 140,
        _invoice_state: 130,
        _qc_status: 120,
        direction: 110,
        production_type: 130,
        _charge: 140,
        // Invoices
        invoice_number: 160,
        _tracking_numbers: 180,
        print_counter: 120,
        // Inventory
        inventory_tracking_numbers: 180,
        _details_count: 120,
        // Warehouse Transfers
        transfer_number: 160,
        transfer_tracking_number: 180,
        from_warehouse: 160,
        to_warehouse: 160,
        // Order Transfers
        amount: 140,
        currency: 100,
        _beneficiaries: 200,
        motif: 220,
        // Warehouse Stock Par Lot
        lot_number: 205,
        status: 145,
        supplier: 220,
        chamber: 155,
        species: 215,
        rem_weight: 195,
        boxes: 130,
        valuation: 185,
        actions: this.getActionsColWidth(),
      };
    },
    allToggleableColumns() {
      return (this.allHeaders || []).filter(h => !h.fixed);
    },
    headers() {
      const all = this.allHeaders || [];
      const hiddenSet = new Set(this.hiddenColumns || []);
      if (!this.columnOrder) {
        return all.filter(h => h.fixed || !hiddenSet.has(h.key));
      }
      const map = Object.fromEntries(all.map(h => [h.key, h]));
      const fixed = all.filter(h => h.fixed);
      const ordered = this.columnOrder
        .filter(k => map[k] && !map[k].fixed && !hiddenSet.has(k))
        .map(k => map[k]);
      const pinState = this.pinState || {};
      return [
        fixed[0],
        ...ordered.filter(h => pinState[h.key] === 'left'),
        ...ordered.filter(h => !pinState[h.key]),
        ...ordered.filter(h => pinState[h.key] === 'right'),
        fixed[fixed.length - 1],
      ].filter(Boolean);
    },
    hasLeftPinnedColumns() {
      return (this.headers || []).some(h => !h.fixed && this.pinState && this.pinState[h.key] === 'left');
    },
    hasRightPinnedColumns() {
      return (this.headers || []).some(h => !h.fixed && this.pinState && this.pinState[h.key] === 'right');
    },
    configColumns() {
      const map = Object.fromEntries((this.allHeaders || []).map(h => [h.key, h]));
      const order = this.columnOrder || (this.allHeaders || []).filter(h => !h.fixed).map(h => h.key);
      let cols = order.map(k => map[k]).filter(Boolean);
      if (this.colSearchQuery && this.colSearchQuery.trim()) {
        const q = this.colSearchQuery.trim().toLowerCase();
        cols = cols.filter(c => (c.title || c.key || '').toLowerCase().includes(q));
      }
      return cols;
    },
    visibleToggleableColumnsCount() {
      const toggleable = this.allToggleableColumns;
      const hidden = new Set(this.hiddenColumns || []);
      return toggleable.filter(t => !hidden.has(t.key)).length;
    },
    isAllExpanded() {
      const items = this.colFilteredItems || [];
      if (!items.length) return false;
      if (this.expandedCount < items.length) return false;
      return items.every(i => Boolean(this.expanded && this.expanded[i.id]));
    },
    expandedCount() {
      if (!this.expanded) return 0;
      let count = 0;
      for (const k in this.expanded) {
        if (this.expanded[k]) count++;
      }
      return count;
    },
    headerPinStyles() {
      const bg = 'var(--table-pin-bg, #ffffff)';
      const actionsW = this.getActionsColWidth ? this.getActionsColWidth() : 92;
      const headers = this.headers || [];
      const pinState = this.pinState || {};
      const colWidths = this.colWidths || {};

      const leftCols = headers.filter(h => !h.fixed && pinState[h.key] === 'left');
      const rightCols = headers.filter(h => !h.fixed && pinState[h.key] === 'right');
      const lastLeftKey = leftCols.length > 0 ? leftCols[leftCols.length - 1].key : null;
      const firstRightKey = rightCols.length > 0 ? rightCols[0].key : null;
      const hasExpand = headers.some(h => h.key === 'expand');

      const styles = {
        __checkbox: {
          position: 'sticky',
          left: '0px',
          zIndex: 4,
          background: bg,
          width: '50px',
          minWidth: '50px',
          maxWidth: '50px',
          boxShadow: !hasExpand && !lastLeftKey ? 'var(--table-pin-shadow-left, 3px 0 6px -2px rgba(0, 0, 0, 0.12))' : 'none',
        },
        expand: {
          position: 'sticky',
          left: '50px',
          zIndex: 3,
          background: bg,
          width: '40px',
          minWidth: '40px',
          maxWidth: '40px',
          boxShadow: !lastLeftKey ? 'var(--table-pin-shadow-left, 3px 0 6px -2px rgba(0, 0, 0, 0.12))' : 'none',
        },
        actions: {
          position: 'sticky',
          right: '0px',
          zIndex: 3,
          background: bg,
          width: actionsW + 'px',
          minWidth: actionsW + 'px',
          boxShadow: !firstRightKey ? 'var(--table-pin-shadow-right, -3px 0 8px -2px rgba(0, 0, 0, 0.12))' : 'none',
        },
      };

      let leftOffset = hasExpand ? 90 : 50;
      for (let i = 0; i < headers.length; i++) {
        const h = headers[i];
        const key = h.key;
        if (key === '__checkbox' || key === 'expand' || key === 'actions') continue;
        const side = pinState[key];
        const w = colWidths[key] || this.getDefaultColWidth(key);
        if (side === 'left') {
          styles[key] = {
            position: 'sticky',
            left: leftOffset + 'px',
            zIndex: 2,
            background: bg,
            width: w + 'px',
            minWidth: w + 'px',
            maxWidth: w + 'px',
            boxShadow: key === lastLeftKey ? 'var(--table-pin-shadow-left, 3px 0 6px -2px rgba(0, 0, 0, 0.12))' : 'none',
          };
          leftOffset += w;
        } else if (!side) {
          styles[key] = {
            width: w + 'px',
            minWidth: w + 'px',
            maxWidth: w + 'px',
          };
        }
      }

      let rightOffset = actionsW;
      for (let i = headers.length - 1; i >= 0; i--) {
        const h = headers[i];
        const key = h.key;
        if (key === '__checkbox' || key === 'expand' || key === 'actions') continue;
        const side = pinState[key];
        if (side === 'right') {
          const w = colWidths[key] || this.getDefaultColWidth(key);
          styles[key] = {
            position: 'sticky',
            right: rightOffset + 'px',
            zIndex: 2,
            background: bg,
            width: w + 'px',
            minWidth: w + 'px',
            maxWidth: w + 'px',
            boxShadow: key === firstRightKey ? 'var(--table-pin-shadow-right, -3px 0 8px -2px rgba(0, 0, 0, 0.12))' : 'none',
          };
          rightOffset += w;
        }
      }

      return styles;
    },
    thPinStyles() {
      const thBg = 'var(--table-pin-th-bg, #f8fafc)';
      const actionsW = this.getActionsColWidth ? this.getActionsColWidth() : 92;
      const headers = this.headers || [];
      const pinState = this.pinState || {};
      const colWidths = this.colWidths || {};

      const leftCols = headers.filter(h => !h.fixed && pinState[h.key] === 'left');
      const rightCols = headers.filter(h => !h.fixed && pinState[h.key] === 'right');
      const lastLeftKey = leftCols.length > 0 ? leftCols[leftCols.length - 1].key : null;
      const firstRightKey = rightCols.length > 0 ? rightCols[0].key : null;
      const hasExpand = headers.some(h => h.key === 'expand');

      const styles = {
        __checkbox: {
          position: 'sticky',
          top: '0px',
          left: '0px',
          zIndex: 8,
          background: thBg,
          width: '50px',
          minWidth: '50px',
          maxWidth: '50px',
          boxShadow: !hasExpand && !lastLeftKey ? 'var(--table-pin-shadow-left, 3px 0 6px -2px rgba(0, 0, 0, 0.12))' : 'none',
        },
        expand: {
          position: 'sticky',
          top: '0px',
          left: '50px',
          zIndex: 7,
          background: thBg,
          width: '40px',
          minWidth: '40px',
          maxWidth: '40px',
          boxShadow: !lastLeftKey ? 'var(--table-pin-shadow-left, 3px 0 6px -2px rgba(0, 0, 0, 0.12))' : 'none',
        },
        actions: {
          position: 'sticky',
          top: '0px',
          right: '0px',
          zIndex: 7,
          background: thBg,
          width: actionsW + 'px',
          minWidth: actionsW + 'px',
          boxShadow: !firstRightKey ? 'var(--table-pin-shadow-right, -3px 0 8px -2px rgba(0, 0, 0, 0.12))' : 'none',
        },
      };

      let leftOffset = hasExpand ? 90 : 50;
      for (let i = 0; i < headers.length; i++) {
        const h = headers[i];
        const key = h.key;
        if (key === '__checkbox' || key === 'expand' || key === 'actions') continue;
        const side = pinState[key];
        const w = colWidths[key] || this.getDefaultColWidth(key);
        if (side === 'left') {
          styles[key] = {
            position: 'sticky',
            top: '0px',
            left: leftOffset + 'px',
            zIndex: 6,
            background: thBg,
            width: w + 'px',
            minWidth: w + 'px',
            maxWidth: w + 'px',
            boxShadow: key === lastLeftKey ? 'var(--table-pin-shadow-left, 3px 0 6px -2px rgba(0, 0, 0, 0.12))' : 'none',
          };
          leftOffset += w;
        } else if (!side) {
          styles[key] = {
            position: 'sticky',
            top: '0px',
            zIndex: 4,
            background: thBg,
            width: w + 'px',
            minWidth: w + 'px',
            maxWidth: w + 'px',
          };
        }
      }

      let rightOffset = actionsW;
      for (let i = headers.length - 1; i >= 0; i--) {
        const h = headers[i];
        const key = h.key;
        if (key === '__checkbox' || key === 'expand' || key === 'actions') continue;
        const side = pinState[key];
        if (side === 'right') {
          const w = colWidths[key] || this.getDefaultColWidth(key);
          styles[key] = {
            position: 'sticky',
            top: '0px',
            right: rightOffset + 'px',
            zIndex: 6,
            background: thBg,
            width: w + 'px',
            minWidth: w + 'px',
            maxWidth: w + 'px',
            boxShadow: key === firstRightKey ? 'var(--table-pin-shadow-right, -3px 0 8px -2px rgba(0, 0, 0, 0.12))' : 'none',
          };
          rightOffset += w;
        }
      }

      return styles;
    },
  },
  mounted() {
    window.addEventListener('keydown', this.handleTableGlobalKeydown);
  },
  beforeUnmount() {
    window.removeEventListener('keydown', this.handleTableGlobalKeydown);
    if (this.isExpandedView) {
      document.body.classList.remove('table-expanded-active');
      document.documentElement.classList.remove('table-expanded-active');
      document.body.style.overflow = '';
    }
  },
  methods: {
    handleTableGlobalKeydown(e) {
      if (e.key === 'Escape' && this.isExpandedView) {
        this.exitExpandedView();
      }
    },
    toggleExpandedView() {
      this.isExpandedView = !this.isExpandedView;
      if (this.isExpandedView) {
        document.body.classList.add('table-expanded-active');
        document.documentElement.classList.add('table-expanded-active');
        document.body.style.overflow = 'hidden';
      } else {
        document.body.classList.remove('table-expanded-active');
        document.documentElement.classList.remove('table-expanded-active');
        document.body.style.overflow = '';
      }
      this.$nextTick(() => {
        window.dispatchEvent(new Event('resize'));
        if (this.$refs.tableWrap) {
          this.tableContainerHeight = this.$refs.tableWrap.clientHeight || 600;
        }
      });
    },
    exitExpandedView() {
      if (this.isExpandedView) {
        this.isExpandedView = false;
        document.body.classList.remove('table-expanded-active');
        document.documentElement.classList.remove('table-expanded-active');
        document.body.style.overflow = '';
        this.$nextTick(() => {
          window.dispatchEvent(new Event('resize'));
          if (this.$refs.tableWrap) {
            this.tableContainerHeight = this.$refs.tableWrap.clientHeight || 600;
          }
        });
      }
    },
    setTableDensity(density) {
      if (!['comfortable', 'compact', 'dense'].includes(density)) return;
      this.tableDensity = density;
      if (density === 'comfortable') {
        this.rowHeight = 52;
      } else if (density === 'dense') {
        this.rowHeight = 34;
      } else {
        this.rowHeight = 42;
      }
      this.saveTableConfig();
      this.$nextTick(() => {
        window.dispatchEvent(new Event('resize'));
      });
    },
    toggleColumnVisibility(key) {
      if (!key) return;
      const current = [...(this.hiddenColumns || [])];
      const idx = current.indexOf(key);
      if (idx > -1) {
        current.splice(idx, 1);
      } else {
        const toggleableKeys = this.allToggleableColumns.map(h => h.key);
        const remainingVisible = toggleableKeys.filter(k => !current.includes(k) && k !== key);
        if (remainingVisible.length === 0) {
          if (this.main?.add_snackbar) {
            this.main.add_snackbar({
              show: true,
              message: this.$t('at_least_one_column_required') || 'At least one column must remain visible',
              color: 'warning',
            });
          }
          return;
        }
        current.push(key);
      }
      this.hiddenColumns = current;
      this.saveTableConfig();
      this.$nextTick(() => {
        window.dispatchEvent(new Event('resize'));
        if (this.measureColWidths) this.measureColWidths();
      });
    },
    isColumnVisible(key) {
      return !(this.hiddenColumns || []).includes(key);
    },
    showAllColumns() {
      this.hiddenColumns = [];
      this.saveTableConfig();
      this.$nextTick(() => {
        window.dispatchEvent(new Event('resize'));
        if (this.measureColWidths) this.measureColWidths();
      });
    },
    hideAllColumns() {
      const toggleableKeys = this.allToggleableColumns.map(h => h.key);
      if (toggleableKeys.length > 1) {
        this.hiddenColumns = toggleableKeys.slice(1);
      }
      this.saveTableConfig();
      this.$nextTick(() => {
        window.dispatchEvent(new Event('resize'));
        if (this.measureColWidths) this.measureColWidths();
      });
    },
    moveColumn(key, direction) {
      const order = [...(this.columnOrder || this.allToggleableColumns.map(h => h.key))];
      const idx = order.indexOf(key);
      if (idx === -1) return;
      const targetIdx = direction === 'up' ? idx - 1 : idx + 1;
      if (targetIdx < 0 || targetIdx >= order.length) return;
      const [moved] = order.splice(idx, 1);
      order.splice(targetIdx, 0, moved);
      this.columnOrder = order;
      this.saveTableConfig();
      this.$nextTick(() => {
        window.dispatchEvent(new Event('resize'));
      });
    },
    moveColumnUp(key) {
      this.moveColumn(key, 'up');
    },
    moveColumnDown(key) {
      this.moveColumn(key, 'down');
    },
    onColumnManagerReorder({ fromKey, toKey }) {
      if (!fromKey || !toKey || fromKey === toKey) return;
      const order = [...(this.columnOrder || this.allToggleableColumns.map(h => h.key))];
      const fromIdx = order.indexOf(fromKey);
      const toIdx = order.indexOf(toKey);
      if (fromIdx > -1 && toIdx > -1) {
        order.splice(fromIdx, 1);
        order.splice(toIdx, 0, fromKey);
        this.columnOrder = order;
        this.saveTableConfig();
        this.$nextTick(() => {
          window.dispatchEvent(new Event('resize'));
        });
      }
    },
    getSelectedItems() {
      const list = this.charges || this.productions || this.shippings || [];
      const sel = new Set(this.selected || []);
      return list.filter(item => sel.has(item.id));
    },
    exportTableToExcel(filename, customItems) {
      try {
        const items = customItems || this.colFilteredItems || [];
        const visibleHeaders = (this.headers || []).filter(
          h => h.key !== 'actions' && h.key !== 'expand' && h.key !== '__checkbox'
        );
        const data = items.map(c => {
          const row = {};
          visibleHeaders.forEach(h => {
            row[h.title || h.key] = this.cellText ? this.cellText(h.key, c) : (c[h.key] != null ? String(c[h.key]) : '-');
          });
          return row;
        });
        const ws = XLSX.utils.json_to_sheet(data);
        const wb = XLSX.utils.book_new();
        const sheetName = (this.tableModuleKey || 'Data').charAt(0).toUpperCase() + (this.tableModuleKey || 'Data').slice(1);
        XLSX.utils.book_append_sheet(wb, ws, sheetName);
        const dateStr = this.today || new Date().toISOString().slice(0, 10);
        XLSX.writeFile(wb, filename || `${this.tableModuleKey || 'export'}_${dateStr}.xlsx`);
        if (this.main?.add_snackbar) {
          this.main.add_snackbar({
            show: true,
            message: this.$t('export_success') || 'Export successfully generated',
            color: 'success',
          });
        }
      } catch (e) {
        console.error('Failed to export Excel:', e);
        if (this.main?.add_snackbar) {
          this.main.add_snackbar({
            show: true,
            message: this.$t('error') || 'Export failed',
            color: 'error',
          });
        }
      }
    },
    exportTableToCsv(filename, customItems) {
      try {
        const items = customItems || this.colFilteredItems || [];
        const visibleHeaders = (this.headers || []).filter(
          h => h.key !== 'actions' && h.key !== 'expand' && h.key !== '__checkbox'
        );
        const headerTitles = visibleHeaders.map(h => `"${(h.title || h.key).replace(/"/g, '""')}"`);
        const rows = items.map(item => {
          return visibleHeaders.map(h => {
            const val = this.cellText ? this.cellText(h.key, item) : (item[h.key] != null ? String(item[h.key]) : '-');
            return `"${String(val).replace(/"/g, '""')}"`;
          }).join(',');
        });
        const csvContent = '\uFEFF' + [headerTitles.join(','), ...rows].join('\r\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        const dateStr = this.today || new Date().toISOString().slice(0, 10);
        link.setAttribute('href', url);
        link.setAttribute('download', filename || `${this.tableModuleKey || 'export'}_${dateStr}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
        if (this.main?.add_snackbar) {
          this.main.add_snackbar({
            show: true,
            message: this.$t('export_success') || 'Export successfully generated',
            color: 'success',
          });
        }
      } catch (e) {
        console.error('Failed to export CSV:', e);
        if (this.main?.add_snackbar) {
          this.main.add_snackbar({
            show: true,
            message: this.$t('error') || 'Export failed',
            color: 'error',
          });
        }
      }
    },
    exportSelectedToExcel() {
      const items = this.getSelectedItems();
      this.exportTableToExcel(null, items);
    },
    exportSelectedToCsv() {
      const items = this.getSelectedItems();
      this.exportTableToCsv(null, items);
    },
    getActionsColWidth() {
      if (this.tableModuleKey === 'warehouse') return 115;
      return 92;
    },
    getDefaultColWidth(key) {
      if (key === 'actions') return this.getActionsColWidth();
      return this.defaultColWidths[key] || 135;
    },
    getColWidth(key) {
      if (key === '__checkbox') return 50;
      if (key === 'expand') return 40;
      if (key === 'actions') return this.getActionsColWidth();
      return (this.colWidths && this.colWidths[key]) || this.getDefaultColWidth(key);
    },
    getHeaderTitle(key) {
      const h = (this.allHeaders || []).find(x => x.key === key);
      return h ? h.title : key;
    },
    isLastLeftPinnedCol(key) {
      const leftCols = (this.headers || []).filter(h => !h.fixed && this.pinState && this.pinState[h.key] === 'left');
      return leftCols.length > 0 && leftCols[leftCols.length - 1].key === key;
    },
    isFirstRightPinnedCol(key) {
      const rightCols = (this.headers || []).filter(h => !h.fixed && this.pinState && this.pinState[h.key] === 'right');
      return rightCols.length > 0 && rightCols[0].key === key;
    },
    getPinLeft(key) {
      let offset = 50 + 40; // checkbox (50) + expand (40)
      for (const h of (this.headers || [])) {
        if (h.key === key) break;
        if (!h.fixed && this.pinState && this.pinState[h.key] === 'left') {
          offset += this.getColWidth(h.key);
        }
      }
      return offset;
    },
    getPinRight(key) {
      let offset = this.getActionsColWidth();
      const rev = [...(this.headers || [])].reverse();
      for (const h of rev) {
        if (h.key === key) break;
        if (!h.fixed && this.pinState && this.pinState[h.key] === 'right') {
          offset += this.getColWidth(h.key);
        }
      }
      return offset;
    },
    colPinStyle(key, zIndex = 2) {
      if (zIndex >= 3) {
        return (this.thPinStyles && this.thPinStyles[key]) || { width: this.getColWidth(key) + 'px' };
      }
      return (this.headerPinStyles && this.headerPinStyles[key]) || { width: this.getColWidth(key) + 'px' };
    },
    // Column Resizing
    startColResize(key, e) {
      if (!key || key === 'actions' || key === 'expand') return;
      e.preventDefault();
      e.stopPropagation();

      this.isColResizing = true;
      this.activeResizeCol = key;
      this.resizeStartX = e.pageX || e.clientX;
      this.resizeStartWidth = this.getColWidth(key);

      document.body.style.cursor = 'col-resize';
      document.body.style.userSelect = 'none';

      const onMouseMove = (moveEvent) => {
        if (!this.isColResizing) return;
        const currentX = moveEvent.pageX || moveEvent.clientX;
        const delta = currentX - this.resizeStartX;
        const newWidth = Math.max(70, Math.min(600, Math.round(this.resizeStartWidth + delta)));
        this.colWidths = {
          ...this.colWidths,
          [key]: newWidth,
        };
      };

      const onMouseUp = () => {
        this.isColResizing = false;
        this.activeResizeCol = null;
        document.body.style.cursor = '';
        document.body.style.userSelect = '';
        window.removeEventListener('mousemove', onMouseMove);
        window.removeEventListener('mouseup', onMouseUp);
        this.saveTableConfig();
        if (this.measureColWidths) this.measureColWidths();
      };

      window.addEventListener('mousemove', onMouseMove);
      window.addEventListener('mouseup', onMouseUp);
    },
    // Pinning
    setPin(key, side) {
      if (!key || key === 'actions' || key === 'expand') return;
      const next = { ...this.pinState };
      if (!side || side === 'none') {
        delete next[key];
      } else {
        next[key] = side;
      }
      this.pinState = next;
      this.saveTableConfig();
      if (this.measureColWidths) this.measureColWidths();
    },
    togglePin(key) {
      const cur = this.pinSide(key);
      if (!cur) this.setPin(key, 'left');
      else if (cur === 'left') this.setPin(key, 'right');
      else this.setPin(key, 'none');
    },
    isPinned(k) {
      return Boolean(this.pinState && this.pinState[k]);
    },
    pinSide(k) {
      return (this.pinState && this.pinState[k]) || null;
    },
    // Sorting (Multi-sort enabled)
    toggleSort(key, event) {
      const isMulti = Boolean(event && (event.shiftKey || event.ctrlKey || event.metaKey));
      const i = this.colSorts.findIndex(s => s.key === key);

      if (i === -1) {
        if (isMulti) {
          this.colSorts.push({ key, dir: 'asc' });
        } else {
          this.colSorts = [{ key, dir: 'asc' }];
        }
      } else if (this.colSorts[i].dir === 'asc') {
        this.colSorts[i].dir = 'desc';
      } else {
        this.colSorts.splice(i, 1);
      }
      this.saveTableConfig();
      if (this.dispatchWorkerFilter) this.dispatchWorkerFilter(0);
    },
    toggleMultiSort(key) {
      const i = this.colSorts.findIndex(s => s.key === key);
      if (i === -1) {
        this.colSorts.push({ key, dir: 'asc' });
      } else if (this.colSorts[i].dir === 'asc') {
        this.colSorts[i].dir = 'desc';
      } else {
        this.colSorts.splice(i, 1);
      }
      this.saveTableConfig();
      if (this.dispatchWorkerFilter) this.dispatchWorkerFilter(0);
    },
    sortDir(key) {
      const s = (this.colSorts || []).find(x => x.key === key);
      return s ? s.dir : null;
    },
    sortIdx(key) {
      const idx = (this.colSorts || []).findIndex(x => x.key === key);
      return idx >= 0 ? idx + 1 : null;
    },
    clearColSorts() {
      this.colSorts = [];
      this.saveTableConfig();
      if (this.dispatchWorkerFilter) this.dispatchWorkerFilter(0);
    },
    removeColSort(key) {
      this.colSorts = (this.colSorts || []).filter(s => s.key !== key);
      this.saveTableConfig();
      if (this.dispatchWorkerFilter) this.dispatchWorkerFilter(0);
    },
    // Expanding
    toggleExpand(id) {
      this.expanded[id] = !this.expanded[id];
      this.expanded = { ...this.expanded };
    },
    toggleExpandAll() {
      if (this.isAllExpanded) {
        this.expanded = {};
      } else {
        const exp = {};
        (this.colFilteredItems || []).forEach(item => {
          exp[item.id] = true;
        });
        this.expanded = exp;
      }
    },
    // Drag & Drop Column Reordering on Table Headers
    onDragStart(key) {
      this.dragCol = key;
    },
    onDragOver(key, e) {
      e.preventDefault();
      this.dragOverCol = key;
    },
    onDragEnd() {
      if (this.dragCol && this.dragOverCol && this.dragCol !== this.dragOverCol) {
        const order = [...(this.columnOrder || this.allHeaders.filter(h => !h.fixed).map(h => h.key))];
        const fromIdx = order.indexOf(this.dragCol);
        const toIdx = order.indexOf(this.dragOverCol);
        if (fromIdx > -1 && toIdx > -1) {
          order.splice(fromIdx, 1);
          order.splice(toIdx, 0, this.dragCol);
          this.columnOrder = order;
          this.saveTableConfig();
        }
      }
      this.dragCol = null;
      this.dragOverCol = null;
    },
    // LocalStorage Persistence
    saveTableConfig() {
      try {
        const config = {
          columnOrder: this.columnOrder,
          colWidths: {
            ...this.colWidths,
            actions: this.getActionsColWidth(),
          },
          pinState: this.pinState,
          colSorts: this.colSorts,
          hiddenColumns: this.hiddenColumns || [],
          tableDensity: this.tableDensity || 'compact',
        };
        localStorage.setItem(this.storageConfigKey, JSON.stringify(config));
      } catch (e) {
        console.error('Failed to save table configuration:', e);
      }
    },
    loadTableConfig() {
      try {
        const raw = localStorage.getItem(this.storageConfigKey);
        if (!raw) {
          if (!this.columnOrder) {
            this.columnOrder = this.allHeaders.filter(h => !h.fixed).map(h => h.key);
          }
          if (!this.colWidths || !Object.keys(this.colWidths).length) {
            this.colWidths = { ...this.defaultColWidths, actions: this.getActionsColWidth() };
          } else {
            this.colWidths.actions = this.getActionsColWidth();
          }
          this.hiddenColumns = [];
          this.tableDensity = 'compact';
          this.rowHeight = 42;
          return;
        }
        const config = JSON.parse(raw);
        const validKeys = new Set(this.allHeaders.filter(h => !h.fixed).map(h => h.key));

        if (config.columnOrder && Array.isArray(config.columnOrder)) {
          const filtered = config.columnOrder.filter(k => validKeys.has(k));
          validKeys.forEach(k => {
            if (!filtered.includes(k)) filtered.push(k);
          });
          this.columnOrder = filtered;
        } else {
          this.columnOrder = this.allHeaders.filter(h => !h.fixed).map(h => h.key);
        }

        if (config.colWidths && typeof config.colWidths === 'object') {
          this.colWidths = {
            ...this.defaultColWidths,
            ...config.colWidths,
            actions: this.getActionsColWidth(),
          };
        } else {
          this.colWidths = {
            ...this.defaultColWidths,
            actions: this.getActionsColWidth(),
          };
        }

        if (config.pinState && typeof config.pinState === 'object') {
          this.pinState = config.pinState;
        }

        if (config.colSorts && Array.isArray(config.colSorts)) {
          this.colSorts = config.colSorts;
        }

        if (config.hiddenColumns && Array.isArray(config.hiddenColumns)) {
          this.hiddenColumns = config.hiddenColumns.filter(k => validKeys.has(k));
        } else {
          this.hiddenColumns = [];
        }

        if (config.tableDensity && ['comfortable', 'compact', 'dense'].includes(config.tableDensity)) {
          this.tableDensity = config.tableDensity;
          if (this.tableDensity === 'comfortable') this.rowHeight = 52;
          else if (this.tableDensity === 'dense') this.rowHeight = 34;
          else this.rowHeight = 42;
        } else {
          this.tableDensity = 'compact';
          this.rowHeight = 42;
        }
      } catch (e) {
        console.error('Failed to load table configuration:', e);
      }
    },
    resetTableConfig() {
      try {
        localStorage.removeItem(this.storageConfigKey);
        this.columnOrder = this.allHeaders.filter(h => !h.fixed).map(h => h.key);
        this.colWidths = { ...this.defaultColWidths, actions: this.getActionsColWidth() };
        this.pinState = {};
        this.colSorts = [];
        this.hiddenColumns = [];
        this.tableDensity = 'compact';
        this.rowHeight = 42;
        this.saveTableConfig();
        if (this.dispatchWorkerFilter) this.dispatchWorkerFilter(0);
        if (this.main && this.main.add_snackbar) {
          this.main.add_snackbar({
            show: true,
            message: this.$t('table_config_reset_success'),
            color: 'info',
          });
        }
        this.$nextTick(() => {
          window.dispatchEvent(new Event('resize'));
        });
      } catch (e) {
        console.error('Failed to reset table configuration:', e);
      }
    },
  },
};
