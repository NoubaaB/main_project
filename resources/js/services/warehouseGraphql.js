import axios from 'axios';

/**
 * Universal GraphQL Request Handler for /warehouses
 */
export async function graphqlRequest(query, variables = {}, operationName = null) {
  try {
    const payload = {
      query,
      variables,
    };
    if (operationName) {
      payload.operationName = operationName;
    }

    const response = await axios.post('/api/graphql', payload);

    if (response.data.errors && response.data.errors.length) {
      const firstError = response.data.errors[0];
      const errorMsg = firstError.message || 'GraphQL Execution Error';
      console.error('[GraphQL Error]:', response.data.errors);
      const err = new Error(errorMsg);
      err.graphQLErrors = response.data.errors;
      throw err;
    }

    return response.data.data;
  } catch (error) {
    console.error('[GraphQL Request Failed]:', error);
    throw error;
  }
}

/**
 * 1. Fetch Chambers Overview
 */
export async function gqlGetChambersOverview() {
  const query = `
    query GetChambersOverview {
      chambersOverview {
        id
        name
        category_name
        warehouse_type
        allow_production_effect
        has_sejour
        active_lots
        lots_count
        active_articles
        total_weight
        total_boxes
        valuation
        total_value
        is_empty
      }
    }
  `;
  const data = await graphqlRequest(query);
  return data?.chambersOverview || [];
}

/**
 * 2. Fetch Species Overview
 */
export async function gqlGetSpeciesOverview(warehouseIds = []) {
  const query = `
    query GetSpeciesOverview($warehouse_ids: [Int]) {
      speciesOverview(warehouse_ids: $warehouse_ids) {
        stockable_id
        stockable_type
        article_name
        species_name
        family_name
        process_name
        total_weight
        total_boxes
        total_valuation
        total_value
        wap
        lots_count
        active_lots_preview
      }
    }
  `;
  const data = await graphqlRequest(query, {
    warehouse_ids: warehouseIds && warehouseIds.length ? warehouseIds.map(Number) : null,
  });
  return data?.speciesOverview || [];
}

/**
 * 3. Fetch Stock Par Lot
 */
export async function gqlGetStockParLot({
  warehouseIds = [],
  stockableTypes = [],
  status = 'all',
  dateFrom = null,
  dateTo = null,
  search = null,
  page = 1,
  perPage = 20,
  sortBy = 'date',
  sortDir = 'desc',
} = {}) {
  const query = `
    query GetStockParLot(
      $warehouse_ids: [Int]
      $stockable_types: [String]
      $status: String
      $date_from: String
      $date_to: String
      $search: String
      $page: Int
      $per_page: Int
      $sort_by: String
      $sort_dir: String
    ) {
      stockParLot(
        warehouse_ids: $warehouse_ids
        stockable_types: $stockable_types
        status: $status
        date_from: $date_from
        date_to: $date_to
        search: $search
        page: $page
        per_page: $per_page
        sort_by: $sort_by
        sort_dir: $sort_dir
      ) {
        summary {
          total_lots
          in_stock_lots
          depleted_lots
          total_initial_weight
          total_remaining_weight
          total_exited_weight
          total_initial_boxes
          total_valuation
          occupied_chambers
        }
        pagination {
          current_page
          per_page
          total
          last_page
        }
        lots {
          lot_number
          raw_lot
          first_date
          last_date
          age_days
          supplier_name
          fishery_name
          origin_name
          contained_types
          primary_warehouse
          initial_weight
          remaining_weight
          exited_weight
          consumption_pct
          initial_boxes
          remaining_boxes
          exited_boxes
          total_valuation
          avg_unit_price
          status
          articles_count
          species_list
          operations_count
          articles {
            id
            warehouse_id
            warehouse_name
            article_name
            clean_type
            clean_source
            fish_family
            process
            date
            initial_weight
            remaining_weight
            exited_weight
            initial_boxes
            remaining_boxes
            package_type_name
            unit_price
            wap
            total_price
            is_depleted
            operations_count
            operations {
              id
              date
              source_type
              source_id
              weight
              package_count
              client_name
              unit_price
              total_price
            }
          }
        }
      }
    }
  `;
  const data = await graphqlRequest(query, {
    warehouse_ids: warehouseIds && warehouseIds.length ? warehouseIds.map(Number) : null,
    stockable_types: stockableTypes && stockableTypes.length ? stockableTypes : null,
    status: status || 'all',
    date_from: dateFrom || null,
    date_to: dateTo || null,
    search: search || null,
    page: page ? Number(page) : 1,
    per_page: perPage ? Number(perPage) : 20,
    sort_by: sortBy || 'date',
    sort_dir: sortDir || 'desc',
  });
  return data?.stockParLot || { lots: [], summary: null, pagination: null };
}

