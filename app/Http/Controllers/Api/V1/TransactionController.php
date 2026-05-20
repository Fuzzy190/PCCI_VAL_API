<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;      // <--- ADD THIS
use App\Models\Applicant; // <--- ADD THIS
use App\Models\Member;    // <--- ADD THIS
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->hasAnyRole(['super_admin', 'admin', 'treasurer'])) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

        $query = Transaction::with(['processedBy', 'applicant', 'member.applicant'])
            ->latest();

        // Filter by type, status, or date...
        if ($request->filled('type')) $query->where('transaction_type', $request->type);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        // FIX: If 'all=true' is passed, fetch everything. Otherwise, paginate.
        if ($request->boolean('all')) {
            return response()->json(['data' => $query->get()]);
        }

        $perPage = $request->input('per_page', 20);
        return response()->json($query->paginate($perPage));
    }

    /**
     * Get high-level financial statistics
     */
    public function getStats(Request $request)
    {
        if (!$request->user()->hasAnyRole(['super_admin', 'admin', 'treasurer'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'total_revenue' => Transaction::where('status', 'approved')->sum('amount'),
            'initial_registration_total' => Transaction::where('transaction_type', 'initial_registration')->where('status', 'approved')->sum('amount'),
            'renewal_total' => Transaction::where('transaction_type', 'renewal')->where('status', 'approved')->sum('amount'),
        ]);
    }
}
