<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Supplier;
use App\Models\Shipping;
use App\Models\Production;
use App\Models\Charge;
use App\Models\ClientPayment;
use App\Models\SupplierPayment;
use Carbon\Carbon;

class PlafondService
{
    /**
     * Check if a client has reached or will reach their plafond.
     *
     * @param Client $client
     * @param string|null $date (Y-m-d)
     * @param float $newAmount (amount of the pending shipping/voucher)
     * @return array
     */
    public function checkClientPlafond(Client $client, ?string $date = null, float $newAmount = 0.0): array
    {
        if (!$client->enable_plafond || empty($client->plafond_amount) || (float)$client->plafond_amount <= 0) {
            return [
                'enable_plafond' => false,
                'plafond_amount' => 0.0,
                'block_on_plafond' => false,
                'plafond_mode' => $client->plafond_mode ?? 'monthly',
                'current_usage' => 0.0,
                'new_amount' => round((float)$newAmount, 2),
                'projected_usage' => round((float)$newAmount, 2),
                'is_reached' => false,
                'is_blocked' => false,
                'reason' => null,
                'message' => null,
            ];
        }

        $plafondAmount = (float)$client->plafond_amount;
        $mode = $client->plafond_mode ?: 'monthly';
        $scope = $client->plafond_blocking_scope ?: 'past_unpaid';
        $dateObj = $date ? Carbon::parse($date) : Carbon::today();
        $year = (int)$dateObj->year;
        $month = (int)$dateObj->month;

        if ($mode === 'yearly') {
            // YEARLY MODE:
            $shippingsTotal = (float) Shipping::where('client_id', $client->id)
                ->whereYear('date', $year)
                ->whereNull('deleted_at')
                ->sum('total_price');

            $productionsTotal = (float) Production::where('client_id', $client->id)
                ->whereYear('date', $year)
                ->whereNull('deleted_at')
                ->sum('total_price');

            $paymentsTotal = (float) ClientPayment::where('client_id', $client->id)
                ->whereYear('date', $year)
                ->whereNull('deleted_at')
                ->sum('amount');

            $currentUsage = max(0.0, round(($shippingsTotal + $productionsTotal) - $paymentsTotal, 2));
            $projectedUsage = round($currentUsage + (float)$newAmount, 2);

            $isReached = $projectedUsage >= $plafondAmount;
            $isBlocked = $isReached && (bool)$client->block_on_plafond;

            $message = null;
            if ($isReached) {
                if ($isBlocked) {
                    $message = "Plafond annuel de crédit atteint ({$projectedUsage} / {$plafondAmount} DH). La création du bon d'expédition est bloquée jusqu'au règlement des factures impayées.";
                } else {
                    $message = "Attention : Le client a atteint son plafond annuel de crédit ({$projectedUsage} / {$plafondAmount} DH). Veuillez contacter le client pour régler son crédit.";
                }
            }

            return [
                'enable_plafond' => true,
                'plafond_amount' => $plafondAmount,
                'block_on_plafond' => (bool)$client->block_on_plafond,
                'plafond_mode' => 'yearly',
                'current_usage' => $currentUsage,
                'new_amount' => round((float)$newAmount, 2),
                'projected_usage' => $projectedUsage,
                'is_reached' => $isReached,
                'is_blocked' => $isBlocked,
                'reason' => $isReached ? 'yearly_limit_reached' : null,
                'message' => $message,
                'shippings_total' => $shippingsTotal,
                'productions_total' => $productionsTotal,
                'payments_total' => $paymentsTotal,
                'year' => $year,
            ];
        }

        // MONTHLY MODE:
        $currentMonthShippings = (float) Shipping::where('client_id', $client->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereNull('deleted_at')
            ->sum('total_price');

        $currentMonthProductions = (float) Production::where('client_id', $client->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereNull('deleted_at')
            ->sum('total_price');

        $currentMonthPayments = (float) ClientPayment::where('client_id', $client->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereNull('deleted_at')
            ->sum('amount');

        $currentMonthUsage = max(0.0, round(($currentMonthShippings + $currentMonthProductions) - $currentMonthPayments, 2));
        $projectedMonthUsage = round($currentMonthUsage + (float)$newAmount, 2);

        $monthLimitReached = $projectedMonthUsage >= $plafondAmount;

        $pastUnpaidReached = false;
        $pastUnpaidBalance = 0.0;

        if ($scope === 'past_unpaid') {
            $startOfMonth = $dateObj->copy()->startOfMonth();
            $pastShippings = (float) Shipping::where('client_id', $client->id)
                ->where('date', '<', $startOfMonth->toDateString())
                ->whereNull('deleted_at')
                ->sum('total_price');

            $pastProductions = (float) Production::where('client_id', $client->id)
                ->where('date', '<', $startOfMonth->toDateString())
                ->whereNull('deleted_at')
                ->sum('total_price');

            $pastPayments = (float) ClientPayment::where('client_id', $client->id)
                ->where('date', '<', $startOfMonth->toDateString())
                ->whereNull('deleted_at')
                ->sum('amount');

            $pastUnpaidBalance = max(0.0, round(($pastShippings + $pastProductions) - $pastPayments, 2));

            if ($pastUnpaidBalance >= $plafondAmount) {
                $pastUnpaidReached = true;
            }
        }

        $isReached = $monthLimitReached || $pastUnpaidReached;
        $isBlocked = $isReached && (bool)$client->block_on_plafond;

        $reason = null;
        $message = null;
        if ($pastUnpaidReached) {
            $reason = 'past_unpaid_reached';
            if ($isBlocked) {
                $message = "Plafond mensuel de crédit bloqué : Le client a un impayé antérieur de " . number_format($pastUnpaidBalance, 2) . " DH atteignant le plafond ({$plafondAmount} DH). Veuillez contacter le client pour régler son crédit afin de débloquer les expéditions.";
            } else {
                $message = "Attention : Le client a un impayé antérieur de " . number_format($pastUnpaidBalance, 2) . " DH atteignant le plafond mensuel ({$plafondAmount} DH). Veuillez contacter le client pour régler son solde.";
            }
        } elseif ($monthLimitReached) {
            $reason = 'current_month_reached';
            if ($isBlocked) {
                $message = "Plafond mensuel de crédit atteint pour le mois {$month}/{$year} ({$projectedMonthUsage} / {$plafondAmount} DH). La création du bon d'expédition est bloquée jusqu'au règlement.";
            } else {
                $message = "Attention : Le client a atteint son plafond mensuel pour le mois {$month}/{$year} ({$projectedMonthUsage} / {$plafondAmount} DH). Veuillez contacter le client pour régler son solde.";
            }
        }

        return [
            'enable_plafond' => true,
            'plafond_amount' => $plafondAmount,
            'block_on_plafond' => (bool)$client->block_on_plafond,
            'plafond_mode' => 'monthly',
            'current_usage' => $currentMonthUsage,
            'new_amount' => round((float)$newAmount, 2),
            'projected_usage' => $projectedMonthUsage,
            'past_unpaid_balance' => $pastUnpaidBalance,
            'is_reached' => $isReached,
            'is_blocked' => $isBlocked,
            'reason' => $reason,
            'message' => $message,
            'year' => $year,
            'month' => $month,
        ];
    }

    /**
     * Check if a supplier has reached or will reach their plafond.
     *
     * @param Supplier $supplier
     * @param string|null $date (Y-m-d)
     * @param float $newAmount (amount of the pending charge/production voucher)
     * @return array
     */
    public function checkSupplierPlafond(Supplier $supplier, ?string $date = null, float $newAmount = 0.0): array
    {
        if (!$supplier->enable_plafond || empty($supplier->plafond_amount) || (float)$supplier->plafond_amount <= 0) {
            return [
                'enable_plafond' => false,
                'plafond_amount' => 0.0,
                'block_on_plafond' => false,
                'plafond_mode' => $supplier->plafond_mode ?? 'monthly',
                'current_usage' => 0.0,
                'new_amount' => round((float)$newAmount, 2),
                'projected_usage' => round((float)$newAmount, 2),
                'is_reached' => false,
                'is_blocked' => false,
                'reason' => null,
                'message' => null,
            ];
        }

        $plafondAmount = (float)$supplier->plafond_amount;
        $mode = $supplier->plafond_mode ?: 'monthly';
        $scope = $supplier->plafond_blocking_scope ?: 'past_unpaid';
        $dateObj = $date ? Carbon::parse($date) : Carbon::today();
        $year = (int)$dateObj->year;
        $month = (int)$dateObj->month;

        if ($mode === 'yearly') {
            // YEARLY MODE:
            $chargesTotal = (float) Charge::where('supplier_id', $supplier->id)
                ->whereYear('date', $year)
                ->whereNull('deleted_at')
                ->sum('total_price');

            $productionsTotal = (float) Production::where('supplier_id', $supplier->id)
                ->whereYear('date', $year)
                ->whereNull('deleted_at')
                ->sum('total_price');

            $paymentsTotal = (float) SupplierPayment::where('supplier_id', $supplier->id)
                ->whereYear('date', $year)
                ->whereNull('deleted_at')
                ->sum('amount');

            $currentUsage = max(0.0, round(($chargesTotal + $productionsTotal) - $paymentsTotal, 2));
            $projectedUsage = round($currentUsage + (float)$newAmount, 2);

            $isReached = $projectedUsage >= $plafondAmount;
            $isBlocked = $isReached && (bool)$supplier->block_on_plafond;

            $message = null;
            if ($isReached) {
                if ($isBlocked) {
                    $message = "Plafond annuel fournisseur atteint ({$projectedUsage} / {$plafondAmount} DH). La création des bons (charges/production) pour ce fournisseur est bloquée. Nous devons effectuer un règlement.";
                } else {
                    $message = "Attention : Nous avons atteint le plafond annuel avec ce fournisseur ({$projectedUsage} / {$plafondAmount} DH). Un règlement est requis.";
                }
            }

            return [
                'enable_plafond' => true,
                'plafond_amount' => $plafondAmount,
                'block_on_plafond' => (bool)$supplier->block_on_plafond,
                'plafond_mode' => 'yearly',
                'current_usage' => $currentUsage,
                'new_amount' => round((float)$newAmount, 2),
                'projected_usage' => $projectedUsage,
                'is_reached' => $isReached,
                'is_blocked' => $isBlocked,
                'reason' => $isReached ? 'yearly_limit_reached' : null,
                'message' => $message,
                'charges_total' => $chargesTotal,
                'productions_total' => $productionsTotal,
                'payments_total' => $paymentsTotal,
                'year' => $year,
            ];
        }

        // MONTHLY MODE:
        $currentMonthCharges = (float) Charge::where('supplier_id', $supplier->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereNull('deleted_at')
            ->sum('total_price');

        $currentMonthProductions = (float) Production::where('supplier_id', $supplier->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereNull('deleted_at')
            ->sum('total_price');

        $currentMonthPayments = (float) SupplierPayment::where('supplier_id', $supplier->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereNull('deleted_at')
            ->sum('amount');

        $currentMonthUsage = max(0.0, round(($currentMonthCharges + $currentMonthProductions) - $currentMonthPayments, 2));
        $projectedMonthUsage = round($currentMonthUsage + (float)$newAmount, 2);

        $monthLimitReached = $projectedMonthUsage >= $plafondAmount;

        $pastUnpaidReached = false;
        $pastUnpaidBalance = 0.0;

        if ($scope === 'past_unpaid') {
            $startOfMonth = $dateObj->copy()->startOfMonth();
            $pastCharges = (float) Charge::where('supplier_id', $supplier->id)
                ->where('date', '<', $startOfMonth->toDateString())
                ->whereNull('deleted_at')
                ->sum('total_price');

            $pastProductions = (float) Production::where('supplier_id', $supplier->id)
                ->where('date', '<', $startOfMonth->toDateString())
                ->whereNull('deleted_at')
                ->sum('total_price');

            $pastPayments = (float) SupplierPayment::where('supplier_id', $supplier->id)
                ->where('date', '<', $startOfMonth->toDateString())
                ->whereNull('deleted_at')
                ->sum('amount');

            $pastUnpaidBalance = max(0.0, round(($pastCharges + $pastProductions) - $pastPayments, 2));

            if ($pastUnpaidBalance >= $plafondAmount) {
                $pastUnpaidReached = true;
            }
        }

        $isReached = $monthLimitReached || $pastUnpaidReached;
        $isBlocked = $isReached && (bool)$supplier->block_on_plafond;

        $reason = null;
        $message = null;
        if ($pastUnpaidReached) {
            $reason = 'past_unpaid_reached';
            if ($isBlocked) {
                $message = "Plafond mensuel fournisseur bloqué : Nous avons un solde impayé antérieur de " . number_format($pastUnpaidBalance, 2) . " DH atteignant le plafond ({$plafondAmount} DH). La saisie est bloquée jusqu'à émission d'un règlement.";
            } else {
                $message = "Attention : Nous avons un solde impayé antérieur de " . number_format($pastUnpaidBalance, 2) . " DH atteignant le plafond mensuel ({$plafondAmount} DH) avec ce fournisseur. Veuillez prévoir un règlement.";
            }
        } elseif ($monthLimitReached) {
            $reason = 'current_month_reached';
            if ($isBlocked) {
                $message = "Plafond mensuel fournisseur atteint pour {$month}/{$year} ({$projectedMonthUsage} / {$plafondAmount} DH). La saisie est bloquée jusqu'à émission d'un règlement.";
            } else {
                $message = "Attention : Nous avons atteint le plafond mensuel pour {$month}/{$year} ({$projectedMonthUsage} / {$plafondAmount} DH) avec ce fournisseur. Un règlement est requis.";
            }
        }

        return [
            'enable_plafond' => true,
            'plafond_amount' => $plafondAmount,
            'block_on_plafond' => (bool)$supplier->block_on_plafond,
            'plafond_mode' => 'monthly',
            'current_usage' => $currentMonthUsage,
            'new_amount' => round((float)$newAmount, 2),
            'projected_usage' => $projectedMonthUsage,
            'past_unpaid_balance' => $pastUnpaidBalance,
            'is_reached' => $isReached,
            'is_blocked' => $isBlocked,
            'reason' => $reason,
            'message' => $message,
            'year' => $year,
            'month' => $month,
        ];
    }
}

