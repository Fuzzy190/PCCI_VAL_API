<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
// use App\Models\User;      // <--- ADD THIS
// use App\Models\Applicant; // <--- ADD THIS
// use App\Models\Member;    // <--- ADD THIS
use Illuminate\Support\Facades\Storage;
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

        if ($request->filled('type')) $query->where('transaction_type', $request->type);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        // Get the data (either paginated or all)
        $transactions = $request->boolean('all') ? $query->get() : $query->paginate($request->input('per_page', 20));

        // Define the transformation logic
        $transform = function ($txn) {
            if ($txn->proof_of_payment_path) {
                // Generate secure S3 URL
                $txn->proof_of_payment_url = Storage::disk('s3')->temporaryUrl(
                    $txn->proof_of_payment_path,
                    now()->addMinutes(30)
                );
            } else {
                $txn->proof_of_payment_url = null;
            }
            return $txn;
        };

        // Apply transformation based on return type
        if ($transactions instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $transactions->getCollection()->transform($transform);
        } else {
            $transactions->transform($transform);
        }

        return response()->json(['data' => $transactions]);
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
