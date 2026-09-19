<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\WorkforceFortnightPayment;

class WorkforcePaymentController extends Controller
{
    /**
     * Toggle the paid status for a single workforce period.
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'workforce_id' => 'required|exists:workforces,id',
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'fortnight_period' => 'required|in:Q1,Q2',
            'is_paid' => 'required|boolean',
        ]);

        $payment = WorkforceFortnightPayment::updateOrCreate(
            [
                'workforce_id' => $request->workforce_id,
                'year' => $request->year,
                'month' => $request->month,
                'fortnight_period' => $request->fortnight_period,
            ],
            ['is_paid' => $request->is_paid]
        );

        return response()->json([
            'message' => 'Payment status updated successfully.',
            'payment' => $payment
        ], 200);
    }

    /**
     * Bulk toggle the paid status for multiple workforces.
     */
    public function bulkToggle(Request $request): JsonResponse
    {
        $request->validate([
            'workforce_ids' => 'required|array',
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'fortnight_period' => 'required|in:Q1,Q2',
            'is_paid' => 'required|boolean',
        ]);

        // Filter to only existing workforces to avoid foreign key constraints
        $validWorkforceIds = \App\Models\Workforce::whereIn('id', $request->workforce_ids)->pluck('id')->toArray();

        $payments = [];
        foreach ($validWorkforceIds as $workforce_id) {
            $payments[] = WorkforceFortnightPayment::updateOrCreate(
                [
                    'workforce_id' => $workforce_id,
                    'year' => $request->year,
                    'month' => $request->month,
                    'fortnight_period' => $request->fortnight_period,
                ],
                ['is_paid' => $request->is_paid]
            );
        }

        return response()->json([
            'message' => 'Bulk payment status updated successfully.',
            'count' => count($payments)
        ], 200);
    }
}
