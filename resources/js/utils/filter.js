import { useWebWorkerFn } from '@vueuse/core';

/**
 * Self-contained pure worker function executed in Web Worker via useWebWorkerFn.
 * Must NOT reference any external closures or browser DOM variables.
 */
export function filterWorkerFn(payload) {
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
      advFilterOptions: {
        articleTypes: [],
        warehouses: [],
        fishFamilies: [],
        fishFreshFrozen: [],
        fishProcessTypes: [],
        fishTypes: [],
        guanoTypes: [],
        packageTypes: [],
      },
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
  function normalizeStockableType(type) {
    if (!type) return 'text';
    if (typeof type !== 'string') return '';
    if (type.includes('FishType')) return 'FishType';
    if (type.includes('GuanoType')) return 'GuanoType';
    if (type.includes('PackageType')) return 'PackageType';
    if (type.toLowerCase() === 'text') return 'text';
    return type;
  }

  function getStockableName(id, type) {
    const norm = normalizeStockableType(type);
    if (norm === 'FishType') return fishTypeMap[id] || '';
    if (norm === 'GuanoType') return guanoTypeMap[id] || '';
    if (norm === 'PackageType') return packageTypeMap[id] || '';
    return '';
  }

  // 3. Cell Text Resolver (universal across all modules)
  function cellText(key, item) {
    if (!item) return '-';
    if (key === 'charge_category_id' || key === 'production_category_id' || key === 'shipping_category_id' || key === 'category_id') {
      return catName(item[key]);
    }
    if (key === 'date') {
      if (!item.date) return '-';
      return typeof item.date === 'string' ? item.date.slice(0, 10) : String(item.date);
    }
    if (key === 'production_type') {
      return item.production_type || '-';
    }
    if (key === 'reception_tracking_number') {
      if (Array.isArray(item.reception_tracking_number)) return item.reception_tracking_number.join(', ') || '-';
      return item.reception_tracking_number ? String(item.reception_tracking_number) : '-';
    }
    if (key === 'tracking_onp_numbers') {
      if (Array.isArray(item.tracking_onp_numbers)) return item.tracking_onp_numbers.join(', ') || '-';
      return item.tracking_onp_numbers ? String(item.tracking_onp_numbers) : '-';
    }
    if (key === '_suppliers') {
      return item._suppliers || (item.supplier && item.supplier.name) || '-';
    }
    if (key === '_clients') {
      return item._clients || (item.client && item.client.name) || '-';
    }
    if (key === '_client_name') {
      return item._client_name || (item.client && item.client.name) || '-';
    }
    if (key === '_supplier_name') {
      return item._supplier_name || (item.supplier && item.supplier.name) || '-';
    }
    if (key === '_tracking') {
      return item._tracking || '-';
    }
    if (key === '_trucks') {
      return item._trucks || '-';
    }
    if (key === '_ship') {
      return item._ship || '-';
    }
    if (key === '_origin') {
      return item._origin || '-';
    }
    if (key === '_fishery') {
      return item._fishery || '-';
    }
    if (key === '_invoice_state') {
      return item._invoice_state || '-';
    }
    const v = item[key];
    return v == null ? '-' : String(v);
  }

  // 4. PRECOMPUTE FILTER OPTIONS FROM THE FULL DATASET (payload.items)
  // CRITICAL FIX: Options are extracted from payload.items so selecting an item NEVER causes options to vanish!
  const filterOptions = {};
  const filterOptionCounts = {};
  const allKeys = payload.allHeaderKeys || [];
  for (let k = 0; k < allKeys.length; k++) {
    const key = allKeys[k];
    if (key === 'expand' || key === 'actions' || key === '__checkbox') continue;
    const countMap = {};
    for (let i = 0; i < payload.items.length; i++) {
      const val = cellText(key, payload.items[i]);
      if (val != null && val !== '' && val !== '-') {
        countMap[val] = (countMap[val] || 0) + 1;
      }
    }
    filterOptions[key] = Object.keys(countMap).sort(function(a, b) {
      return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
    });
    filterOptionCounts[key] = countMap;
  }

  // 5. Search Filtering
  let items = payload.items;
  if (payload.searchQuery && typeof payload.searchQuery === 'string' && payload.searchQuery.trim()) {
    const q = payload.searchQuery.trim().toLowerCase();

    items = items.filter(function(it) {
      if (mType === 'charge') {
        const cat = (catName(it.charge_category_id) || '').toLowerCase();
        const date = (it.date || '').toLowerCase();
        const tr = (Array.isArray(it.reception_tracking_number) ? it.reception_tracking_number.join(' ') : String(it.reception_tracking_number || '')).toLowerCase();
        const onp = (Array.isArray(it.tracking_onp_numbers) ? it.tracking_onp_numbers.join(' ') : String(it.tracking_onp_numbers || '')).toLowerCase();
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
      } else {
        // Generic module search across all keys
        for (let k = 0; k < allKeys.length; k++) {
          const val = cellText(allKeys[k], it);
          if (val && String(val).toLowerCase().includes(q)) return true;
        }
        return false;
      }
    });
  }

  // 6. Advanced Filters
  const af = payload.advFilter;
  const selectedArticleTypes = af ? (
    Array.isArray(af.articleTypes) && af.articleTypes.length > 0
      ? af.articleTypes
      : (af.articleType ? [af.articleType] : [])
  ) : [];
  const hasWarehouses = Boolean(af && Array.isArray(af.warehouses) && af.warehouses.length > 0);
  const hasFishFamilies = Boolean(af && Array.isArray(af.fishFamilies) && af.fishFamilies.length > 0);
  const hasFishFreshFrozen = Boolean(af && Array.isArray(af.fishFreshFrozen) && af.fishFreshFrozen.length > 0);
  const hasFishProcessTypes = Boolean(af && Array.isArray(af.fishProcessTypes) && af.fishProcessTypes.length > 0);
  const hasFishTypes = Boolean(af && Array.isArray(af.fishTypes) && af.fishTypes.length > 0);
  const hasGuanoTypes = Boolean(af && Array.isArray(af.guanoTypes) && af.guanoTypes.length > 0);
  const hasPackageTypes = Boolean(af && Array.isArray(af.packageTypes) && af.packageTypes.length > 0);

  const isAdvFilterActive = Boolean(
    selectedArticleTypes.length > 0 ||
    hasWarehouses ||
    hasFishFamilies ||
    hasFishFreshFrozen ||
    hasFishProcessTypes ||
    hasFishTypes ||
    hasGuanoTypes ||
    hasPackageTypes
  );

  if (isAdvFilterActive) {
    const ftLookup = {};
    if (Array.isArray(payload.fishTypes)) {
      for (let i = 0; i < payload.fishTypes.length; i++) {
        ftLookup[payload.fishTypes[i].id] = payload.fishTypes[i];
      }
    }

    items = items.filter(function(it) {
      const dcs = it.detail_charges || it.detail_productions || it.detail_shippings || [];
      if (!dcs.length) return false;

      for (let i = 0; i < dcs.length; i++) {
        const dc = dcs[i];

        // 1. Warehouse filter check
        if (hasWarehouses && !af.warehouses.includes(dc.warehouse_id)) {
          continue;
        }

        const normType = normalizeStockableType(dc.stockable_type);

        // 2. Article Types filter check (multi-filter supported)
        if (selectedArticleTypes.length > 0) {
          if (!selectedArticleTypes.includes(normType)) {
            continue;
          }
        }

        // 3. Sub-filter checks
        if (normType === 'FishType') {
          const ft = (dc.stockable && dc.stockable.fish_family_id !== undefined) ? dc.stockable : ftLookup[dc.stockable_id];
          if (hasFishFamilies) {
            if (!ft || !af.fishFamilies.includes(ft.fish_family_id)) continue;
          }
          if (hasFishFreshFrozen) {
            if (!ft || !af.fishFreshFrozen.includes(ft.type)) continue;
          }
          if (hasFishProcessTypes) {
            if (!ft || !af.fishProcessTypes.includes(ft.fish_process_type_id)) continue;
          }
          if (hasFishTypes) {
            if (!af.fishTypes.includes(dc.stockable_id)) continue;
          }
        } else if (normType === 'GuanoType') {
          if (hasGuanoTypes && !af.guanoTypes.includes(dc.stockable_id)) {
            continue;
          }
          // If fish-specific sub-filters are active, guano items cannot satisfy them
          if (hasFishFamilies || hasFishFreshFrozen || hasFishProcessTypes || hasFishTypes) {
            continue;
          }
        } else if (normType === 'PackageType') {
          const isStockableMatch = af.packageTypes.includes(dc.stockable_id);
          const isPkgFieldMatch = dc.package_type_id && af.packageTypes.includes(dc.package_type_id);
          if (hasPackageTypes && !isStockableMatch && !isPkgFieldMatch) {
            continue;
          }
          // If fish, guano or specific fish type sub-filters are active, package items cannot satisfy them
          if (hasFishFamilies || hasFishFreshFrozen || hasFishProcessTypes || hasFishTypes || hasGuanoTypes) {
            continue;
          }
        } else if (normType === 'text') {
          // If specific sub-filters are active, text description cannot satisfy them
          if (hasFishFamilies || hasFishFreshFrozen || hasFishProcessTypes || hasFishTypes || hasGuanoTypes || hasPackageTypes) {
            continue;
          }
        }

        // Passed all active criteria!
        return true;
      }
      return false;
    });
  }

  // 7. Column Filters
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

  // 8. Multi-column Sorting
  const sorts = payload.colSorts || [];
  if (sorts.length > 0) {
    items.sort(function(a, b) {
      for (let i = 0; i < sorts.length; i++) {
        const s = sorts[i];
        const key = s.key;
        const dir = s.dir;
        let va = a[key];
        let vb = b[key];
        if (va == null || typeof va === 'object') {
          va = cellText(key, a);
        }
        if (vb == null || typeof vb === 'object') {
          vb = cellText(key, b);
        }
        if (va == null) va = '';
        if (vb == null) vb = '';

        const numA = Number(va);
        const numB = Number(vb);
        const isNum = !isNaN(numA) && !isNaN(numB) && va !== '' && vb !== '';
        if (isNum) {
          if (numA !== numB) return dir === 'asc' ? numA - numB : numB - numA;
        } else {
          const c = String(va).localeCompare(String(vb), undefined, { numeric: true, sensitivity: 'base' });
          if (c !== 0) return dir === 'asc' ? c : -c;
        }
      }
      return 0;
    });
  }

  // 9. Aggregates and Totals
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

  const secPackagingComps = payload.secondaryPackagingComps ? JSON.parse(JSON.stringify(payload.secondaryPackagingComps)) : null;

  for (let i = 0; i < items.length; i++) {
    const item = items[i];
    const w = Number(item.total_weight || 0);
    const wi = Number(item.total_weight_invoice || 0);
    const p = Number(item.total_price || 0);
    const spc = Number(item.total_secondary_packaging_cost || 0);

    totalWeight += w;
    totalWeightInvoice += wi;
    totalPrice += p;
    totalSecondaryPackagingCost += spc;

    if (item._is_invoiced) {
      totalInvoiced++;
      totalInvoiceWeight += w;
      totalInvoicePrice += p;
    } else {
      totalNotInvoiced++;
    }

    if (mType === 'charge') {
      const catId = item.charge_category_id;
      if (catId != null) {
        if (!categoryStatsMap[catId]) {
          categoryStatsMap[catId] = {
            id: catId,
            name: catName(catId),
            weight: 0,
            weightInvoice: 0,
            price: 0,
            count: 0,
          };
        }
        categoryStatsMap[catId].weight += w;
        categoryStatsMap[catId].weightInvoice += wi;
        categoryStatsMap[catId].price += p;
        categoryStatsMap[catId].count++;
      }
    } else if (mType === 'production') {
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

  const filteredIds = items.map(function(it) {
    return it.id;
  });

  // Extract Dynamic Cascading Advanced Filter Options from datatable records (payload.items)
  const fishFamiliesLookup = payload.fishFamilies || [];
  const fishTypesLookup = payload.fishTypes || [];
  const fishProcessTypesLookup = payload.fishProcessTypes || [];
  const guanoTypesLookup = payload.guanoTypes || [];
  const packageTypesLookup = payload.packageTypes || [];
  const warehousesLookup = payload.warehouses || [];

  const famLookup = {};
  for (let i = 0; i < fishFamiliesLookup.length; i++) {
    const f = fishFamiliesLookup[i];
    if (f && f.id != null) famLookup[f.id] = f['name_' + lang] || f.name || ('#' + f.id);
  }
  const procLookup = {};
  for (let i = 0; i < fishProcessTypesLookup.length; i++) {
    const p = fishProcessTypesLookup[i];
    if (p && p.id != null) procLookup[p.id] = p['name_' + lang] || p.name || ('#' + p.id);
  }
  const gtLookup = {};
  for (let i = 0; i < guanoTypesLookup.length; i++) {
    const g = guanoTypesLookup[i];
    if (g && g.id != null) gtLookup[g.id] = g;
  }
  const ptLookup = {};
  for (let i = 0; i < packageTypesLookup.length; i++) {
    const p = packageTypesLookup[i];
    if (p && p.id != null) ptLookup[p.id] = p;
  }
  const whLookup = {};
  for (let i = 0; i < warehousesLookup.length; i++) {
    const w = warehousesLookup[i];
    if (w && w.id != null) whLookup[w.id] = w['name_' + lang] || w.name || ('#' + w.id);
  }
  const ftFullLookup = {};
  for (let i = 0; i < fishTypesLookup.length; i++) {
    const ft = fishTypesLookup[i];
    if (ft && ft.id != null) ftFullLookup[ft.id] = ft;
  }

  const articleTypeCounts = {};
  const warehouseCounts = {};
  const fishDetails = [];
  const guanoDetails = [];
  const packageDetails = [];

  for (let i = 0; i < payload.items.length; i++) {
    const it = payload.items[i];
    const dcs = it.detail_charges || it.detail_productions || it.detail_shippings || [];
    for (let d = 0; d < dcs.length; d++) {
      const dc = dcs[d];
      const normType = normalizeStockableType(dc.stockable_type);
      articleTypeCounts[normType] = (articleTypeCounts[normType] || 0) + 1;

      if (dc.warehouse_id != null) {
        warehouseCounts[dc.warehouse_id] = (warehouseCounts[dc.warehouse_id] || 0) + 1;
      }

      if (normType === 'FishType') {
        const ft = (dc.stockable && dc.stockable.fish_family_id !== undefined) ? dc.stockable : ftFullLookup[dc.stockable_id];
        fishDetails.push({
          stockableId: dc.stockable_id,
          familyId: ft ? ft.fish_family_id : null,
          freshFrozen: ft ? ft.type : null,
          processTypeId: ft ? ft.fish_process_type_id : null,
          warehouseId: dc.warehouse_id,
          stockable: ft || null,
        });
      } else if (normType === 'GuanoType') {
        guanoDetails.push({
          stockableId: dc.stockable_id,
          warehouseId: dc.warehouse_id,
          stockable: dc.stockable || gtLookup[dc.stockable_id] || null,
        });
      } else if (normType === 'PackageType') {
        packageDetails.push({
          packageTypeId: dc.stockable_id,
          warehouseId: dc.warehouse_id,
          stockable: dc.stockable || ptLookup[dc.stockable_id] || null,
        });
      }

      if (dc.package_type_id != null && normType !== 'PackageType') {
        packageDetails.push({
          packageTypeId: dc.package_type_id,
          warehouseId: dc.warehouse_id,
          stockable: ptLookup[dc.package_type_id] || null,
        });
      }
    }
  }

  const artTypeTitles = {
    FishType: { en: 'Fish', fr: 'Poisson', es: 'Pescado', icon: 'mdi-fish' },
    GuanoType: { en: 'Guano', fr: 'Guano', es: 'Guano', icon: 'mdi-leaf' },
    PackageType: { en: 'Packaging', fr: 'Emballage', es: 'Embalaje', icon: 'mdi-package-variant-closed' },
    text: { en: 'Text', fr: 'Texte', es: 'Texto', icon: 'mdi-format-text' },
  };
  const advArticleTypeOptions = [];
  const knownTypes = ['FishType', 'GuanoType', 'PackageType', 'text'];
  for (let k = 0; k < knownTypes.length; k++) {
    const t = knownTypes[k];
    const c = articleTypeCounts[t] || 0;
    if (c > 0) {
      const meta = artTypeTitles[t] || { en: t, fr: t, es: t, icon: 'mdi-cube-outline' };
      const rawTitle = meta[lang] || meta.en || t;
      advArticleTypeOptions.push({
        value: t,
        title: rawTitle + ' (' + c + ')',
        rawTitle: rawTitle,
        count: c,
        icon: meta.icon,
      });
    }
  }

  const advWarehouseOptions = [];
  for (const wId in warehouseCounts) {
    const c = warehouseCounts[wId];
    if (c > 0) {
      const numId = Number(wId);
      const rawTitle = whLookup[numId] || ('#' + numId);
      advWarehouseOptions.push({
        value: numId,
        title: rawTitle + ' (' + c + ')',
        rawTitle: rawTitle,
        count: c,
      });
    }
  }
  advWarehouseOptions.sort(function(a, b) { return b.count - a.count || a.rawTitle.localeCompare(b.rawTitle); });

  const famCounts = {};
  for (let i = 0; i < fishDetails.length; i++) {
    const fid = fishDetails[i].familyId;
    if (fid != null) famCounts[fid] = (famCounts[fid] || 0) + 1;
  }
  const advFishFamilyOptions = [];
  for (const fid in famCounts) {
    const c = famCounts[fid];
    if (c > 0) {
      const numId = Number(fid);
      const rawTitle = famLookup[numId] || ('#' + numId);
      advFishFamilyOptions.push({
        value: numId,
        title: rawTitle + ' (' + c + ')',
        rawTitle: rawTitle,
        count: c,
      });
    }
  }
  advFishFamilyOptions.sort(function(a, b) { return b.count - a.count || a.rawTitle.localeCompare(b.rawTitle); });

  const selectedFams = af && Array.isArray(af.fishFamilies) && af.fishFamilies.length > 0 ? af.fishFamilies : [];
  const stateCounts = {};
  for (let i = 0; i < fishDetails.length; i++) {
    const fd = fishDetails[i];
    if (selectedFams.length > 0 && !selectedFams.includes(fd.familyId)) continue;
    if (fd.freshFrozen) stateCounts[fd.freshFrozen] = (stateCounts[fd.freshFrozen] || 0) + 1;
  }
  const stateLabels = {
    fresh: { en: 'Fresh', fr: 'Frais', es: 'Fresco' },
    frozen: { en: 'Frozen', fr: 'Congelé', es: 'Congelado' },
  };
  const advFishFreshFrozenOptions = [];
  ['fresh', 'frozen'].forEach(function(st) {
    const c = stateCounts[st] || 0;
    if (c > 0) {
      const lbl = stateLabels[st] ? (stateLabels[st][lang] || stateLabels[st].en) : st;
      advFishFreshFrozenOptions.push({
        value: st,
        title: lbl + ' (' + c + ')',
        rawTitle: lbl,
        count: c,
        icon: st === 'frozen' ? 'mdi-snowflake' : 'mdi-water',
      });
    }
  });

  const selectedFF = af && Array.isArray(af.fishFreshFrozen) && af.fishFreshFrozen.length > 0 ? af.fishFreshFrozen : [];
  const procCounts = {};
  for (let i = 0; i < fishDetails.length; i++) {
    const fd = fishDetails[i];
    if (selectedFams.length > 0 && !selectedFams.includes(fd.familyId)) continue;
    if (selectedFF.length > 0 && !selectedFF.includes(fd.freshFrozen)) continue;
    if (fd.processTypeId != null) procCounts[fd.processTypeId] = (procCounts[fd.processTypeId] || 0) + 1;
  }
  const advFishProcessTypeOptions = [];
  for (const pid in procCounts) {
    const c = procCounts[pid];
    if (c > 0) {
      const numId = Number(pid);
      const rawTitle = procLookup[numId] || ('#' + numId);
      advFishProcessTypeOptions.push({
        value: numId,
        title: rawTitle + ' (' + c + ')',
        rawTitle: rawTitle,
        count: c,
      });
    }
  }
  advFishProcessTypeOptions.sort(function(a, b) { return b.count - a.count || a.rawTitle.localeCompare(b.rawTitle); });

  const selectedProc = af && Array.isArray(af.fishProcessTypes) && af.fishProcessTypes.length > 0 ? af.fishProcessTypes : [];
  const fishTypeCounts = {};
  for (let i = 0; i < fishDetails.length; i++) {
    const fd = fishDetails[i];
    if (selectedFams.length > 0 && !selectedFams.includes(fd.familyId)) continue;
    if (selectedFF.length > 0 && !selectedFF.includes(fd.freshFrozen)) continue;
    if (selectedProc.length > 0 && !selectedProc.includes(fd.processTypeId)) continue;
    if (fd.stockableId != null) fishTypeCounts[fd.stockableId] = (fishTypeCounts[fd.stockableId] || 0) + 1;
  }
  const advFishTypeOptions = [];
  for (const ftId in fishTypeCounts) {
    const c = fishTypeCounts[ftId];
    if (c > 0) {
      const numId = Number(ftId);
      const ft = ftFullLookup[numId];
      const famName = ft && ft.fish_family_id ? (famLookup[ft.fish_family_id] || '') : '';
      const stateLbl = ft && ft.type ? (stateLabels[ft.type] ? (stateLabels[ft.type][lang] || ft.type) : ft.type) : '';
      const rawTitle = ft ? (ft['name_' + lang] || ft.name || ('#' + numId)) : ('#' + numId);
      const subParts = [];
      if (famName) subParts.push(famName);
      if (stateLbl) subParts.push(stateLbl);
      if (ft && ft.scientific_name) subParts.push(ft.scientific_name);
      advFishTypeOptions.push({
        value: numId,
        title: rawTitle,
        subtitle: subParts.join(' • '),
        rawTitle: rawTitle,
        count: c,
        scientific_name: ft ? (ft.scientific_name || null) : null,
      });
    }
  }
  advFishTypeOptions.sort(function(a, b) { return b.count - a.count || a.rawTitle.localeCompare(b.rawTitle); });

  const guanoCounts = {};
  for (let i = 0; i < guanoDetails.length; i++) {
    const gid = guanoDetails[i].stockableId;
    if (gid != null) guanoCounts[gid] = (guanoCounts[gid] || 0) + 1;
  }
  const advGuanoTypeOptions = [];
  for (const gid in guanoCounts) {
    const c = guanoCounts[gid];
    if (c > 0) {
      const numId = Number(gid);
      const gt = gtLookup[numId];
      const rawTitle = gt ? (gt['name_' + lang] || gt.name || ('#' + numId)) : ('#' + numId);
      advGuanoTypeOptions.push({
        value: numId,
        title: rawTitle,
        subtitle: gt ? (gt.scientific_name || '') : '',
        rawTitle: rawTitle,
        count: c,
      });
    }
  }
  advGuanoTypeOptions.sort(function(a, b) { return b.count - a.count || a.rawTitle.localeCompare(b.rawTitle); });

  const packageCounts = {};
  for (let i = 0; i < packageDetails.length; i++) {
    const pid = packageDetails[i].packageTypeId;
    if (pid != null) packageCounts[pid] = (packageCounts[pid] || 0) + 1;
  }
  const advPackageTypeOptions = [];
  for (const pid in packageCounts) {
    const c = packageCounts[pid];
    if (c > 0) {
      const numId = Number(pid);
      const pt = ptLookup[numId];
      const rawTitle = pt ? (pt['name_' + lang] || pt.name || ('#' + numId)) : ('#' + numId);
      const subParts = [];
      if (pt && pt.type_emballage) subParts.push(pt.type_emballage);
      if (pt && pt.size) subParts.push(pt.size + 'kg');
      advPackageTypeOptions.push({
        value: numId,
        title: rawTitle,
        subtitle: subParts.join(' • '),
        rawTitle: rawTitle,
        count: c,
      });
    }
  }
  advPackageTypeOptions.sort(function(a, b) { return b.count - a.count || a.rawTitle.localeCompare(b.rawTitle); });

  const dynamicAdvOptionsResult = {
    articleTypes: advArticleTypeOptions,
    warehouses: advWarehouseOptions,
    fishFamilies: advFishFamilyOptions,
    fishFreshFrozen: advFishFreshFrozenOptions,
    fishProcessTypes: advFishProcessTypeOptions,
    fishTypes: advFishTypeOptions,
    guanoTypes: advGuanoTypeOptions,
    packageTypes: advPackageTypeOptions,
  };

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
    advFilterOptions: dynamicAdvOptionsResult,
  };
}

/**
 * Creates a global worker using @vueuse/core's useWebWorkerFn.
 * Provides fallback to synchronous execution if web workers are unsupported.
 */
export function createGlobalFilterWorker() {
  let workerFn = null;
  try {
    const res = useWebWorkerFn(filterWorkerFn);
    workerFn = res.workerFn;
  } catch (e) {
    console.warn('[GlobalFilter] Web Worker initialization failed, fallback to synchronous mode:', e);
  }

  return async function runFilter(payload) {
    if (workerFn) {
      try {
        return await workerFn(payload);
      } catch (err) {
        console.warn('[GlobalFilter] Worker execution error, running fallback sync:', err);
        return filterWorkerFn(payload);
      }
    }
    return filterWorkerFn(payload);
  };
}

/**
 * Synchronous column options extractor.
 * Can be used by any component to extract all distinct column values from the base dataset.
 */
export function extractColumnOptions(items, allKeys, cellTextFn) {
  const options = {};
  const counts = {};
  if (!Array.isArray(items) || !Array.isArray(allKeys)) {
    return { options, counts };
  }

  for (let k = 0; k < allKeys.length; k++) {
    const key = allKeys[k];
    if (key === 'expand' || key === 'actions' || key === '__checkbox') continue;
    const countMap = {};
    for (let i = 0; i < items.length; i++) {
      const it = items[i];
      const val = cellTextFn ? cellTextFn(key, it) : (it[key] != null ? String(it[key]) : '-');
      if (val != null && val !== '' && val !== '-') {
        countMap[val] = (countMap[val] || 0) + 1;
      }
    }
    options[key] = Object.keys(countMap).sort((a, b) => {
      return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
    });
    counts[key] = countMap;
  }

  return { options, counts };
}

/**
 * Stateful List Worker supporting debouncing, termination, and graceful local sync fallback.
 */
export function createListWorker() {
  const { workerFn, workerStatus, workerTerminate } = useWebWorkerFn(filterWorkerFn);

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
            try {
              const res = filterWorkerFn(payload);
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
          return filterWorkerFn(payload);
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
    filterSortAggregateLocal: filterWorkerFn,
  };
}

export function extractAdvFilterOptions(items, advFilter, lookups, lang) {
  if (!lang) lang = 'en';
  if (!advFilter) advFilter = {};
  if (!items || !Array.isArray(items) || items.length === 0) {
    return {
      articleTypes: [],
      warehouses: [],
      fishFamilies: [],
      fishFreshFrozen: [],
      fishProcessTypes: [],
      fishTypes: [],
      guanoTypes: [],
      packageTypes: [],
    };
  }

  const fishFamiliesLookup = lookups?.fishFamilies || [];
  const fishTypesLookup = lookups?.fishTypes || [];
  const fishProcessTypesLookup = lookups?.fishProcessTypes || [];
  const guanoTypesLookup = lookups?.guanoTypes || [];
  const packageTypesLookup = lookups?.packageTypes || [];
  const warehousesLookup = lookups?.warehouses || [];

  const famLookup = {};
  for (let i = 0; i < fishFamiliesLookup.length; i++) {
    const f = fishFamiliesLookup[i];
    if (f && f.id != null) famLookup[f.id] = f['name_' + lang] || f.name || ('#' + f.id);
  }
  const procLookup = {};
  for (let i = 0; i < fishProcessTypesLookup.length; i++) {
    const p = fishProcessTypesLookup[i];
    if (p && p.id != null) procLookup[p.id] = p['name_' + lang] || p.name || ('#' + p.id);
  }
  const gtLookup = {};
  for (let i = 0; i < guanoTypesLookup.length; i++) {
    const g = guanoTypesLookup[i];
    if (g && g.id != null) gtLookup[g.id] = g;
  }
  const ptLookup = {};
  for (let i = 0; i < packageTypesLookup.length; i++) {
    const p = packageTypesLookup[i];
    if (p && p.id != null) ptLookup[p.id] = p;
  }
  const whLookup = {};
  for (let i = 0; i < warehousesLookup.length; i++) {
    const w = warehousesLookup[i];
    if (w && w.id != null) whLookup[w.id] = w['name_' + lang] || w.name || ('#' + w.id);
  }
  const ftFullLookup = {};
  for (let i = 0; i < fishTypesLookup.length; i++) {
    const ft = fishTypesLookup[i];
    if (ft && ft.id != null) ftFullLookup[ft.id] = ft;
  }

  function normalizeStockableType(type) {
    if (!type) return 'text';
    if (typeof type !== 'string') return '';
    if (type.includes('FishType')) return 'FishType';
    if (type.includes('GuanoType')) return 'GuanoType';
    if (type.includes('PackageType')) return 'PackageType';
    if (type.toLowerCase() === 'text') return 'text';
    return type;
  }

  const articleTypeCounts = {};
  const warehouseCounts = {};
  const fishDetails = [];
  const guanoDetails = [];
  const packageDetails = [];

  for (let i = 0; i < items.length; i++) {
    const it = items[i];
    const dcs = it.detail_charges || it.detail_productions || it.detail_shippings || [];
    for (let d = 0; d < dcs.length; d++) {
      const dc = dcs[d];
      const normType = normalizeStockableType(dc.stockable_type);
      articleTypeCounts[normType] = (articleTypeCounts[normType] || 0) + 1;

      if (dc.warehouse_id != null) {
        warehouseCounts[dc.warehouse_id] = (warehouseCounts[dc.warehouse_id] || 0) + 1;
      }

      if (normType === 'FishType') {
        const ft = (dc.stockable && dc.stockable.fish_family_id !== undefined) ? dc.stockable : ftFullLookup[dc.stockable_id];
        fishDetails.push({
          stockableId: dc.stockable_id,
          familyId: ft ? ft.fish_family_id : null,
          freshFrozen: ft ? ft.type : null,
          processTypeId: ft ? ft.fish_process_type_id : null,
          warehouseId: dc.warehouse_id,
          stockable: ft || null,
        });
      } else if (normType === 'GuanoType') {
        guanoDetails.push({
          stockableId: dc.stockable_id,
          warehouseId: dc.warehouse_id,
          stockable: dc.stockable || gtLookup[dc.stockable_id] || null,
        });
      } else if (normType === 'PackageType') {
        packageDetails.push({
          packageTypeId: dc.stockable_id,
          warehouseId: dc.warehouse_id,
          stockable: dc.stockable || ptLookup[dc.stockable_id] || null,
        });
      }

      if (dc.package_type_id != null && normType !== 'PackageType') {
        packageDetails.push({
          packageTypeId: dc.package_type_id,
          warehouseId: dc.warehouse_id,
          stockable: ptLookup[dc.package_type_id] || null,
        });
      }
    }
  }

  const artTypeTitles = {
    FishType: { en: 'Fish', fr: 'Poisson', es: 'Pescado', icon: 'mdi-fish' },
    GuanoType: { en: 'Guano', fr: 'Guano', es: 'Guano', icon: 'mdi-leaf' },
    PackageType: { en: 'Packaging', fr: 'Emballage', es: 'Embalaje', icon: 'mdi-package-variant-closed' },
    text: { en: 'Text', fr: 'Texte', es: 'Texto', icon: 'mdi-format-text' },
  };
  const advArticleTypeOptions = [];
  const knownTypes = ['FishType', 'GuanoType', 'PackageType', 'text'];
  for (let k = 0; k < knownTypes.length; k++) {
    const t = knownTypes[k];
    const c = articleTypeCounts[t] || 0;
    if (c > 0) {
      const meta = artTypeTitles[t] || { en: t, fr: t, es: t, icon: 'mdi-cube-outline' };
      const rawTitle = meta[lang] || meta.en || t;
      advArticleTypeOptions.push({
        value: t,
        title: rawTitle + ' (' + c + ')',
        rawTitle: rawTitle,
        count: c,
        icon: meta.icon,
      });
    }
  }

  const advWarehouseOptions = [];
  for (const wId in warehouseCounts) {
    const c = warehouseCounts[wId];
    if (c > 0) {
      const numId = Number(wId);
      const rawTitle = whLookup[numId] || ('#' + numId);
      advWarehouseOptions.push({
        value: numId,
        title: rawTitle + ' (' + c + ')',
        rawTitle: rawTitle,
        count: c,
      });
    }
  }
  advWarehouseOptions.sort(function(a, b) { return b.count - a.count || a.rawTitle.localeCompare(b.rawTitle); });

  const famCounts = {};
  for (let i = 0; i < fishDetails.length; i++) {
    const fid = fishDetails[i].familyId;
    if (fid != null) famCounts[fid] = (famCounts[fid] || 0) + 1;
  }
  const advFishFamilyOptions = [];
  for (const fid in famCounts) {
    const c = famCounts[fid];
    if (c > 0) {
      const numId = Number(fid);
      const rawTitle = famLookup[numId] || ('#' + numId);
      advFishFamilyOptions.push({
        value: numId,
        title: rawTitle + ' (' + c + ')',
        rawTitle: rawTitle,
        count: c,
      });
    }
  }
  advFishFamilyOptions.sort(function(a, b) { return b.count - a.count || a.rawTitle.localeCompare(b.rawTitle); });

  const selectedFams = advFilter && Array.isArray(advFilter.fishFamilies) && advFilter.fishFamilies.length > 0 ? advFilter.fishFamilies : [];
  const stateCounts = {};
  for (let i = 0; i < fishDetails.length; i++) {
    const fd = fishDetails[i];
    if (selectedFams.length > 0 && !selectedFams.includes(fd.familyId)) continue;
    if (fd.freshFrozen) stateCounts[fd.freshFrozen] = (stateCounts[fd.freshFrozen] || 0) + 1;
  }
  const stateLabels = {
    fresh: { en: 'Fresh', fr: 'Frais', es: 'Fresco' },
    frozen: { en: 'Frozen', fr: 'Congelé', es: 'Congelado' },
  };
  const advFishFreshFrozenOptions = [];
  ['fresh', 'frozen'].forEach(function(st) {
    const c = stateCounts[st] || 0;
    if (c > 0) {
      const lbl = stateLabels[st] ? (stateLabels[st][lang] || stateLabels[st].en) : st;
      advFishFreshFrozenOptions.push({
        value: st,
        title: lbl + ' (' + c + ')',
        rawTitle: lbl,
        count: c,
        icon: st === 'frozen' ? 'mdi-snowflake' : 'mdi-water',
      });
    }
  });

  const selectedFF = advFilter && Array.isArray(advFilter.fishFreshFrozen) && advFilter.fishFreshFrozen.length > 0 ? advFilter.fishFreshFrozen : [];
  const procCounts = {};
  for (let i = 0; i < fishDetails.length; i++) {
    const fd = fishDetails[i];
    if (selectedFams.length > 0 && !selectedFams.includes(fd.familyId)) continue;
    if (selectedFF.length > 0 && !selectedFF.includes(fd.freshFrozen)) continue;
    if (fd.processTypeId != null) procCounts[fd.processTypeId] = (procCounts[fd.processTypeId] || 0) + 1;
  }
  const advFishProcessTypeOptions = [];
  for (const pid in procCounts) {
    const c = procCounts[pid];
    if (c > 0) {
      const numId = Number(pid);
      const rawTitle = procLookup[numId] || ('#' + numId);
      advFishProcessTypeOptions.push({
        value: numId,
        title: rawTitle + ' (' + c + ')',
        rawTitle: rawTitle,
        count: c,
      });
    }
  }
  advFishProcessTypeOptions.sort(function(a, b) { return b.count - a.count || a.rawTitle.localeCompare(b.rawTitle); });

  const selectedProc = advFilter && Array.isArray(advFilter.fishProcessTypes) && advFilter.fishProcessTypes.length > 0 ? advFilter.fishProcessTypes : [];
  const fishTypeCounts = {};
  for (let i = 0; i < fishDetails.length; i++) {
    const fd = fishDetails[i];
    if (selectedFams.length > 0 && !selectedFams.includes(fd.familyId)) continue;
    if (selectedFF.length > 0 && !selectedFF.includes(fd.freshFrozen)) continue;
    if (selectedProc.length > 0 && !selectedProc.includes(fd.processTypeId)) continue;
    if (fd.stockableId != null) fishTypeCounts[fd.stockableId] = (fishTypeCounts[fd.stockableId] || 0) + 1;
  }
  const advFishTypeOptions = [];
  for (const ftId in fishTypeCounts) {
    const c = fishTypeCounts[ftId];
    if (c > 0) {
      const numId = Number(ftId);
      const ft = ftFullLookup[numId];
      const famName = ft && ft.fish_family_id ? (famLookup[ft.fish_family_id] || '') : '';
      const stateLbl = ft && ft.type ? (stateLabels[ft.type] ? (stateLabels[ft.type][lang] || ft.type) : ft.type) : '';
      const rawTitle = ft ? (ft['name_' + lang] || ft.name || ('#' + numId)) : ('#' + numId);
      const subParts = [];
      if (famName) subParts.push(famName);
      if (stateLbl) subParts.push(stateLbl);
      if (ft && ft.scientific_name) subParts.push(ft.scientific_name);
      advFishTypeOptions.push({
        value: numId,
        title: rawTitle,
        subtitle: subParts.join(' • '),
        rawTitle: rawTitle,
        count: c,
        scientific_name: ft ? (ft.scientific_name || null) : null,
      });
    }
  }
  advFishTypeOptions.sort(function(a, b) { return b.count - a.count || a.rawTitle.localeCompare(b.rawTitle); });

  const guanoCounts = {};
  for (let i = 0; i < guanoDetails.length; i++) {
    const gid = guanoDetails[i].stockableId;
    if (gid != null) guanoCounts[gid] = (guanoCounts[gid] || 0) + 1;
  }
  const advGuanoTypeOptions = [];
  for (const gid in guanoCounts) {
    const c = guanoCounts[gid];
    if (c > 0) {
      const numId = Number(gid);
      const gt = gtLookup[numId];
      const rawTitle = gt ? (gt['name_' + lang] || gt.name || ('#' + numId)) : ('#' + numId);
      advGuanoTypeOptions.push({
        value: numId,
        title: rawTitle,
        subtitle: gt ? (gt.scientific_name || '') : '',
        rawTitle: rawTitle,
        count: c,
      });
    }
  }
  advGuanoTypeOptions.sort(function(a, b) { return b.count - a.count || a.rawTitle.localeCompare(b.rawTitle); });

  const packageCounts = {};
  for (let i = 0; i < packageDetails.length; i++) {
    const pid = packageDetails[i].packageTypeId;
    if (pid != null) packageCounts[pid] = (packageCounts[pid] || 0) + 1;
  }
  const advPackageTypeOptions = [];
  for (const pid in packageCounts) {
    const c = packageCounts[pid];
    if (c > 0) {
      const numId = Number(pid);
      const pt = ptLookup[numId];
      const rawTitle = pt ? (pt['name_' + lang] || pt.name || ('#' + numId)) : ('#' + numId);
      const subParts = [];
      if (pt && pt.type_emballage) subParts.push(pt.type_emballage);
      if (pt && pt.size) subParts.push(pt.size + 'kg');
      advPackageTypeOptions.push({
        value: numId,
        title: rawTitle,
        subtitle: subParts.join(' • '),
        rawTitle: rawTitle,
        count: c,
      });
    }
  }
  advPackageTypeOptions.sort(function(a, b) { return b.count - a.count || a.rawTitle.localeCompare(b.rawTitle); });

  return {
    articleTypes: advArticleTypeOptions,
    warehouses: advWarehouseOptions,
    fishFamilies: advFishFamilyOptions,
    fishFreshFrozen: advFishFreshFrozenOptions,
    fishProcessTypes: advFishProcessTypeOptions,
    fishTypes: advFishTypeOptions,
    guanoTypes: advGuanoTypeOptions,
    packageTypes: advPackageTypeOptions,
  };
}

export default {
  filterWorkerFn,
  createListWorker,
  createGlobalFilterWorker,
  extractColumnOptions,
  extractAdvFilterOptions,
};
