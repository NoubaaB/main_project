/**
 * tableConfigMixin.js
 *
 * Universal mixin for rich datatables across Charge, Production, and Shipping.
 * Features:
 * - Interactive Column Resizing (with visual resizer handle and mouse drag).
 * - PIN / UNPIN Columns (left, right, none) with pixel-perfect sticky positioning & drop shadows.
 * - Multi-column Sorting (Shift+Click or dedicated toggle, priority badges, active sorts bar).
 * - Expand / Collapse All Details toggle.
 * - LocalStorage Persistence & Restoration of column order, widths, pin states, and sorts.
 */

export default {
  data() {
    return {
      isColResizing: false,
      activeResizeCol: null,
      resizeStartX: 0,
      resizeStartWidth: 0,
      tableConfigMenu: false,
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
        actions: this.getActionsColWidth(),
      };
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
      return order.map(k => map[k]).filter(Boolean);
    },
    isAllExpanded() {
      const items = this.colFilteredItems || [];
      if (!items.length) return false;
      return items.every(i => Boolean(this.expanded && this.expanded[i.id]));
    },
    expandedCount() {
      return Object.values(this.expanded || {}).filter(Boolean).length;
    },
  },
  methods: {
    getActionsColWidth() {
      if (this.tableModuleKey === 'production') return 145;
      if (this.tableModuleKey === 'shipping') return 165;
      return 170; // charge
    },
    getDefaultColWidth(key) {
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
      const isDark = this.main?.is_dark;
      const bg = isDark ? '#1e293b' : '#ffffff';
      const thBg = isDark ? '#1c1f28' : '#f8fafc';
      const currentBg = zIndex >= 3 ? thBg : bg;

      if (key === '__checkbox') {
        return {
          position: 'sticky',
          left: '0px',
          zIndex: zIndex + 2,
          background: currentBg,
          width: '50px',
          minWidth: '50px',
          maxWidth: '50px',
        };
      }

      if (key === 'expand') {
        const isLastLeft = !this.hasLeftPinnedColumns;
        return {
          position: 'sticky',
          left: '50px',
          zIndex: zIndex + 1,
          background: currentBg,
          width: '40px',
          minWidth: '40px',
          maxWidth: '40px',
          boxShadow: isLastLeft ? '3px 0 6px -2px rgba(0, 0, 0, 0.12)' : 'none',
        };
      }

      if (key === 'actions') {
        const isFirstRight = !this.hasRightPinnedColumns;
        const actionsW = this.getActionsColWidth();
        return {
          position: 'sticky',
          right: '0px',
          zIndex: zIndex + 1,
          background: currentBg,
          width: actionsW + 'px',
          minWidth: actionsW + 'px',
          maxWidth: actionsW + 'px',
          boxShadow: isFirstRight ? '-3px 0 6px -2px rgba(0, 0, 0, 0.12)' : 'none',
        };
      }

      const side = this.pinSide(key);
      const w = this.getColWidth(key);

      if (!side) {
        return {
          width: w + 'px',
          minWidth: w + 'px',
          maxWidth: w + 'px',
        };
      }

      if (side === 'left') {
        const isLastLeft = this.isLastLeftPinnedCol(key);
        return {
          position: 'sticky',
          left: this.getPinLeft(key) + 'px',
          zIndex: zIndex,
          background: currentBg,
          width: w + 'px',
          minWidth: w + 'px',
          maxWidth: w + 'px',
          boxShadow: isLastLeft ? '3px 0 6px -2px rgba(0, 0, 0, 0.12)' : 'none',
        };
      }

      if (side === 'right') {
        const isFirstRight = this.isFirstRightPinnedCol(key);
        return {
          position: 'sticky',
          right: this.getPinRight(key) + 'px',
          zIndex: zIndex,
          background: currentBg,
          width: w + 'px',
          minWidth: w + 'px',
          maxWidth: w + 'px',
          boxShadow: isFirstRight ? '-3px 0 6px -2px rgba(0, 0, 0, 0.12)' : 'none',
        };
      }

      return {
        width: w + 'px',
        minWidth: w + 'px',
        maxWidth: w + 'px',
      };
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
      this.dispatchWorkerFilter(0);
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
      this.dispatchWorkerFilter(0);
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
      this.dispatchWorkerFilter(0);
    },
    removeColSort(key) {
      this.colSorts = (this.colSorts || []).filter(s => s.key !== key);
      this.saveTableConfig();
      this.dispatchWorkerFilter(0);
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
    // Drag & Drop Column Reordering
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
          colWidths: this.colWidths,
          pinState: this.pinState,
          colSorts: this.colSorts,
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
            this.colWidths = { ...this.defaultColWidths };
          }
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
          this.colWidths = { ...this.defaultColWidths, ...config.colWidths };
        } else {
          this.colWidths = { ...this.defaultColWidths };
        }

        if (config.pinState && typeof config.pinState === 'object') {
          this.pinState = config.pinState;
        }

        if (config.colSorts && Array.isArray(config.colSorts)) {
          this.colSorts = config.colSorts;
        }
      } catch (e) {
        console.error('Failed to load table configuration:', e);
      }
    },
    resetTableConfig() {
      try {
        localStorage.removeItem(this.storageConfigKey);
        this.columnOrder = this.allHeaders.filter(h => !h.fixed).map(h => h.key);
        this.colWidths = { ...this.defaultColWidths };
        this.pinState = {};
        this.colSorts = [];
        this.saveTableConfig();
        this.dispatchWorkerFilter(0);
        if (this.main && this.main.add_snackbar) {
          this.main.add_snackbar({
            show: true,
            message: this.$t('table_config_reset_success'),
            color: 'info',
          });
        }
      } catch (e) {
        console.error('Failed to reset table configuration:', e);
      }
    },
  },
};
