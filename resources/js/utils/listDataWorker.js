import { useWebWorkerFn } from '@vueuse/core';

/**
 * Self-contained pure worker function executed in Web Worker via useWebWorkerFn.
 * Must NOT reference any external closures or browser DOM variables.
 */
function filterSortAggregateWorker(payload) {
  if (!payload || !Array.isArray(payload.items) || payload.items.length === 0) {
    return {
      filteredIds: [],
      totals: {
        totalWeight: 0,
        totalWeightInvoice: 0,
        totalPrice: 0,
        totalSecondaryPackagingCost: 0,
        totalInvoiced: 0,
        totalNotInvoiced: 0,
        totalInvoiceWeight: 0,
        totalInvoicePrice: 0,
      },
      categoryStats: [],
      chartBuckets: {},
      secondaryPackagingStats: null,
      filterOptions: {},
      filterOptionCounts: {},
    };
  }

  const lang = payload.langCode || 'en';
  const mType = payload.moduleType || 'charge';

  // 1. Categories lookup
  const catMap = {};
  if (Array.isArray(payload.cats)) {
    for (let i = 0; i < payload.cats.length; i++) {
      const c = payload.cats[i];
      if (c && c.id != null) {
        catMap[c.id] = c['name_' + lang] || c.name || ('#' + c.id);
      }
    }
  }
  function catName(id) {
    return catMap[id] || ('#' + id);
  }

  // 2. Stockable items lookup (for details search)
  const fishTypeMap = {};
  if (Array.isArray(payload.fishTypes)) {
    for (let i = 0; i < payload.fishTypes.length; i++) {
      const ft = payload.fishTypes[i];
      if (ft && ft.id != null) {
        fishTypeMap[ft.id] = ft['name_' + lang] || ft.name || ('#' + ft.id);
      }
    }
  }
  const guanoTypeMap = {};
  if (Array.isArray(payload.guanoTypes)) {
    for (let i = 0; i < payload.guanoTypes.length; i++) {
      const gt = payload.guanoTypes[i];
      if (gt && gt.id != null) {
        guanoTypeMap[gt.id] = gt['name_' + lang] || gt.name || ('#' + gt.id);
      }
    }
  }
  const packageTypeMap = {};
  if (Array.isArray(payload.packageTypes)) {
    for (let i = 0; i < payload.packageTypes.length; i++) {
      const pt = payload.packageTypes[i];
      if (pt && pt.id != null) {
        packageTypeMap[pt.id] = pt['name_' + lang] || pt.name || ('#' + pt.id);
      }
    }
  }
  function getStockableName(id, type) {
    if (type === 'FishType') return fishTypeMap[id] || '';
    if (type === 'GuanoType') return guanoTypeMap[id] || '';
    if (type === 'PackageType') return packageTypeMap[id] || '';
    return '';
  }

  // 3. Cell Text Resolver
  function cellText(key, item) {
    if (key === 'charge_category_id' || key === 'production_category_id' || key === 'shipping_category_id') {
      return catName(item[key]);
    }
    if (key === 'date') {
      if (!item.date) return '-';
      return typeof item.date === 'string' ? item.date.slice(0, 10) : String(item.date);
    }
    if (key === 'production_type') {
      return item.production_type || '-';
    }
    const v = item[key];
    return v == null ? '-' : String(v);
  }

  // 4. Search Filtering
  let items = payload.items;
  if (payload.searchQuery && typeof payload.searchQuery === 'string' && payload.searchQuery.trim()) {
    const q = payload.searchQuery.trim().toLowerCase();

    items = items.filter(function(it) {
      if (mType === 'charge') {
        const cat = (catName(it.charge_category_id) || '').toLowerCase();
        const date = (it.date || '').toLowerCase();
        const tr = (it.reception_tracking_number || []).join(' ').toLowerCase();
        const onp = (it.tracking_onp_numbers || []).join(' ').toLowerCase();
        const sup = (it._suppliers || '').toLowerCase();
        const ship = (it._ship || '').toLowerCase();
        const origin = (it._origin || '').toLowerCase();
        const fishery = (it._fishery || '').toLowerCase();
        if (
          cat.includes(q) ||
          date.includes(q) ||
          tr.includes(q) ||
          onp.includes(q) ||
          sup.includes(q) ||
          ship.includes(q) ||
          origin.includes(q) ||
          fishery.includes(q)
        ) {
          return true;
        }
        const dcs = it.detail_charges || [];
        for (let i = 0; i < dcs.length; i++) {
          const dc = dcs[i];
          if (dc.article_description && dc.article_description.toLowerCase().includes(q)) return true;
          if (dc.stockable_type) {
            const sName = getStockableName(dc.stockable_id, dc.stockable_type);
            if (sName && sName.toLowerCase().includes(q)) return true;
          }
          if (dc.warehouse && dc.warehouse.name && dc.warehouse.name.toLowerCase().includes(q)) return true;
        }
        return false;
      } else if (mType === 'production') {
        if (it.date && String(it.date).toLowerCase().includes(q)) return true;
        if (it.production_category_id && catName(it.production_category_id).toLowerCase().includes(q)) return true;
        if (it.production_type && it.production_type.toLowerCase().includes(q)) return true;
        if (it._tracking && it._tracking.toLowerCase().includes(q)) return true;
        if (it._charge && it._charge.toLowerCase().includes(q)) return true;
        if (it._suppliers && it._suppliers.toLowerCase().includes(q)) return true;
        if (it.supplier && it.supplier.name && it.supplier.name.toLowerCase().includes(q)) return true;
        if (it.client && it.client.name && it.client.name.toLowerCase().includes(q)) return true;
        if (it._invoice_state && it._invoice_state.toLowerCase().includes(q)) return true;
        const dcs = it.detail_productions || [];
        for (let i = 0; i < dcs.length; i++) {
          const dc = dcs[i];
          if (dc.article_description && dc.article_description.toLowerCase().includes(q)) return true;
          if (dc.stockable_type) {
            const sName = getStockableName(dc.stockable_id, dc.stockable_type);
            if (sName && sName.toLowerCase().includes(q)) return true;
          }
          if (dc.warehouse && dc.warehouse.name && dc.warehouse.name.toLowerCase().includes(q)) return true;
        }
        return false;
      } else if (mType === 'shipping') {
        if (it.date && String(it.date).toLowerCase().includes(q)) return true;
        if (it.shipping_category_id && catName(it.shipping_category_id).toLowerCase().includes(q)) return true;
        if (it._clients && it._clients.toLowerCase().includes(q)) return true;
        if (it._tracking && it._tracking.toLowerCase().includes(q)) return true;
        if (it._origin && it._origin.toLowerCase().includes(q)) return true;
        if (it.direction && it.direction.toLowerCase().includes(q)) return true;
        if (it._trucks && it._trucks.toLowerCase().includes(q)) return true;
        if (it._invoice_state && it._invoice_state.toLowerCase().includes(q)) return true;
        const dcs = it.detail_shippings || [];
        for (let i = 0; i < dcs.length; i++) {
          const dc = dcs[i];
          if (dc.article_description && dc.article_description.toLowerCase().includes(q)) return true;
          if (dc.stockable_type) {
            const sName = getStockableName(dc.stockable_id, dc.stockable_type);
            if (sName && sName.toLowerCase().includes(q)) return true;
          }
          if (dc.warehouse && dc.warehouse.name && dc.warehouse.name.toLowerCase().includes(q)) return true;
        }
        return false;
      }
      return true;
    });
  }

  // 5. Advanced Filters
  const af = payload.advFilter;
  if (
    af &&
    (af.articleType ||
      (af.warehouses && af.warehouses.length) ||
      (af.fishFamilies && af.fishFamilies.length) ||
      (af.fishFreshFrozen && af.fishFreshFrozen.length) ||
      (af.fishProcessTypes && af.fishProcessTypes.length) ||
      (af.guanoTypes && af.guanoTypes.length) ||
      (af.packageTypes && af.packageTypes.length))
  ) {
    const ftLookup = {};
    if (Array.isArray(payload.fishTypes)) {
      for (let i = 0; i < payload.fishTypes.length; i++) {
        ftLookup[payload.fishTypes[i].id] = payload.fishTypes[i];
      }
    }

    items = items.filter(function(it) {
      const dcs = it.detail_charges || it.detail_productions || it.detail_shippings || [];
      for (let i = 0; i < dcs.length; i++) {
        const dc = dcs[i];
        if (af.warehouses && af.warehouses.length && !af.warehouses.includes(dc.warehouse_id)) continue;
        if (af.articleType) {
          if (af.articleType === 'text' && dc.stockable_type) continue;
          if (af.articleType === 'FishType' && dc.stockable_type !== 'FishType') continue;
          if (af.articleType === 'GuanoType' && dc.stockable_type !== 'GuanoType') continue;
          if (af.articleType === 'PackageType' && dc.stockable_type !== 'PackageType') continue;
        }
        if (af.articleType === 'FishType') {
          const ft = ftLookup[dc.stockable_id];
          if (ft) {
            if (af.fishFamilies && af.fishFamilies.length && !af.fishFamilies.includes(ft.fish_family_id)) continue;
            if (af.fishFreshFrozen && af.fishFreshFrozen.length && !af.fishFreshFrozen.includes(ft.type)) continue;
            if (af.fishProcessTypes && af.fishProcessTypes.length && !af.fishProcessTypes.includes(ft.fish_process_type_id)) continue;
          }
        }
        if (af.articleType === 'GuanoType' && af.guanoTypes && af.guanoTypes.length && !af.guanoTypes.includes(dc.stockable_id)) continue;
        if (af.articleType === 'PackageType' && af.packageTypes && af.packageTypes.length && !af.packageTypes.includes(dc.stockable_id)) continue;
        return true;
      }
      return false;
    });
  }

  // 6. Column Filters
  const colFilters = payload.colFilters || {};
  const activeColFilterKeys = Object.keys(colFilters).filter(function(k) {
    return Array.isArray(colFilters[k]) && colFilters[k].length > 0;
  });
  if (activeColFilterKeys.length > 0) {
    items = items.filter(function(it) {
      for (let i = 0; i < activeColFilterKeys.length; i++) {
        const k = activeColFilterKeys[i];
        const val = cellText(k, it);
        if (!colFilters[k].includes(val)) return false;
      }
      return true;
    });
  }

  // 7. Multi-column Sorting
  const sorts = payload.colSorts || [];
  if (sorts.length > 0) {
    items.sort(function(a, b) {
      for (let i = 0; i < sorts.length; i++) {
        const s = sorts[i];
        const key = s.key;
        const dir = s.dir;
        let va =
          key === 'charge_category_id' || key === 'production_category_id' || key === 'shipping_category_id'
            ? catName(a[key])
            : a[key];
        let vb =
          key === 'charge_category_id' || key === 'production_category_id' || key === 'shipping_category_id'
            ? catName(b[key])
            : b[key];
        if (va == null) va = '';
        if (vb == null) vb = '';
        const na = typeof va === 'number' && typeof vb === 'number';
        if (na) {
          if (va !== vb) return dir === 'asc' ? va - vb : vb - va;
        } else {
          const c = String(va).localeCompare(String(vb));
          if (c !== 0) return dir === 'asc' ? c : -c;
        }
      }
      return 0;
    });
  }

  // 8. Aggregations & Metrics Calculation (Single Pass)
  let totalWeight = 0;
  let totalWeightInvoice = 0;
  let totalPrice = 0;
  let totalSecondaryPackagingCost = 0;
  let totalInvoiced = 0;
  let totalNotInvoiced = 0;
  let totalInvoiceWeight = 0;
  let totalInvoicePrice = 0;

  const categoryStatsMap = {};
  const chartBuckets = {};

  const secPackagingComps =
    mType === 'production'
      ? {
          caisses: { key: 'caisses', label: 'caisses', unit: 'U', qte: 0, total_cost: 0 },
          cellophane: { key: 'cellophane', label: 'cellophane', unit: 'Kg', qte: 0, total_cost: 0 },
          plastique: { key: 'plastique', label: 'plastique', unit: 'Kg', qte: 0, total_cost: 0 },
          strapex: { key: 'strapex', label: 'strapex', unit: 'm', qte: 0, total_cost: 0 },
          palletes: { key: 'palletes', label: 'palletes', unit: 'U', qte: 0, total_cost: 0 },
          scotch: { key: 'scotch', label: 'scotch', unit: 'Rouleau', qte: 0, total_cost: 0 },
        }
      : null;

  for (let i = 0; i < items.length; i++) {
    const item = items[i];
    const w = Number(item.total_weight || 0);
    const p = Number(item.total_price || 0);
    totalWeight += w;
    totalPrice += p;

    if (item._is_invoiced) {
      totalInvoiced++;
      totalInvoiceWeight += w;
      totalInvoicePrice += p;
    } else {
      totalNotInvoiced++;
    }

    if (mType === 'charge') {
      const wi = Number(item._total_weight_invoice || 0);
      totalWeightInvoice += wi;
      const catId = item.charge_category_id;
      if (catId != null) {
        if (!categoryStatsMap[catId]) {
          categoryStatsMap[catId] = {
            id: catId,
            name: catName(catId),
            weight: 0,
            price: 0,
            weightInvoice: 0,
            count: 0,
          };
        }
        categoryStatsMap[catId].weight += w;
        categoryStatsMap[catId].price += p;
        categoryStatsMap[catId].weightInvoice += wi;
        categoryStatsMap[catId].count++;
      }
    } else if (mType === 'production') {
      totalSecondaryPackagingCost += Number(item.total_secondary_packaging_cost || 0);
      const catId = item.production_category_id;
      if (catId != null) {
        if (!categoryStatsMap[catId]) {
          categoryStatsMap[catId] = {
            id: catId,
            name: catName(catId),
            weight: 0,
            price: 0,
            invoiceWeight: 0,
            invoicePrice: 0,
            count: 0,
          };
        }
        categoryStatsMap[catId].weight += w;
        categoryStatsMap[catId].price += p;
        if (item._is_invoiced) {
          categoryStatsMap[catId].invoiceWeight += w;
          categoryStatsMap[catId].invoicePrice += p;
        }
        categoryStatsMap[catId].count++;
      }

      if (secPackagingComps) {
        const details = item.detail_productions || [];
        for (let d = 0; d < details.length; d++) {
          let sec = details[d].secondary_packaging_details;
          if (typeof sec === 'string') {
            try {
              sec = JSON.parse(sec);
            } catch (e) {
              sec = null;
            }
          }
          if (sec && typeof sec === 'object') {
            for (const k in secPackagingComps) {
              if (sec[k]) {
                secPackagingComps[k].qte += Number(sec[k].qte || 0);
                secPackagingComps[k].total_cost += Number(sec[k].total_cost || 0);
              }
            }
          }
        }
      }
    } else if (mType === 'shipping') {
      const catId = item.shipping_category_id;
      if (catId != null) {
        if (!categoryStatsMap[catId]) {
          categoryStatsMap[catId] = { id: catId, name: catName(catId), weight: 0, price: 0, count: 0 };
        }
        categoryStatsMap[catId].weight += w;
        categoryStatsMap[catId].price += p;
        categoryStatsMap[catId].count++;
      }
    }

    // Chart Buckets
    const dStr = typeof item.date === 'string' ? item.date.slice(0, 10) : '';
    let bKey = dStr;
    if (payload.chartMode === 'monthly') {
      bKey = dStr ? dStr.slice(0, 7) : '';
    } else if (payload.chartMode === 'yearly') {
      bKey = dStr ? dStr.slice(0, 4) : '';
    }
    if (bKey) {
      if (!chartBuckets[bKey]) chartBuckets[bKey] = { w: 0, p: 0 };
      chartBuckets[bKey].w += w;
      chartBuckets[bKey].p += p;
    }
  }

  // 9. Precompute Filter Options for Header Menus from base dataset (payload.items)
  // FIX: deriving options from payload.items ensures selected filter options never disappear!
  const filterOptions = {};
  const filterOptionCounts = {};
  const allKeys = payload.allHeaderKeys || [];
  for (let k = 0; k < allKeys.length; k++) {
    const key = allKeys[k];
    if (key === 'expand' || key === 'actions' || key === '__checkbox') continue;
    const countMap = {};
    for (let i = 0; i < payload.items.length; i++) {
      const val = cellText(key, payload.items[i]);
      if (val && val !== '-') {
        countMap[val] = (countMap[val] || 0) + 1;
      }
    }
    filterOptions[key] = Object.keys(countMap).sort();
    filterOptionCounts[key] = countMap;
  }

  const filteredIds = items.map(function(it) {
    return it.id;
  });

  return {
    filteredIds: filteredIds,
    totals: {
      totalWeight: totalWeight,
      totalWeightInvoice: totalWeightInvoice,
      totalPrice: totalPrice,
      totalSecondaryPackagingCost: totalSecondaryPackagingCost,
      totalInvoiced: totalInvoiced,
      totalNotInvoiced: totalNotInvoiced,
      totalInvoiceWeight: totalInvoiceWeight,
      totalInvoicePrice: totalInvoicePrice,
    },
    categoryStats: Object.values(categoryStatsMap),
    chartBuckets: chartBuckets,
    secondaryPackagingStats: secPackagingComps,
    filterOptions: filterOptions,
    filterOptionCounts: filterOptionCounts,
  };
}