/**
 * 4. Fetch Stock Movements Journal
 */
export async function gqlGetStockMovementsJournal({
  warehouseIds = [],
  stockableTypes = [],
  type = null,
  search = null,
  limit = 200,
} = {}) {
  const query = `
    query GetStockMovementsJournal(
      $warehouse_ids: [Int]
      $stockable_types: [String]
      $type: String
      $search: String
      $limit: Int
    ) {
      stockMovementsJournal(
        warehouse_ids: $warehouse_ids
        stockable_types: $stockable_types
        type: $type
        search: $search
        limit: $limit
      ) {
        totals {
          total_count
          total_in_weight
          total_out_weight
          net_weight
        }
        movements {
          id
          date
          type
          action_label
          direction
          source_type
          source_id
          clean_source
          reference_number
          warehouse_id
          warehouse_name
          stockable_id
          clean_stockable_type
          article_name
          reception_tracking_number
          weight
          boxes
          unit_price
          total_price
          partner_name
          notes
        }
      }
    }
  `;
  const data = await graphqlRequest(query, {
    warehouse_ids: warehouseIds && warehouseIds.length ? warehouseIds.map(Number) : null,
    stockable_types: stockableTypes && stockableTypes.length ? stockableTypes : null,
    type: type || null,
    search: search || null,
    limit: limit || 200,
  });
  return data?.stockMovementsJournal || { totals: {}, movements: [] };
}

/**
 * 5. Fetch Analytics & Intelligence Suite
 */
export async function gqlGetWarehouseAnalytics({
  warehouseIds = [],
  stockableTypes = [],
  dateFrom = null,
  dateTo = null,
  granularity = 'day',
} = {}) {
  const query = `
    query GetWarehouseAnalytics(
      $warehouse_ids: [Int]
      $stockable_types: [String]
      $date_from: String
      $date_to: String
      $granularity: String
    ) {
      warehouseAnalytics(
        warehouse_ids: $warehouse_ids
        stockable_types: $stockable_types
        date_from: $date_from
        date_to: $date_to
        granularity: $granularity
      ) {
        date_range {
          from
          to
          days_count
        }
        granularity
        summary {
          in_weight
          out_weight
          net_weight
          in_boxes
          out_boxes
          net_boxes
          in_valuation
          out_valuation
          net_valuation
          movements_count
          in_count
          out_count
          active_lots_count
        }
        ratios {
          turnover_ratio_weight
          turnover_ratio_value
          out_to_in_weight_pct
        }
        aging {
          total_lots
          total_items
          total_weight
          total_valuation
          avg_age_days
          buckets {
            fresh { key label weight valuation lots lots_count pct color }
            optimal { key label weight valuation lots lots_count pct color }
            warning { key label weight valuation lots lots_count pct color }
            alert { key label weight valuation lots lots_count pct color }
          }
        }
        timeline {
          label
          in_weight
          out_weight
          net_weight
          in_boxes
          out_boxes
          in_valuation
          out_valuation
          movements_count
        }
        species {
          name
          weight
          boxes
          valuation
          pct_weight
          pct_valuation
        }
        families {
          name
          weight
          boxes
          valuation
          pct_weight
        }
        chambers {
          name
          weight
          boxes
          valuation
          pct_weight
        }
        partners {
          name
          weight
          boxes
          valuation
          pct_weight
        }
        tracking_numbers {
          name
          weight
          boxes
          valuation
          pct_weight
        }
      }
    }
  `;
  const data = await graphqlRequest(query, {
    warehouse_ids: warehouseIds && warehouseIds.length ? warehouseIds.map(Number) : null,
    stockable_types: stockableTypes && stockableTypes.length ? stockableTypes : null,
    date_from: dateFrom || null,
    date_to: dateTo || null,
    granularity: granularity || 'day',
  });
  return data?.warehouseAnalytics || null;
}

/**
 * 6. Fetch Traceability Entrance Vouchers (Stage 1)
 */
