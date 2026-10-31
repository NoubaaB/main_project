<?php

namespace App\GraphQL;

use App\Http\Controllers\ClientPaymentController;
use App\Http\Controllers\SupplierPaymentController;
use Illuminate\Http\Request;

class SettlementGraphQL
{
    public static function getSDL(): string
    {
        return <<<GQL
extend type Query {
    supplierPayments(year: Int): [SupplierPayment]
    supplierBalance(year: Int): SupplierBalanceResponse
    supplierYearSummary(year: Int): SupplierYearSummaryResponse
    clientPayments(year: Int): [ClientPayment]
    clientBalance(year: Int): ClientBalanceResponse
    clientYearSummary(year: Int): ClientYearSummaryResponse
}

type SupplierPayment {
    id: ID!
    date: String
    supplier_id: Int
    supplier: Supplier
    supplier_invoice_id: Int
    supplier_invoice: InvoiceSummary
    payment_type: String
    amount: Float
    reference: String
    bank_name: String
    due_date: String
    notes: String
    created_at: String
    updated_at: String
}

type ClientPayment {
    id: ID!
    date: String
    client_id: Int
    client: Client
    client_invoice_id: Int
    client_invoice: InvoiceSummary
    payment_type: String
    amount: Float
    reference: String
    bank_name: String
    due_date: String
    notes: String
    created_at: String
    updated_at: String
}

type SettlementMonthData {
    month: Int
    invoices: Float
    payments: Float
    balance: Float
}

type SettlementBalanceRow {
    id: ID
    supplier_id: Int
    client_id: Int
    name: String
    carry_forward: Float
    months: [SettlementMonthData]
}

type SupplierBalanceResponse {
    balance: [SettlementBalanceRow]
    carry_forward_total: Float
}

type ClientBalanceResponse {
    balance: [SettlementBalanceRow]
    carry_forward_total: Float
}

type FiscalYearClosingSummary {
    id: ID
    year: Int
    is_closed: Boolean
    closed_at: String
    closed_by: String
    notes: String
}

type SettlementSummaryItem {
    id: ID
    supplier_id: Int
    client_id: Int
    name: String
    supplier_name: String
    client_name: String
    carry_forward: Float
    total_invoices: Float
    total_payments: Float
    balance: Float
    invoice_count: Int
    payment_count: Int
}

type SupplierYearSummaryResponse {
    summary: [SettlementSummaryItem]
    closing: FiscalYearClosingSummary
}

type ClientYearSummaryResponse {
    summary: [SettlementSummaryItem]
    closing: FiscalYearClosingSummary
}
GQL;
    }

