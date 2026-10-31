<?php

namespace App\GraphQL;

use App\Http\Controllers\ChargeController;
use App\Models\Charge;
use Illuminate\Http\Request;

class ChargeGraphQL
{
    public static function getSDL(): string
    {
        return <<<GQL
extend type Query {
    charges(
        date_start: String
        date_end: String
        per_page: Int
        page: Int
        all: Boolean
    ): [Charge]
    charge(id: ID!): Charge
}

type Charge {
    id: ID!
    date: String
    charge_category_id: Int
    charge_category: ChargeCategory
    supplier_id: Int
    supplier: Supplier
    fishery_id: Int
    fishery: Fishery
    ship_id: Int
    ship: Ship
    origin_id: Int
    origin: Origin
    production_id: Int
    invoice_id: Int
    supplier_invoice_id: Int
    supplier_invoice: InvoiceSummary
    reception_tracking_number: [String]
    tracking_onp_numbers: [String]
    trucks: [Truck]
    detail_charges: [DetailCharge]
    quality_control: QualityControlSummary
    observation: String
    created_at: String
    updated_at: String
}

type ChargeCategory {
    id: ID!
    name: String
    name_fr: String
    name_en: String
    name_es: String
    is_production: Boolean
    description: String
}

type DetailCharge {
    id: ID!
    charge_id: Int
    detail_charge_category_id: Int
    detail_charge_category: DetailChargeCategory
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

type DetailChargeCategory {
    id: ID!
    name: String
    name_fr: String
    name_en: String
    name_es: String
}
GQL;
    }

    public static function getResolvers(): array
    {
        return [
            'charges' => function ($root, array $args, $context) {
                $controller = app(ChargeController::class);
                $resp = $controller->get_charges(new Request($args));
                $data = $resp->getData(true);
                return $data['charges'] ?? [];
            },
            'charge' => function ($root, array $args, $context) {
                $charge = Charge::with([
                    'chargeCategory',
                    'supplier',
                    'trucks',
                    'fishery',
                    'ship',
                    'origin',
                    'production',
                    'supplierInvoice',
                    'qualityControl',
                    'detailCharges.detailChargeCategory',
                    'detailCharges.stockable',
                    'detailCharges.warehouse',
                    'detailCharges.packageType',
                ])->find($args['id'] ?? null);

                return $charge ? $charge->toArray() : null;
            },
        ];
    }
}

