<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Get audit logs for a specific record.
     * Usage: GET /api/audit-logs?auditable_type=Charge&auditable_id=123
     */
    public function index(Request $request)
    {
        $request->validate([
            'auditable_type' => 'required|string',
            'auditable_id'   => 'required|integer',
        ]);

        $logs = AuditLog::forRecord(
            $request->input('auditable_type'),
            $request->input('auditable_id')
        )
        ->orderByDesc('created_at')
        ->get();

        return response()->json(['audit_logs' => $logs]);
    }
}
