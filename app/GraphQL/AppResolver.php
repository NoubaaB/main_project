<?php

namespace App\GraphQL;

class AppResolver
{
    public static function getRootValue(): array
    {
        return array_merge(
            WarehouseResolver::getRootValue(),
            ProductionGraphQL::getResolvers(),
            ChargeGraphQL::getResolvers(),
            ShippingGraphQL::getResolvers(),
            InvoiceGraphQL::getResolvers(),
            SettlementGraphQL::getResolvers(),
            OrderTransferGraphQL::getResolvers(),
            InventoryGraphQL::getResolvers(),
            UserStatisticsGraphQL::getResolvers(),
            WorkforceGraphQL::getResolvers()
        );
    }
}

