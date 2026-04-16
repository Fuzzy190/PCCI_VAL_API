<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDuesPaymentRequest;
use App\Http\Resources\DuesPaymentResource;
use App\Models\DuesPayment;
use App\Models\MembershipDue;
use Illuminate\Http\Request;

class DuesPaymentController extends Controller
{
    /**
     * Display a listing of dues payments
     * 
     * Query Parameters:
     * - membership_due_id: Filter by membership due
     * - member_id: Filter by member
     * - year: Filter payments by year
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Role-based access control
        if ($user->hasAnyRole(['super_admin', 'admin', 'treasurer'])) {
            $query = DuesPayment::query();
        } else {
            return response()->json([
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Optional filters
        if ($request->filled('membership_due_id')) {
            $query->where('membership_due_id', $request->membership_due_id);
        }

        if ($request->filled('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('year')) {
            $query->whereYear('payment_date', $request->year);
        }

        $payments = $query->with(['membershipDue', 'member', 'receivedBy', 'submittedBy'])
            ->orderBy('payment_date', 'desc')
            ->paginate(20);

        return DuesPaymentResource::collection($payments);
    }

    /**
     * Store a newly created dues payment
     * 
     * IMPORTANT: Payment year must match the due year
     */
    public function store(StoreDuesPaymentRequest $request)
    {
        $validated = $request->validated();
        $membershipDue = MembershipDue::find($validated['membership_due_id']);

        // Validate payment
        $errors = DuesPayment::validatePayment($membershipDue, $validated);
        if (!empty($errors)) {
            return response()->json([
                'message' => 'Payment validation failed',
                'errors' => $errors
            ], 422);
        }

        // Record the payment
        $success = DuesPayment::recordPayment(
            $membershipDue,
            $request->user(),
            $validated
        );

        if (!$success) {
            return response()->json([
                'message' => 'Payment year does not match the due year. ' .
                    'Only the year of payment counts for marking dues as paid.'
            ], 422);
        }

        $payment = DuesPayment::where('membership_due_id', $membershipDue->id)
            ->where('or_number', $validated['or_number'])
            ->first();

        return new DuesPaymentResource($payment->load(['membershipDue', 'member', 'receivedBy']));
    }

    /**
     * Display the specified dues payment
     */
    public function show(DuesPayment $duesPayment)
    {
        $user = request()->user();

        if (!$user->hasAnyRole(['super_admin', 'admin', 'treasurer'])) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        return new DuesPaymentResource($duesPayment->load(['membershipDue', 'member', 'receivedBy']));
    }

    /**
     * Get all payments for a specific membership due
     */
    public function getDuePayments(MembershipDue $membershipDue)
    {
        $user = request()->user();

        if (!$user->hasAnyRole(['super_admin', 'admin', 'treasurer'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $payments = $membershipDue->payments()->with(['member', 'receivedBy'])->get();

        return DuesPaymentResource::collection($payments);
    }

    /**
     * Get all payments collected in a specific year
     */
    public function getCollectionByYear(Request $request)
    {
        $user = $request->user();

        if (!$user->hasAnyRole(['super_admin', 'admin', 'treasurer'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $year = $request->query('year', now()->year);

        $total = DuesPayment::getTotalCollectedByYear($year);
        $payments = DuesPayment::whereYear('payment_date', $year)
            ->with(['membershipDue', 'member', 'receivedBy'])
            ->orderBy('payment_date', 'desc')
            ->paginate(20);

        return response()->json([
            'year' => $year,
            'total_collected' => $total,
            'payments' => DuesPaymentResource::collection($payments),
        ]);
    }

    /**
     * Get all payments received by a specific user (treasurer)
     */
    public function getTreasurerPayments(Request $request)
    {
        $user = $request->user();

        if (!$user->hasAnyRole(['super_admin', 'admin'])) {
            // Treasurers can only view their own payments
            if ($user->hasRole('treasurer')) {
                $treasurerId = $user->id;
            } else {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } else {
            $treasurerId = $request->query('user_id', $user->id);
        }

        $payments = DuesPayment::where('received_by_user_id', $treasurerId)
            ->with(['membershipDue', 'member', 'receivedBy'])
            ->orderBy('payment_date', 'desc')
            ->paginate(20);

        return DuesPaymentResource::collection($payments);
    }

    /**
     * Get payment statistics
     */
    public function getStats(Request $request)
    {
        $user = $request->user();

        if (!$user->hasAnyRole(['super_admin', 'admin', 'treasurer'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $year = $request->query('year', now()->year);

        return response()->json([
            'year' => $year,
            'total_collected' => DuesPayment::getTotalCollectedByYear($year),
            'total_payments' => DuesPayment::whereYear('payment_date', $year)->count(),
            'by_payment_method' => DuesPayment::whereYear('payment_date', $year)
                ->groupBy('payment_method')
                ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
                ->get(),
        ]);
    }
}