    public static function getResolvers(): array
    {
        return [
            'supplierPayments' => function ($root, array $args, $context) {
                $controller = app(SupplierPaymentController::class);
                $resp = $controller->index(new Request($args));
                $data = $resp->getData(true);
                return $data['payments'] ?? [];
            },
            'supplierBalance' => function ($root, array $args, $context) {
                $controller = app(SupplierPaymentController::class);
                $resp = $controller->supplierBalance(new Request($args));
                $data = $resp->getData(true);
                $suppliers = $data['suppliers'] ?? [];
                $rows = [];
                foreach ($data['balance'] ?? [] as $row) {
                    $sid = $row['supplier_id'] ?? null;
                    $monthsList = [];
                    foreach ($row['months'] ?? [] as $m => $mdata) {
                        $monthsList[] = [
                            'month' => (int) $m,
                            'invoices' => (float) ($mdata['invoices'] ?? 0),
                            'payments' => (float) ($mdata['payments'] ?? 0),
                            'balance' => (float) ($mdata['balance'] ?? 0),
                        ];
                    }
                    $rows[] = [
                        'id' => $sid,
                        'supplier_id' => $sid,
                        'name' => $suppliers[$sid] ?? ('Supplier #' . $sid),
                        'carry_forward' => (float) ($row['carry_forward'] ?? 0),
                        'months' => $monthsList,
                    ];
                }
                return [
                    'balance' => $rows,
                    'carry_forward_total' => (float) ($data['carry_forward_total'] ?? 0),
                ];
            },
            'supplierYearSummary' => function ($root, array $args, $context) {
                $controller = app(SupplierPaymentController::class);
                $resp = $controller->yearSummary(new Request($args));
                $data = $resp->getData(true);
                $summary = [];
                foreach ($data['summary'] ?? [] as $s) {
                    $summary[] = [
                        'id' => $s['supplier_id'] ?? null,
                        'supplier_id' => $s['supplier_id'] ?? null,
                        'name' => $s['supplier_name'] ?? '',
                        'supplier_name' => $s['supplier_name'] ?? '',
                        'carry_forward' => (float) ($s['carry_forward'] ?? 0),
                        'total_invoices' => (float) ($s['total_invoices'] ?? 0),
                        'total_payments' => (float) ($s['total_payments'] ?? 0),
                        'balance' => (float) ($s['balance'] ?? 0),
                        'invoice_count' => (int) ($s['invoice_count'] ?? 0),
                        'payment_count' => (int) ($s['payment_count'] ?? 0),
                    ];
                }
                return [
                    'summary' => $summary,
                    'closing' => $data['closing'] ?? null,
                ];
            },
            'clientPayments' => function ($root, array $args, $context) {
                $controller = app(ClientPaymentController::class);
                $resp = $controller->index(new Request($args));
                $data = $resp->getData(true);
                return $data['payments'] ?? [];
            },
            'clientBalance' => function ($root, array $args, $context) {
                $controller = app(ClientPaymentController::class);
                $resp = $controller->clientBalance(new Request($args));
                $data = $resp->getData(true);
                $clients = $data['clients'] ?? [];
                $rows = [];
                foreach ($data['balance'] ?? [] as $row) {
                    $cid = $row['client_id'] ?? null;
                    $monthsList = [];
                    foreach ($row['months'] ?? [] as $m => $mdata) {
                        $monthsList[] = [
                            'month' => (int) $m,
                            'invoices' => (float) ($mdata['invoices'] ?? 0),
                            'payments' => (float) ($mdata['payments'] ?? 0),
                            'balance' => (float) ($mdata['balance'] ?? 0),
                        ];
                    }
                    $rows[] = [
                        'id' => $cid,
                        'client_id' => $cid,
                        'name' => $clients[$cid] ?? ('Client #' . $cid),
                        'carry_forward' => (float) ($row['carry_forward'] ?? 0),
                        'months' => $monthsList,
                    ];
                }
                return [
                    'balance' => $rows,
                    'carry_forward_total' => (float) ($data['carry_forward_total'] ?? 0),
                ];
            },
            'clientYearSummary' => function ($root, array $args, $context) {
                $controller = app(ClientPaymentController::class);
                $resp = $controller->yearSummary(new Request($args));
                $data = $resp->getData(true);
                $summary = [];
                foreach ($data['summary'] ?? [] as $s) {
                    $summary[] = [
                        'id' => $s['client_id'] ?? null,
                        'client_id' => $s['client_id'] ?? null,
                        'name' => $s['client_name'] ?? '',
                        'client_name' => $s['client_name'] ?? '',
                        'carry_forward' => (float) ($s['carry_forward'] ?? 0),
                        'total_invoices' => (float) ($s['total_invoices'] ?? 0),
                        'total_payments' => (float) ($s['total_payments'] ?? 0),
                        'balance' => (float) ($s['balance'] ?? 0),
                        'invoice_count' => (int) ($s['invoice_count'] ?? 0),
                        'payment_count' => (int) ($s['payment_count'] ?? 0),
                    ];
                }
                return [
                    'summary' => $summary,
                    'closing' => $data['closing'] ?? null,
                ];
            },
        ];
    }
}

