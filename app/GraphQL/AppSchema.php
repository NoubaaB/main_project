<?php

namespace App\GraphQL;

use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;

class AppSchema
{
    private static ?Schema $schema = null;

    public static function getSDL(): string
    {
        return implode("\n\n", [
            WarehouseSchema::getSDL(),
            CommonGraphQL::getSDL(),
            ProductionGraphQL::getSDL(),
            ChargeGraphQL::getSDL(),
            ShippingGraphQL::getSDL(),
            InvoiceGraphQL::getSDL(),
            SettlementGraphQL::getSDL(),
            OrderTransferGraphQL::getSDL(),
            InventoryGraphQL::getSDL(),
            UserStatisticsGraphQL::getSDL(),
            WorkforceGraphQL::getSDL(),
        ]);
    }

    public static function build(bool $forceFresh = false): Schema
    {
        if (self::$schema === null || $forceFresh) {
            self::$schema = BuildSchema::build(self::getSDL());
        }

        return self::$schema;
    }
}

