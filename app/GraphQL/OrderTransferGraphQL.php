<?php

namespace App\GraphQL;

use App\Http\Controllers\OrderTransferController;
use App\Models\OrderTransfer;
use Illuminate\Http\Request;

class OrderTransferGraphQL
{
    public static function getSDL(): string
    {
        return <<<GQL
extend type Query {
    orderTransfers(date_start: String, date_end: String): [OrderTransfer]
    orderTransfer(id: ID!): OrderTransfer
}

type OrderTransfer {
    id: ID!
    date: String
    transfer_number: String
    amount: Float
    currency: String
    motif: String
    notes: String
    created_by: Int
    created_by_user: UserSummary
    beneficiaries: [OrderTransferBeneficiary]
    beneficiary_names: [BeneficiaryNameItem]
    created_at: String
    updated_at: String
}

type OrderTransferBeneficiary {
    id: ID!
    order_transfer_id: Int
    beneficiary_type: String
    beneficiary_id: Int
    amount: Float
    rib: String
    bank_name: String
}

type BeneficiaryNameItem {
    id: ID
    name: String
    type: String
    amount: Float
}
GQL;
    }

    public static function getResolvers(): array
    {
        return [
            'orderTransfers' => function ($root, array $args, $context) {
                $controller = app(OrderTransferController::class);
                $resp = $controller->index(new Request($args));
                $data = $resp->getData(true);
                return $data['order_transfers'] ?? [];
            },
            'orderTransfer' => function ($root, array $args, $context) {
                $transfer = OrderTransfer::find($args['id'] ?? null);
                if (!$transfer) return null;
                $controller = app(OrderTransferController::class);
                $resp = $controller->show($transfer);
                $data = $resp->getData(true);
                return $data['order_transfer'] ?? null;
            },
        ];
    }
}

