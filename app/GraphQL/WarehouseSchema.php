<?php

namespace App\GraphQL;

use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;

class WarehouseSchema
{
    public static function getSDL(): string
    {
        return <<<GQL
type Query {
    warehouses: [Warehouse]
    warehouse(id: ID!): Warehouse
    warehouseCategories: [WarehouseCategory]
    chambersOverview: [ChamberOverview]
    speciesOverview(warehouse_ids: [Int]): [SpeciesOverview]
    stockParLot(
        warehouse_ids: [Int],
        stockable_types: [String],
        status: String,
        date_from: String,
        date_to: String,
        search: String,
        page: Int,
        per_page: Int,
        sort_by: String,
        sort_dir: String
    ): StockParLotResponse
    stockMovementsJournal(
        warehouse_ids: [Int],
        stockable_types: [String],
        type: String,
        search: String,
        limit: Int
    ): JournalResponse
    warehouseAnalytics(
        warehouse_ids: [Int],
        stockable_types: [String],
        date_from: String,
        date_to: String,
        granularity: String
    ): WarehouseAnalytics
    traceabilityVouchers(
        tracking_numbers: [String],
        status: String,
        date_from: String,
        date_to: String
    ): [TraceabilityVoucher]
    traceabilityVoucherArticles(balance_ids: [Int]!): [TraceabilityArticle]
    traceabilityPedigreeTimeline(balance_ids: [Int]!): TraceabilityPedigreeResponse
    lotTraceability(lot_number: String!): LotPedigreeResponse
}

type Mutation {
    createWarehouse(name: String!, capacity: Float, warehouse_category_id: Int): Warehouse
    updateWarehouse(id: ID!, name: String, capacity: Float, warehouse_category_id: Int): Warehouse
    deleteWarehouse(id: ID!): Boolean
}

type Warehouse {
    id: ID!
    name: String
    capacity: Float
    warehouse_category_id: Int
    category: WarehouseCategory
    created_at: String
    updated_at: String
}

type WarehouseCategory {
    id: ID!
    name: String
    description: String
}

type ChamberOverview {
    id: ID!
    name: String
    category_name: String
    warehouse_type: String
    allow_production_effect: Boolean
    has_sejour: Boolean
    active_lots: Int
    lots_count: Int
    active_articles: Int
    total_weight: Float
    total_boxes: Float
    valuation: Float
    total_value: Float
    is_empty: Boolean
}

type SpeciesOverview {
    stockable_id: ID
    stockable_type: String
    article_name: String
    species_name: String
    family_name: String
    process_name: String
    total_weight: Float
    total_boxes: Float
    total_valuation: Float
    total_value: Float
    wap: Float
    lots_count: Int
    active_lots_preview: [String]
}

type StockLotArticleOperation {
    id: ID
    date: String
    source_type: String
    source_id: Int
    weight: Float
    package_count: Float
    client_name: String
    unit_price: Float
    total_price: Float
}

type StockLotArticle {
    id: ID!
    warehouse_id: Int
    warehouse_name: String
    article_name: String
    clean_type: String
    clean_source: String
    fish_family: String
    process: String
    date: String
    initial_weight: Float
    remaining_weight: Float
    exited_weight: Float
    initial_boxes: Float
    remaining_boxes: Float
    package_type_name: String
    unit_price: Float
    wap: Float
    total_price: Float
    is_depleted: Boolean
    operations_count: Int
    operations: [StockLotArticleOperation]
}

type StockLotWarehouse {
    id: ID
    name: String
}

type StockLot {
    lot_number: String
    raw_lot: String
    first_date: String
    last_date: String
    age_days: Float
    supplier_name: String
    fishery_name: String
    origin_name: String
    contained_types: [String]
    warehouses: [StockLotWarehouse]
    primary_warehouse: String
    initial_weight: Float
    remaining_weight: Float
    exited_weight: Float
    consumption_pct: Float
    initial_boxes: Float
    remaining_boxes: Float
    exited_boxes: Float
    total_valuation: Float
    avg_unit_price: Float
    status: String
    articles_count: Int
    species_list: [String]
    articles: [StockLotArticle]
    operations_count: Int
}

type StockParLotSummary {
    total_lots: Int
    in_stock_lots: Int
    depleted_lots: Int
    total_initial_weight: Float
    total_remaining_weight: Float
    total_exited_weight: Float
    total_initial_boxes: Float
    total_valuation: Float
    occupied_chambers: Int
}

type StockParLotPagination {
    current_page: Int
    per_page: Int
    total: Int
    last_page: Int
}

type StockParLotResponse {
    lots: [StockLot]
    summary: StockParLotSummary
    pagination: StockParLotPagination
    balances: [WarehouseBalance]
    total_weight: Float
    total_boxes: Float
    total_value: Float
    total_count: Int
}

type WarehouseBalance {
    id: ID!
    warehouse_id: Int
    warehouse_name: String
    stockable_id: Int
    clean_stockable_type: String
    article_name: String
    family_name: String
    process_name: String
    reception_tracking_number: String
    clean_lot_number: String
    date: String
    total_weight: Float
    remaining_weight: Float
    package_count: Float
    remaining_boxes: Float
    price_per_kg: Float
    total_price: Float
    status: String
    age_days: Int
    source_type: String
    source_id: Int
    clean_source: String
    supplier_name: String
}

type JournalTotals {
    total_count: Int
    total_in_weight: Float
    total_out_weight: Float
    net_weight: Float
}

type StockMovement {
    id: ID!
    date: String
    type: String
    action_label: String
    direction: String
    source_type: String
    source_id: Int
    clean_source: String
    reference_number: String
    warehouse_id: Int
    warehouse_name: String
    stockable_id: Int
    clean_stockable_type: String
    article_name: String
    reception_tracking_number: String
    clean_lot_number: String
    lot_number: String
    weight: Float
    boxes: Float
    unit_price: Float
    total_price: Float
    partner_name: String
    notes: String
}

type JournalResponse {
    totals: JournalTotals
    movements: [StockMovement]
}

type WarehouseAnalytics {
    date_range: AnalyticsDateRange
    granularity: String
    summary: AnalyticsSummary
    ratios: AnalyticsRatios
    aging: AnalyticsAging
    timeline: [AnalyticsTimelinePoint]
    species: [AnalyticsSpeciesItem]
    families: [AnalyticsCategoryItem]
    chambers: [AnalyticsCategoryItem]
    partners: [AnalyticsCategoryItem]
    tracking_numbers: [AnalyticsCategoryItem]
}

type AnalyticsDateRange {
    from: String
    to: String
    days_count: Int
}

type AnalyticsSummary {
    in_weight: Float
    out_weight: Float
    net_weight: Float
    in_boxes: Float
    out_boxes: Float
    net_boxes: Float
    in_valuation: Float
    out_valuation: Float
    net_valuation: Float
    movements_count: Int
    in_count: Int
    out_count: Int
    active_lots_count: Int
}

type AnalyticsRatios {
    turnover_ratio_weight: Float
    turnover_ratio_value: Float
    out_to_in_weight_pct: Float
}

type AnalyticsAging {
    total_lots: Int
    total_items: Int
    total_weight: Float
    total_valuation: Float
    avg_age_days: Float
    buckets: AnalyticsAgingBuckets
    fresh: AgingBucket
    optimal: AgingBucket
    warning: AgingBucket
    alert: AgingBucket
    critical: AgingBucket
}

type AnalyticsAgingBuckets {
    fresh: AgingBucket
    optimal: AgingBucket
    warning: AgingBucket
    alert: AgingBucket
}

type AgingBucket {
    key: String
    label: String
    weight: Float
    valuation: Float
    lots: Int
    lots_count: Int
    pct: Float
    color: String
}

type AnalyticsTimelinePoint {
    label: String
    in_weight: Float
    out_weight: Float
    net_weight: Float
    in_boxes: Float
    out_boxes: Float
    in_valuation: Float
    out_valuation: Float
    movements_count: Int
}

type AnalyticsSpeciesItem {
    name: String
    weight: Float
    boxes: Float
    valuation: Float
    pct_weight: Float
    pct_valuation: Float
}

type AnalyticsCategoryItem {
    name: String
    weight: Float
    boxes: Float
    valuation: Float
    pct_weight: Float
}

type TraceabilityVoucher {
    key: String!
    source_type: String
    parent_id: Int
    voucher_type: String
    voucher_number: String
    date: String
    supplier_name: String
    total_weight: Float
    remaining_weight: Float
    exited_weight: Float
    total_boxes: Float
    status: String
    consumption_pct: Float
    articles_count: Int
    articles_preview: [String]
    article_types: [String]
    tracking_numbers: [String]
    balance_ids: [Int]
}

type TraceabilityArticle {
    id: ID!
    article_name: String
    clean_type: String
    warehouse_name: String
    initial_weight: Float
    remaining_weight: Float
    remaining_boxes: Float
    status: String
    wap: Float
    total_price: Float
}

type TraceabilityTimelineSummary {
    tracking_numbers: [String]
    first_date: String
    last_date: String
    age_days: Int
    initial_weight: Float
    remaining_weight: Float
    exited_weight: Float
    consumption_pct: Float
    total_boxes: Float
    suppliers: [String]
    fisheries: [String]
    origins: [String]
    chambers: [String]
}

type TraceabilityTimelineEvent {
    event_id: String
    event_action: String
    title: String
    type: String
    date: String
    weight: Float
    boxes: Float
    unit_price: Float
    total_price: Float
    warehouse: String
    partner: String
    lot_number: String
    article: String
    clean_type: String
    source_type: String
    source_id: Int
    color: String
    icon: String
    is_inflow: Boolean
}

type TraceabilityPedigreeResponse {
    status: String
    summary: TraceabilityTimelineSummary
    timeline: [TraceabilityTimelineEvent]
}

type LotPedigreeResponse {
    status: String
    lot_number: String
    balances: [WarehouseBalance]
    articles: [TraceabilityArticle]
    timeline: [TraceabilityTimelineEvent]
    summary: TraceabilityTimelineSummary
}
GQL;
    }

    public static function build(): Schema
    {
        return AppSchema::build();
    }
}
