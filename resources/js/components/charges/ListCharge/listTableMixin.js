// Shared mixin for column filters, sorting, pinning, and excel export
import * as XLSX from 'xlsx';

export default {
  data() {
    return {
      columnFilters: {},
      columnSorts: [], // [{key, dir:'asc'|'desc'}, ...]
      pinnedColumns: new Set(['data-table-select']),
      expandedRows: new Set(),
      filterMenuOpen: {},
    };
  },
  methods: {
    // -- Column Filtering --
    getCellText(key, item) {
      const slot = this.headers.find(h => h.key === key);
      if (!slot) return '';
      const v = item[key];
      if (v === null || v === undefined) return '-';
      if (Array.isArray(v)) return v.map(x => x.name || x).join(', ');
      if (typeof v === 'object' && v.name) return v.name;
      return String(v);
    },
    getFilterOptions(key) {
      const rows = this.preFilteredItems(key);
      const s = new Set();
      rows.forEach(r => s.add(this.getCellText(key, r)));
      return [...s].filter(Boolean).sort();
    },
    preFilteredItems(excludeKey) {
      return this.rawItems.filter(item => {
        return Object.entries(this.columnFilters).every(([k, vals]) => {
          if (k === excludeKey || !vals || !vals.length) return true;
          return vals.includes(this.getCellText(k, item));
        });
      });
    },
    clearAllFilters() { this.columnFilters = {}; },
    hasActiveFilters() { return Object.values(this.columnFilters).some(f => f && f.length); },

    // -- Sorting --
    toggleSort(key) {
      const idx = this.columnSorts.findIndex(s => s.key === key);
      if (idx === -1) this.columnSorts.push({ key, dir: 'asc' });
      else if (this.columnSorts[idx].dir === 'asc') this.columnSorts[idx].dir = 'desc';
      else this.columnSorts.splice(idx, 1);
    },
    getSortIndex(key) { return this.columnSorts.findIndex(s => s.key === key) + 1; },
    getSortDir(key) { const s = this.columnSorts.find(s => s.key === key); return s ? s.dir : null; },
    removeSort(key) { this.columnSorts = this.columnSorts.filter(s => s.key !== key); },
    applySorts(items) {
      if (!this.columnSorts.length) return items;
      return [...items].sort((a, b) => {
        for (const { key, dir } of this.columnSorts) {
          let va = a[key], vb = b[key];
          if (va == null) va = '';
          if (vb == null) vb = '';
          if (typeof va === 'number' && typeof vb === 'number') {
            if (va !== vb) return dir === 'asc' ? va - vb : vb - va;
          } else {
            const cmp = String(va).localeCompare(String(vb));
            if (cmp !== 0) return dir === 'asc' ? cmp : -cmp;
          }
        }
        return 0;
      });
    },

    // -- Pinning --
    togglePin(key) {
      if (this.pinnedColumns.has(key)) this.pinnedColumns.delete(key);
      else this.pinnedColumns.add(key);
      this.pinnedColumns = new Set(this.pinnedColumns); // trigger reactivity
    },
    isPinned(key) { return this.pinnedColumns.has(key); },
    getPinOffset(key, columns) {
      let offset = 0;
      for (const col of columns) {
        if (col.key === key) break;
        if (this.pinnedColumns.has(col.key)) offset += 120;
      }
      return offset;
    },

    // -- Expand --
    toggleExpand(id) {
      if (this.expandedRows.has(id)) this.expandedRows.delete(id);
      else this.expandedRows.add(id);
      this.expandedRows = new Set(this.expandedRows);
    },
    isExpanded(id) { return this.expandedRows.has(id); },

    // -- Export Excel --
    exportToExcel(items, headers, filename) {
      const data = items.map(item => {
        const row = {};
        headers.forEach(h => {
          if (h.key === 'actions' || h.key === 'data-table-select' || h.key === 'expand') return;
          row[h.title] = this.getCellText(h.key, item);
        });
        return row;
      });
      const ws = XLSX.utils.json_to_sheet(data);
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'Data');
      XLSX.writeFile(wb, `${filename}_${new Date().toISOString().slice(0,10)}.xlsx`);
    },

    // -- Invoice tracking number prefix --
    prefixTrackingWithYear(val) {
      const y = String(new Date().getFullYear()).slice(-2);
      if (val.startsWith(y + '-')) return val;
      return y + '-' + val.replace(/^0+/, '').padStart(4, '0');
    },
  },
};
