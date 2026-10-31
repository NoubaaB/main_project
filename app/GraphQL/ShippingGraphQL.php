<?php

namespace App\GraphQL;

use App\Http\Controllers\ShippingController;
use App\Models\Shipping;
use Illuminate\Http\Request;

class ShippingGraphQL
{
    public static function getSDL(): string
    {
        return <<<GQL
extend type Query {
    shippings(
        date_start: String
        date_end: String
        per_page: Int
        page: Int
        all: Boolean
    ): [Shipping]
    shipping(id: ID!): Shipping
}

type Shipping {
    id: ID!
    date: String
    shipping_category_id: Int
    shipping_category: ShippingCategory
    client_id: Int
    client: Client
    origin_id: Int
    origin: Origin
    invoice_id: Int
    client_invoice_id: Int
    client_invoice: InvoiceSummary
    shipping_tracking_number: [String]
    tracking_onp_numbers: [String]
    trucks: [Truck]
    detail_shippings: [DetailShipping]
    observation: String
    created_at: String
    updated_at: String
}

type ShippingCategory {
    id: ID!
    name: String
    name_fr: String
    name_en: String
    name_es: String
    description: String
}

type DetailShipping {
    id: ID!
    shipping_id: Int
    detail_shipping_category_id: Int
    detail_shipping_category: DetailShippingCategory
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
}

type DetailShippingCategory {
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
            'shippings' => function ($root, array $args, $context) {
                $controller = app(ShippingController::class);
                $resp = $controller->get_shippings(new Request($args));
                $data = $resp->getData(true);
                return $data['shippings'] ?? [];
            },
            'shipping' => function ($root, array $args, $context) {
                $shipping = Shipping::with([
                    'shippingCategory',
                    'client',
                    'trucks',
                    'origin',
                    'clientInvoice',
                    'detailShippings.detailShippingCategory',
                    'detailShippings.stockable',
                    'detailShippings.warehouse',
                    'detailShippings.packageType',
                ])->find($args['id'] ?? null);

                return $shipping ? $shipping->toArray() : null;
            },
        ];
    }
}