/**
 * Creates a worker controller using VueUse useWebWorkerFn.
 * Provides debounce, status tracking, auto-termination of previous runs,
 * and seamless fallback to synchronous local execution if Web Workers are blocked.
 */
export function createListWorker() {
  const { workerFn, workerStatus, workerTerminate } = useWebWorkerFn(filterSortAggregateWorker);

  let debounceTimer = null;

  async function execute(payload, debounceMs = 0) {
    if (debounceMs > 0) {
      if (debounceTimer) clearTimeout(debounceTimer);
      return new Promise((resolve) => {
        debounceTimer = setTimeout(async () => {
          debounceTimer = null;
          try {
            if (workerStatus.value === 'RUNNING') {
              workerTerminate();
            }
            const res = await workerFn(payload);
            resolve(res);
          } catch (err) {
            // Graceful fallback to local synchronous execution
            try {
              const res = filterSortAggregateWorker(payload);
              resolve(res);
            } catch (fallbackErr) {
              console.error('[createListWorker] Local fallback error:', fallbackErr);
              resolve(null);
            }
          }
        }, debounceMs);
      });
    } else {
      if (debounceTimer) {
        clearTimeout(debounceTimer);
        debounceTimer = null;
      }
      try {
        if (workerStatus.value === 'RUNNING') {
          workerTerminate();
        }
        return await workerFn(payload);
      } catch (err) {
        try {
          return filterSortAggregateWorker(payload);
        } catch (fallbackErr) {
          console.error('[createListWorker] Local fallback error:', fallbackErr);
          return null;
        }
      }
    }
  }

  function terminate() {
    if (debounceTimer) {
      clearTimeout(debounceTimer);
      debounceTimer = null;
    }
    try {
      workerTerminate();
    } catch (e) {
      /* silent */
    }
  }

  return {
    execute,
    terminate,
    workerStatus,
    filterSortAggregateLocal: filterSortAggregateWorker,
  };
}
