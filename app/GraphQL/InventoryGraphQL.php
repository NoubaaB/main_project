<?php

namespace App\GraphQL;

use App\Http\Controllers\InventoryController;
use App\Models\Inventory;
use Illuminate\Http\Request;

class InventoryGraphQL
{
    public static function getSDL(): string
    {
        return <<<GQL
extend type Query {
    inventories(
        date_start: String
        date_end: String
        per_page: Int
        page: Int
        all: Boolean
    ): [Inventory]
    inventory(id: ID!): Inventory
}

type Inventory {
    id: ID!
    date: String
    inventory_category_id: Int
    inventory_tracking_numbers: [String]
    observation: String
    detail_inventories: [DetailInventory]
    created_at: String
    updated_at: String
}

type DetailInventory {
    id: ID!
    inventory_id: Int
    detail_inventory_category_id: Int
    detail_inventory_category: DetailInventoryCategory
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
    operation_type: String
}

type DetailInventoryCategory {
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
            'inventories' => function ($root, array $args, $context) {
                $controller = app(InventoryController::class);
                $resp = $controller->get_inventories(new Request($args));
                $data = $resp->getData(true);
                return $data['inventories'] ?? [];
            },
            'inventory' => function ($root, array $args, $context) {
                $inv = Inventory::with([
                    'detailInventories.detailInventoryCategory',
                    'detailInventories.stockable',
                    'detailInventories.warehouse',
                    'detailInventories.packageType',
                ])->find($args['id'] ?? null);

                return $inv ? $inv->toArray() : null;
            },
        ];
    }
}

