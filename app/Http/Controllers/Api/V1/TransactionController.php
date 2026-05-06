<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Display a listing of all global transactions (The General Ledger)
     */
    public function index(Request $request)
    {
        // Only allow admins and treasurers to view the ledger
        if (!$request->user()->hasAnyRole(['super_admin', 'admin', 'treasurer'])) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

        // FIX: We must eager-load 'applicant' and 'member.applicant' so the frontend receives the business names!
        $query = Transaction::with(['processedBy', 'applicant', 'member.applicant']); 

        // Filter by Transaction Type (e.g., ?type=renewal)
        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }

        // Filter by Status (e.g., ?status=approved)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by Date Range (e.g., ?start_date=2026-01-01&end_date=2026-12-31)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00', 
                $request->end_date . ' 23:59:59'
            ]);
        }

        $perPage = $request->input('per_page', 20);
        
        // Return paginated results, newest first
        $transactions = $query->latest()->paginate($perPage);

        return response()->json($transactions);
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