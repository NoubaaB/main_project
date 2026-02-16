<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Charge;
use Illuminate\Http\Request;
use App\Models\SupplierInvoice;
use Illuminate\Support\Facades\DB;

class SupplierInvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $date_start = request()->query('date_start');
        $date_end = request()->query('date_end');
        $supplier_invoices = SupplierInvoice::whereBetween('date', [$date_start, $date_end])
            ->with(['charges'])
            ->orderBy("date")
            ->get();
        return response()->json(["supplier_invoices" => $supplier_invoices], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $data = $request->validate([
                'date' => 'required|date',
                'type_invoice' => 'required|string|in:local,export',
                'tracking_numbers' => 'required|array',
                'invoice_invoicer_ids' => 'required|array',
                'invoice_invoicer_ids.*' => 'exists:suppliers,id',
                'charge_ids' => 'required|array',
                'charge_ids.*' => 'exists:charges,id'
            ]);

        $row = DB::table('charges')
            ->whereIn("id", $data["charge_ids"])
            ->where('allow_invoice', true)
            ->whereNull('invoice_id')
            ->selectRaw('COALESCE(SUM(total_price),0) as total_price, COALESCE(SUM(total_weight),0) as total_weight')
            ->first();

        $supplier_invoice = SupplierInvoice::create([
            "date"=> $data["date"],
            "type_invoice"=> $data["type_invoice"],
            "total_price"=> $row->total_price,
            "total_weight"=> $row->total_weight,
            "tracking_numbers"=> $data["tracking_numbers"],
            "supplier_ids"=> $data["invoice_invoicer_ids"],
        ]);

        Charge::whereIn("id",$data["charge_ids"])
        ->where('allow_invoice', true)
        ->where('invoice_id', null)
        ->update(["invoice_id"=>$supplier_invoice->id]);
        
        $tracking_numbers = SupplierInvoice::generateNextTrackingNumbers();

        return response()->json(["supplier_invoice"=>$supplier_invoice, "tracking_numbers"=> $tracking_numbers],200);
    }

    /**
     * Display the specified resource.
     */
    public function show(SupplierInvoice $supplierInvoice)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SupplierInvoice $supplierInvoice)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SupplierInvoice $supplierInvoice)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SupplierInvoice $supplierInvoice)
    {
        //
    }

    public function supplierInvoicesMonthlyForSupplier(Request $request)
    {
        $requestedYear = (int) $request->query('year', now()->year);
        $metrice = $request->query('metrice');
        $supplier_id = (int)$request->query('supplier_id');
        $previousYear = $requestedYear - 1;

        // Aggregate sums grouped by year + month
        $rows = DB::table('supplier_invoices')
            ->whereNotNull('date')
            ->whereIn(DB::raw('YEAR(date)'), [$requestedYear, $previousYear])
            ->whereJsonContains('supplier_ids', $supplier_id)
            ->selectRaw("YEAR(date) as year, MONTH(date) as month, SUM($metrice) as total")
            ->groupBy('year', 'month')
            ->get();

        // initialize arrays with zeros for 12 months
        $currentYearTotals = array_fill(0, 12, 0.00);
        $previousYearTotals = array_fill(0, 12, 0.00);

        foreach ($rows as $r) {
            $monthIndex = ((int) $r->month) - 1;
            $total = (float) $r->total;
            if ((int) $r->year === $requestedYear) {
                $currentYearTotals[$monthIndex] = round($total, 2);
            } elseif ((int) $r->year === $previousYear) {
                $previousYearTotals[$monthIndex] = round($total, 2);
            }
        }

        return response()->json([
            'currentYear' => $currentYearTotals,
            'previousYear' => $previousYearTotals,
            'years' => [
                'current' => $requestedYear,
                'previous' => $previousYear,
            ],
        ]);
    }

    public function supplierInvoicesMonthlyOverview(Request $request)
    {
        $requestedYear = (int) $request->query('year', now()->year);
        $metrice = $request->query('metrice');
        $previousYear = $requestedYear - 1;
        // Aggregate sums grouped by year + month
        $rows = DB::table('supplier_invoices')
            ->whereNotNull('date')
            ->whereIn(DB::raw('YEAR(date)'), [$requestedYear, $previousYear])
            ->selectRaw("YEAR(date) as year, MONTH(date) as month, SUM($metrice) as total")
            ->groupBy('year', 'month')
            ->get();

        // initialize arrays with zeros for 12 months
        $currentYearTotals = array_fill(0, 12, 0.00);
        $previousYearTotals = array_fill(0, 12, 0.00);

        foreach ($rows as $r) {
            $monthIndex = ((int) $r->month) - 1;
            $total = (float) $r->total;
            if ((int) $r->year === $requestedYear) {
                $currentYearTotals[$monthIndex] = round($total, 2);
            } elseif ((int) $r->year === $previousYear) {
                $previousYearTotals[$monthIndex] = round($total, 2);
            }
        }

        return response()->json([
            'currentYear' => $currentYearTotals,
            'previousYear' => $previousYearTotals,
            'years' => [
                'current' => $requestedYear,
                'previous' => $previousYear,
            ],
        ]);
    }
    
    public function supplierInvoicesMonthlyCards(Request $request)
    {
        $period = $request->query('period', Carbon::now()->format('Y-m')); // e.g. "2025-02"
        // validate period
        try {
            $dt = Carbon::createFromFormat('Y-m', $period);
        } catch (\Exception $e) {
            $dt = Carbon::now();
            $period = $dt->format('Y-m');
        }

        $year = (int) $dt->format('Y');

        $prevYear = $year - 1;

        // 1) overall totals for current period
        $currentTotals = DB::table('supplier_invoices')
            ->whereYear('date', $year)
            ->selectRaw('COALESCE(SUM(total_price),0) as price, COALESCE(SUM(total_weight),0) as weight')
            ->first();

        $previousTotals = DB::table('supplier_invoices')
            ->whereYear('date', $prevYear)
            ->selectRaw('COALESCE(SUM(total_price),0) as price, COALESCE(SUM(total_weight),0) as weight')
            ->first();

        // 2) top supplier by price for current period (extract first supplier id/name from supplier_ids JSON array)
        // NOTE: this gets the first element of supplier_ids array. If supplier_ids is an array of IDs or names this will work.
        $topCurrent = DB::table('supplier_invoices')
            ->whereYear('date', $year)
            ->selectRaw(
                "COALESCE(JSON_UNQUOTE(JSON_EXTRACT(supplier_ids, '$[0]')), 'Unknown') as supplier,
                 COALESCE(SUM(total_price),0) as total_price,
                 COALESCE(SUM(total_weight),0) as total_weight"
            )
            ->groupBy('supplier')
            ->orderByDesc('total_price')
            ->limit(1)
            ->first();

        $topPrevious = DB::table('supplier_invoices')
            ->whereYear('date', $prevYear)
            ->selectRaw(
                "COALESCE(JSON_UNQUOTE(JSON_EXTRACT(supplier_ids, '$[0]')), 'Unknown') as supplier,
                 COALESCE(SUM(total_price),0) as total_price,
                 COALESCE(SUM(total_weight),0) as total_weight"
            )
            ->groupBy('supplier')
            ->orderByDesc('total_price')
            ->limit(1)
            ->first();

        // if no top found, normalize
        $topSupplierName = $topCurrent->supplier ?? ($topPrevious->supplier ?? 'Unknown');
        $topSupplierCurrent = [
            'price' => $topCurrent->total_price ?? 0.0,
            'weight' => $topCurrent->total_weight ?? 0.0,
        ];
        $topSupplierPrevious = [
            'price' => $topPrevious->total_price ?? 0.0,
            'weight' => $topPrevious->total_weight ?? 0.0,
        ];

        // 3) totals by type_invoice (local/export) for both periods
        $typesCurrentRows = DB::table('supplier_invoices')
            ->whereYear('date', $year)
            ->selectRaw("type_invoice, COALESCE(SUM(total_price),0) as price, COALESCE(SUM(total_weight),0) as weight")
            ->groupBy('type_invoice')
            ->get();

        $typesPreviousRows = DB::table('supplier_invoices')
            ->whereYear('date', $prevYear)
            ->selectRaw("type_invoice, COALESCE(SUM(total_price),0) as price, COALESCE(SUM(total_weight),0) as weight")
            ->groupBy('type_invoice')
            ->get();

        // normalize into structured arrays
        $typesCurrent = [
            'local' => ['price' => 0.0, 'weight' => 0.0],
            'export' => ['price' => 0.0, 'weight' => 0.0],
        ];
        foreach ($typesCurrentRows as $r) {
            $k = $r->type_invoice ?? 'local';
            if (!isset($typesCurrent[$k])) $typesCurrent[$k] = ['price' => 0.0, 'weight' => 0.0];
            $typesCurrent[$k] = ['price' => (float) $r->price, 'weight' => (float) $r->weight];
        }

        $typesPrevious = [
            'local' => ['price' => 0.0, 'weight' => 0.0],
            'export' => ['price' => 0.0, 'weight' => 0.0],
        ];
        foreach ($typesPreviousRows as $r) {
            $k = $r->type_invoice ?? 'local';
            if (!isset($typesPrevious[$k])) $typesPrevious[$k] = ['price' => 0.0, 'weight' => 0.0];
            $typesPrevious[$k] = ['price' => (float) $r->price, 'weight' => (float) $r->weight];
        }

        // donut series: current prices distribution between local and export
        $donutSeries = [
            (float) ($typesCurrent['local']['price'] ?? 0.0),
            (float) ($typesCurrent['export']['price'] ?? 0.0),
        ];

        return response()->json([
            'period' => $period,
            'current' => [
                'price' => (float) ($currentTotals->price ?? 0.0),
                'weight' => (float) ($currentTotals->weight ?? 0.0),
            ],
            'previous' => [
                'price' => (float) ($previousTotals->price ?? 0.0),
                'weight' => (float) ($previousTotals->weight ?? 0.0),
            ],
            'top_supplier' => [
                'name' => $topSupplierName,
                'current' => $topSupplierCurrent,
                'previous' => $topSupplierPrevious,
            ],
            'types' => [
                'current' => $typesCurrent,
                'previous' => $typesPrevious,
            ],
            'donut' => [
                'labels' => ['local', 'export'],
                'series' => $donutSeries,
            ],
        ]);
    }

    public function supplierInvoicesByIds() {
        $ids = request()->query('ids');
        $supplier_invoices = SupplierInvoice::findMany($ids);
        return response()->json(["supplier_invoices"=> $supplier_invoices],200);
    }
}
