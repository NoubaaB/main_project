<?php

namespace App\Http\Controllers;

use App\Models\OrderTransfer;
use App\Models\OrderTransferBeneficiary;
use App\Models\Supplier;
use App\Models\Client;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderTransferController extends Controller
{
    use BroadcastsModelChanges;

    /**
     * List order transfers with optional date range filtering.
     */
    public function index(Request $request)
    {
        $query = OrderTransfer::with(['beneficiaries', 'createdBy']);

        if ($request->has('date_start') && $request->has('date_end')) {
            $query->whereBetween('date', [$request->date_start, $request->date_end]);
        }

        $transfers = $query->orderBy('date', 'desc')->get();

        // Append beneficiary names
        $transfers->each(function ($transfer) {
            $transfer->beneficiary_names = $this->resolveBeneficiaryNames($transfer);
        });

        return response()->json(['order_transfers' => $transfers]);
    }

    /**
     * Store a new order transfer with beneficiaries.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'date'                       => 'required|date',
            'motif'                      => 'nullable|string|max:500',
            'amount'                     => 'required|numeric|min:0',
            'currency'                   => 'required|in:MAD,EUR,USD',
            'beneficiaries'              => 'required|array|min:1',
            'beneficiaries.*.type'       => 'required|in:supplier,client',
            'beneficiaries.*.id'         => 'required|integer',
        ]);

        return DB::transaction(function () use ($data) {
            $transferNumber = $this->generateTransferNumber();

            $transfer = OrderTransfer::create([
                'transfer_number' => $transferNumber,
                'date'            => $data['date'],
                'motif'           => $data['motif'] ?? null,
                'amount'          => $data['amount'],
                'currency'        => $data['currency'],
                'created_by'      => Auth::id(),
            ]);

            foreach ($data['beneficiaries'] as $ben) {
                OrderTransferBeneficiary::create([
                    'order_transfer_id' => $transfer->id,
                    'beneficiary_type'  => $ben['type'],
                    'beneficiary_id'    => $ben['id'],
                ]);
            }

            $transfer->load(['beneficiaries', 'createdBy']);
            $transfer->beneficiary_names = $this->resolveBeneficiaryNames($transfer);

            $this->broadcastChange('OrderTransfer', 'created', $transfer->id, $transfer->toArray());

            return response()->json(['order_transfer' => $transfer], 201);
        });
    }

    /**
     * Show a single order transfer.
     */
    public function show(OrderTransfer $orderTransfer)
    {
        $orderTransfer->load(['beneficiaries', 'createdBy']);
        $orderTransfer->beneficiary_names = $this->resolveBeneficiaryNames($orderTransfer);

        return response()->json(['order_transfer' => $orderTransfer]);
    }

    /**
     * Update an order transfer.
     */
    public function update(Request $request, OrderTransfer $orderTransfer)
    {
        $data = $request->validate([
            'date'                       => 'required|date',
            'motif'                      => 'nullable|string|max:500',
            'amount'                     => 'required|numeric|min:0',
            'currency'                   => 'required|in:MAD,EUR,USD',
            'beneficiaries'              => 'required|array|min:1',
            'beneficiaries.*.type'       => 'required|in:supplier,client',
            'beneficiaries.*.id'         => 'required|integer',
        ]);

        return DB::transaction(function () use ($data, $orderTransfer) {
            $orderTransfer->update([
                'date'     => $data['date'],
                'motif'    => $data['motif'] ?? null,
                'amount'   => $data['amount'],
                'currency' => $data['currency'],
            ]);

            // Sync beneficiaries
            $orderTransfer->beneficiaries()->delete();
            foreach ($data['beneficiaries'] as $ben) {
                OrderTransferBeneficiary::create([
                    'order_transfer_id' => $orderTransfer->id,
                    'beneficiary_type'  => $ben['type'],
                    'beneficiary_id'    => $ben['id'],
                ]);
            }

            $orderTransfer->load(['beneficiaries', 'createdBy']);
            $orderTransfer->beneficiary_names = $this->resolveBeneficiaryNames($orderTransfer);

            $this->broadcastChange('OrderTransfer', 'updated', $orderTransfer->id, $orderTransfer->toArray());

            return response()->json(['order_transfer' => $orderTransfer]);
        });
    }

    /**
     * Bulk delete order transfers by IDs.
     */
    public function destroy(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['message' => 'No IDs provided'], 400);
        }

        DB::transaction(function () use ($ids) {
            OrderTransfer::whereIn('id', $ids)->each(function ($t) {
                $t->beneficiaries()->delete();
                $t->delete();
            });
        });

        foreach ($ids as $id) {
            $this->broadcastChange('OrderTransfer', 'deleted', (int) $id);
        }

        return response()->json(['message' => 'Deleted', 'count' => count($ids)]);
    }

    /**
     * Analytics data for charts.
     */
    public function analytics(Request $request)
    {
        $query = OrderTransfer::query();

        if ($request->has('date_start') && $request->has('date_end')) {
            $query->whereBetween('date', [$request->date_start, $request->date_end]);
        }

        $byMonth = (clone $query)
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as period, currency, COUNT(*) as count, SUM(amount) as total")
            ->groupBy('period', 'currency')
            ->orderBy('period')
            ->get();

        $byCurrency = (clone $query)
            ->selectRaw("currency, COUNT(*) as count, SUM(amount) as total")
            ->groupBy('currency')
            ->get();

        return response()->json([
            'by_month'    => $byMonth,
            'by_currency' => $byCurrency,
        ]);
    }

    /**
     * Generate sequential transfer number: OV-{YYYY}-{SEQ}
     */
    private function generateTransferNumber(): string
    {
        $year   = date('Y');
        $prefix = "OV-{$year}-";

        $last = OrderTransfer::where('transfer_number', 'like', $prefix . '%')
            ->orderByRaw("CAST(SUBSTRING(transfer_number, " . (strlen($prefix) + 1) . ") AS UNSIGNED) DESC")
            ->first();

        $nextSeq = $last
            ? ((int) substr($last->transfer_number, strlen($prefix))) + 1
            : 1;

        return $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Resolve beneficiary names from the polymorphic pivot.
     */
    private function resolveBeneficiaryNames(OrderTransfer $transfer): array
    {
        $names = [];
        foreach ($transfer->beneficiaries as $ben) {
            if ($ben->beneficiary_type === 'supplier') {
                $supplier = Supplier::withTrashed()->find($ben->beneficiary_id);
                $names[] = [
                    'type' => 'supplier',
                    'id'   => $ben->beneficiary_id,
                    'name' => $supplier ? $supplier->name : 'N/A',
                ];
            } else {
                $client = Client::withTrashed()->find($ben->beneficiary_id);
                $names[] = [
                    'type' => 'client',
                    'id'   => $ben->beneficiary_id,
                    'name' => $client ? $client->name : 'N/A',
                ];
            }
        }
        return $names;
    }
}