export async function gqlGetTraceabilityVouchers({
  trackingNumbers = [],
  status = 'all',
  dateFrom = null,
  dateTo = null,
} = {}) {
  const query = `
    query GetTraceabilityVouchers(
      $tracking_numbers: [String]
      $status: String
      $date_from: String
      $date_to: String
    ) {
      traceabilityVouchers(
        tracking_numbers: $tracking_numbers
        status: $status
        date_from: $date_from
        date_to: $date_to
      ) {
        key
        source_type
        parent_id
        voucher_type
        voucher_number
        date
        supplier_name
        total_weight
        remaining_weight
        exited_weight
        total_boxes
        status
        consumption_pct
        articles_count
        articles_preview
        article_types
        tracking_numbers
        balance_ids
      }
    }
  `;
  const data = await graphqlRequest(query, {
    tracking_numbers: trackingNumbers && trackingNumbers.length ? trackingNumbers.map(String) : null,
    status: status || 'all',
    date_from: dateFrom || null,
    date_to: dateTo || null,
  });
  return data?.traceabilityVouchers || [];
}

/**
 * 7. Fetch Traceability Voucher Articles (Stage 2)
 */
export async function gqlGetTraceabilityVoucherArticles(balanceIds = []) {
  const query = `
    query GetTraceabilityVoucherArticles($balance_ids: [Int]!) {
      traceabilityVoucherArticles(balance_ids: $balance_ids) {
        id
        article_name
        clean_type
        warehouse_name
        initial_weight
        remaining_weight
        remaining_boxes
        status
        wap
        total_price
      }
    }
  `;
  const data = await graphqlRequest(query, {
    balance_ids: balanceIds.map(Number),
  });
  return data?.traceabilityVoucherArticles || [];
}

/**
 * 8. Fetch Traceability Pedigree Timeline (Stage 3)
 */
export async function gqlGetTraceabilityPedigreeTimeline(balanceIds = []) {
  const query = `
    query GetTraceabilityPedigreeTimeline($balance_ids: [Int]!) {
      traceabilityPedigreeTimeline(balance_ids: $balance_ids) {
        status
        summary {
          tracking_numbers
          first_date
          last_date
          age_days
          initial_weight
          remaining_weight
          exited_weight
          consumption_pct
          total_boxes
          suppliers
          fisheries
          origins
          chambers
        }
        timeline {
          event_id
          event_action
          title
          type
          date
          weight
          boxes
          unit_price
          total_price
          warehouse
          partner
          lot_number
          article
          clean_type
          source_type
          source_id
          color
          icon
          is_inflow
        }
      }
    }
  `;
  const data = await graphqlRequest(query, {
    balance_ids: balanceIds.map(Number),
  });
  return data?.traceabilityPedigreeTimeline || null;
}

/**
 * 9. Fetch Lot Traceability (Modal Dialog)
 */
export async function gqlGetLotTraceability(lotNumber) {
  const query = `
    query GetLotTraceability($lot_number: String!) {
      lotTraceability(lot_number: $lot_number) {
        lot_number
        balances {
          id
          warehouse_id
          warehouse_name
          article_name
          total_weight
          remaining_weight
          status
        }
        articles {
          id
          article_name
          clean_type
          warehouse_name
          initial_weight
          remaining_weight
          remaining_boxes
          status
          wap
          total_price
        }
        timeline {
          event_id
          event_action
          title
          type
          date
          weight
          boxes
          unit_price
          total_price
          warehouse
          partner
          lot_number
          article
          clean_type
          source_type
          source_id
          color
          icon
          is_inflow
        }
        summary {
          tracking_numbers
          first_date
          last_date
          age_days
          initial_weight
          remaining_weight
          exited_weight
          consumption_pct
          total_boxes
          suppliers
          chambers
        }
      }
    }
  `;
  const data = await graphqlRequest(query, {
    lot_number: String(lotNumber),
  });
  return data?.lotTraceability || null;
}

/**
 * 10. Fetch Warehouses List & Categories
 */
export async function gqlGetWarehouses() {
  const query = `
    query GetWarehouses {
      warehouses {
        id
        name
        capacity
        warehouse_category_id
        category {
          id
          name
          description
        }
        created_at
        updated_at
      }
      warehouseCategories {
        id
        name
        description
      }
    }
  `;
  const data = await graphqlRequest(query);
  return {
    warehouses: data?.warehouses || [],
    categories: data?.warehouseCategories || [],
  };
}

export default {
  graphqlRequest,
  gqlGetChambersOverview,
  gqlGetSpeciesOverview,
  gqlGetStockParLot,
  gqlGetStockMovementsJournal,
  gqlGetWarehouseAnalytics,
  gqlGetTraceabilityVouchers,
  gqlGetTraceabilityVoucherArticles,
  gqlGetTraceabilityPedigreeTimeline,
  gqlGetLotTraceability,
  gqlGetWarehouses,
};
