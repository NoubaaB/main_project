<?php

namespace App\GraphQL;

use App\Models\ClientInvoice;
use App\Models\SupplierInvoice;

class InvoiceGraphQL
{
    public static function getSDL(): string
    {
        return <<<GQL
extend type Query {
    supplierInvoices(date_start: String, date_end: String): [SupplierInvoice]
    supplierInvoice(id: ID!): SupplierInvoice
    clientInvoices(date_start: String, date_end: String): [ClientInvoice]
    clientInvoice(id: ID!): ClientInvoice
}

type SupplierInvoice {
    id: ID!
    date: String
    type_invoice: String
    tracking_numbers: [String]
    supplier_id: Int
    supplier: Supplier
    total_price: Float
    charges: [Charge]
    productions: [Production]
    created_at: String
    updated_at: String
}

type ClientInvoice {
    id: ID!
    date: String
    type_invoice: String
    tracking_numbers: [String]
    client_id: Int
    client: Client
    total_price: Float
    incoterm: String
    currency: String
    shippings: [Shipping]
    productions: [Production]
    created_at: String
    updated_at: String
}
GQL;
    }

    public static function getResolvers(): array
    {
        return [
            'supplierInvoices' => function ($root, array $args, $context) {
                $query = SupplierInvoice::with([
                    'charges.supplier',
                    'charges.trucks',
                    'charges.chargeCategory',
                    'charges.fishery',
                    'charges.ship',
                    'charges.origin',
                    'productions.productionCategory',
                    'productions.supplier',
                    'supplier',
                ])->orderBy('date', 'desc');

                if (!empty($args['date_start']) && !empty($args['date_end'])) {
                    $query->whereBetween('date', [$args['date_start'], $args['date_end']]);
                }

                return $query->get()->toArray();
            },
            'supplierInvoice' => function ($root, array $args, $context) {
                $inv = SupplierInvoice::with([
                    'charges.supplier',
                    'charges.trucks',
                    'charges.chargeCategory',
                    'charges.fishery',
                    'charges.ship',
                    'charges.origin',
                    'productions.productionCategory',
                    'productions.supplier',
                    'supplier',
                ])->find($args['id'] ?? null);

                return $inv ? $inv->toArray() : null;
            },
            'clientInvoices' => function ($root, array $args, $context) {
                $query = ClientInvoice::with([
                    'client',
                    'shippings.client',
                    'shippings.shippingCategory',
                    'productions.client',
                    'productions.productionCategory',
                ])->orderBy('date', 'desc');

                if (!empty($args['date_start']) && !empty($args['date_end'])) {
                    $query->whereBetween('date', [$args['date_start'], $args['date_end']]);
                }

                return $query->get()->toArray();
            },
            'clientInvoice' => function ($root, array $args, $context) {
                $inv = ClientInvoice::with([
                    'client',
                    'shippings.client',
                    'shippings.shippingCategory',
                    'productions.client',
                    'productions.productionCategory',
                ])->find($args['id'] ?? null);

                return $inv ? $inv->toArray() : null;
            },
        ];
    }
}

