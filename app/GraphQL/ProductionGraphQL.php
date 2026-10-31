<?php

namespace App\GraphQL;

use App\Http\Controllers\ProductionController;
use App\Models\Production;
use Illuminate\Http\Request;

class ProductionGraphQL
{
    public static function getSDL(): string
    {
        return <<<GQL
extend type Query {
    productions(
        date_start: String
        date_end: String
        per_page: Int
        page: Int
        all: Boolean
    ): [Production]
    production(id: ID!): Production
    productionAnalytics(
        date_start: String
        date_end: String
        mode: String
    ): ProductionAnalytics
}

type Production {
    id: ID!
    date: String
    production_category_id: Int
    production_category: ProductionCategory
    reception_tracking_number: [String]
    tracking_onp_numbers: [String]
    production_tracking_number: [String]
    production_type: String
    fishery_id: Int
    ship_id: Int
    origin_id: Int
    charge_id: Int
    supplier_id: Int
    client_id: Int
    supplier_invoice_id: Int
    client_invoice_id: Int
    supplier: Supplier
    client: Client
    charge: ChargeSummary
    supplier_invoice: InvoiceSummary
    client_invoice: InvoiceSummary
    observation: String
    total_weight: Float
    total_price: Float
    total_secondary_packaging_cost: Float
    detail_productions: [DetailProduction]
    created_at: String
    updated_at: String
}

type ProductionCategory {
    id: ID!
    name: String
    name_fr: String
    name_en: String
    name_es: String
    description: String
}

type DetailProduction {
    id: ID!
    production_id: Int
    detail_production_category_id: Int
    detail_production_category: DetailProductionCategory
    stockable_id: Int
    stockable_type: String
    stockable: StockableItem
    warehouse_id: Int
    warehouse: Warehouse
    package_type_id: Int
    package_type: PackageType
    weight: Float
    package_count: Float
    unit_price: Float
    total_price: Float
    temperature: Float
    is_rejected: Boolean
}

type DetailProductionCategory {
    id: ID!
    name: String
    name_fr: String
    name_en: String
    name_es: String
}

type ProductionYieldSeries {
    name: String
    color: String
    data: [Point2D]
}

type ProductionAnalytics {
    fresh_reception: [Point2D]
    frozen_production: [Point2D]
    guano_production: [Point2D]
    equilibrium: [Point2D]
    yield_series: [ProductionYieldSeries]
}
GQL;
    }

    public static function getResolvers(): array
    {
        return [
            'productions' => function ($root, array $args, $context) {
                $controller = app(ProductionController::class);
                $resp = $controller->index(new Request($args));
                $data = $resp->getData(true);
                return $data['productions'] ?? [];
            },
            'production' => function ($root, array $args, $context) {
                $prod = Production::with([
                    'productionCategory',
                    'supplier',
                    'client',
                    'charge',
                    'supplierInvoice',
                    'clientInvoice',
                    'detailProductions.detailProductionCategory',
                    'detailProductions.stockable',
                    'detailProductions.warehouse',
                    'detailProductions.packageType',
                ])->find($args['id'] ?? null);

                return $prod ? $prod->toArray() : null;
            },
            'productionAnalytics' => function ($root, array $args, $context) {
                $controller = app(ProductionController::class);
                $resp = $controller->analytics(new Request($args));
                return $resp->getData(true);
            },
        ];
    }
}

