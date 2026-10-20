<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Charge;
use App\Models\Production;
use App\Models\Shipping;
use App\Models\Warehouse;
use App\Models\WarehouseBalance;
use App\Models\WarehouseTransfer;
use App\Models\User;

class HomeController extends Controller
{
    /**
     * GET /api/home/overview
     *
     * Provides aggregated real-time KPIs and recent activity
     * for the Home Welcome Dashboard.
     */
    public function overview()
    {
        $now = Carbon::now();
        $today = $now->toDateString();
        $startOfMonth = $now->copy()->startOfMonth()->toDateString();
        $endOfMonth = $now->copy()->endOfMonth()->toDateString();

        // 1. Charges KPIs
        $chargesToday = Charge::whereDate('date', $today)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total_weight), 0) as weight, COALESCE(SUM(total_price), 0) as price')
            ->first();

        $chargesMonth = Charge::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total_weight), 0) as weight, COALESCE(SUM(total_price), 0) as price')
            ->first();

        $chargesTotalCount = Charge::count();

        // 2. Production KPIs
        $productionToday = Production::whereDate('date', $today)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total_weight), 0) as weight, COALESCE(SUM(frozen_production_weight), 0) as frozen_weight')
            ->first();

        $productionMonth = Production::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total_weight), 0) as weight')
            ->first();

        $productionTotalCount = Production::count();

        // 3. Shipping KPIs
        $shippingToday = Shipping::whereDate('date', $today)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total_weight), 0) as weight, COALESCE(SUM(total_price), 0) as price')
            ->first();

        $shippingMonth = Shipping::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total_weight), 0) as weight')
            ->first();

        $shippingTotalCount = Shipping::count();

        // 4. Warehouse & Stock KPIs
        $totalWarehouses = Warehouse::count();
        $stockBalances = WarehouseBalance::where('is_finished', false)
            ->selectRaw('COALESCE(SUM(remaining_weight), 0) as stored_weight, COALESCE(SUM(package_count), 0) as total_packages')
            ->first();

        // 5. Warehouse Transfers KPIs
        $transfersToday = WarehouseTransfer::whereDate('date', $today)->count();
        $transfersMonth = WarehouseTransfer::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total_weight), 0) as weight')
            ->first();

        // 6. Recent Activity across modules (latest items for quick jump)
        $recentCharges = Charge::with('supplier:id,name')
            ->orderBy('created_at', 'desc')
            ->take(4)
            ->get(['id', 'date', 'reception_tracking_number', 'total_weight', 'total_price', 'supplier_id', 'created_at']);

        $recentProductions = Production::orderBy('created_at', 'desc')
            ->take(4)
            ->get(['id', 'date', 'production_tracking_number', 'production_type', 'total_weight', 'created_at']);

        $recentShippings = Shipping::with('client:id,name')
            ->orderBy('created_at', 'desc')
            ->take(4)
            ->get(['id', 'date', 'shipping_tracking_number', 'total_weight', 'client_id', 'created_at']);

        $recentTransfers = WarehouseTransfer::orderBy('created_at', 'desc')
            ->take(4)
            ->get(['id', 'date', 'transfer_tracking_number', 'total_weight', 'created_at']);

        // Determine shift by hour
        $hour = $now->hour;
        if ($hour >= 6 && $hour < 14) {
            $shift = 'Morning Shift (A)';
        } elseif ($hour >= 14 && $hour < 22) {
            $shift = 'Afternoon Shift (B)';
        } else {
            $shift = 'Night Shift (C)';
        }

        return response()->json([
            'success' => true,
            'kpis' => [
                'charges' => [
                    'today_count'  => (int) $chargesToday->cnt,
                    'today_weight' => round((float) $chargesToday->weight, 2),
                    'today_price'  => round((float) $chargesToday->price, 2),
                    'month_count'  => (int) $chargesMonth->cnt,
                    'month_weight' => round((float) $chargesMonth->weight, 2),
                    'month_price'  => round((float) $chargesMonth->price, 2),
                    'total_count'  => (int) $chargesTotalCount,
                ],
                'productions' => [
                    'today_count'   => (int) $productionToday->cnt,
                    'today_weight'  => round((float) $productionToday->weight, 2),
                    'frozen_weight' => round((float) $productionToday->frozen_weight, 2),
                    'month_count'   => (int) $productionMonth->cnt,
                    'month_weight'  => round((float) $productionMonth->weight, 2),
                    'total_count'   => (int) $productionTotalCount,
                ],
                'shippings' => [
                    'today_count'  => (int) $shippingToday->cnt,
                    'today_weight' => round((float) $shippingToday->weight, 2),
                    'today_price'  => round((float) $shippingToday->price, 2),
                    'month_count'  => (int) $shippingMonth->cnt,
                    'month_weight' => round((float) $shippingMonth->weight, 2),
                    'total_count'  => (int) $shippingTotalCount,
                ],
                'warehouses' => [
                    'total_warehouses'  => (int) $totalWarehouses,
                    'stored_weight'     => round((float) ($stockBalances->stored_weight ?? 0), 2),
                    'stored_packages'   => (int) ($stockBalances->total_packages ?? 0),
                ],
                'transfers' => [
                    'today_count'  => (int) $transfersToday,
                    'month_count'  => (int) ($transfersMonth->cnt ?? 0),
                    'month_weight' => round((float) ($transfersMonth->weight ?? 0), 2),
                ],
            ],
            'recent_activity' => [
                'charges'     => $recentCharges,
                'productions' => $recentProductions,
                'shippings'   => $recentShippings,
                'transfers'   => $recentTransfers,
            ],
            'system' => [
                'server_time' => $now->toIso8601String(),
                'shift'       => $shift,
                'user_count'  => User::where('is_active', true)->count(),
            ],
        ]);
    }
}
