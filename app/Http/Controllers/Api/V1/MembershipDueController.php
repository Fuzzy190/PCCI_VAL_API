<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMembershipDueRequest;
use App\Http\Resources\MembershipDueResource;
use App\Models\MembershipDue;
use App\Models\Member;
use Illuminate\Http\Request;

class MembershipDueController extends Controller
{
    /**
     * Display a listing of membership dues
     * 
     * Query Parameters:
     * - member_id: Filter by member
     * - due_year: Filter by year
     * - status: Filter by status (pending, paid, overdue, waived, expired)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        MembershipDue::generateMissingDuesForExistingMembers();

        // Role-based access control
        if ($user->hasAnyRole(['super_admin', 'admin', 'treasurer'])) {
            $query = MembershipDue::query();
        } else {
            return response()->json([
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Optional filters
        if ($request->filled('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        if ($request->filled('due_year')) {
            $query->where('due_year', $request->due_year);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $dues = $query->with(['member', 'payments'])->paginate(20);

        return MembershipDueResource::collection($dues);
    }

    /**
     * Store a newly created membership due
     */
    public function store(StoreMembershipDueRequest $request)
    {
        $data = $request->validated();
        $data['status'] = $data['status'] ?? 'pending';

        // Check if due already exists for this member and year
        $existingDue = MembershipDue::where('member_id', $data['member_id'])
            ->where('due_year', $data['due_year'])
            ->first();

        if ($existingDue) {
            return response()->json([
                'message' => "Due already exists for this member in {$data['due_year']}"
            ], 409);
        }

        $due = MembershipDue::create($data);

        return new MembershipDueResource($due->load(['member', 'payments']));
    }

    /**
     * Display the specified membership due
     */
    public function show(MembershipDue $membershipDue)
    {
        $user = request()->user();

        if (!$user->hasAnyRole(['super_admin', 'admin', 'treasurer'])) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        return new MembershipDueResource($membershipDue->load(['member', 'payments']));
    }

    /**
     * Update the specified membership due
     */
    public function update(Request $request, MembershipDue $membershipDue)
    {
        $user = $request->user();

        if (!$user->hasAnyRole(['super_admin', 'admin'])) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'status' => ['nullable', 'in:pending,paid,overdue,waived,expired'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $membershipDue->update($validated);

        return new MembershipDueResource($membershipDue->load(['member', 'payments']));
    }

    /**
     * Get all pending dues
     */
    public function getPending(Request $request)
    {
        $user = $request->user();

        MembershipDue::generateMissingDuesForExistingMembers();

        if (!$user->hasAnyRole(['super_admin', 'admin', 'treasurer'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $dues = MembershipDue::where('status', MembershipDue::STATUS_UNPAID)
            ->with(['member', 'payments'])
            ->paginate(20);

        return MembershipDueResource::collection($dues);
    }

    /**
     * Get all overdue dues
     */
    public function getOverdue(Request $request)
    {
        $user = $request->user();

        MembershipDue::generateMissingDuesForExistingMembers();

        if (!$user->hasAnyRole(['super_admin', 'admin', 'treasurer'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $dues = MembershipDue::where('status', MembershipDue::STATUS_UNPAID)
            ->where('due_date', '<', now())
            ->with(['member', 'payments'])
            ->paginate(20);

        return MembershipDueResource::collection($dues);
    }

    /**
     * Get unpaid dues for a specific member across multiple years
     */
    public function getMemberUnpaidDues(Request $request, Member $member)
    {
        $user = $request->user();

        MembershipDue::generateMissingDuesForExistingMembers();

        if (!$user->hasAnyRole(['super_admin', 'admin', 'treasurer'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $dues = $member->membershipDues()
            ->where('status', '!=', 'paid')
            ->orderBy('due_year')
            ->get();

        return MembershipDueResource::collection($dues);
    }

    /**
     * Get summary statistics for dues
     */
    public function getStats(Request $request)
    {
        $user = $request->user();

        MembershipDue::generateMissingDuesForExistingMembers();

        if (!$user->hasAnyRole(['super_admin', 'admin', 'treasurer'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'total_pending' => MembershipDue::where('status', MembershipDue::STATUS_UNPAID)->count(),
            'total_paid' => MembershipDue::where('status', MembershipDue::STATUS_PAID)->count(),
            'total_overdue' => MembershipDue::where('status', MembershipDue::STATUS_UNPAID)
                ->where('due_date', '<', now())
                ->count(),
            'total_waived' => 0,
            'total_amount_pending' => MembershipDue::where('status', MembershipDue::STATUS_UNPAID)->sum('amount'),
            'total_amount_paid' => MembershipDue::where('status', MembershipDue::STATUS_PAID)->sum('amount'),
            'total_amount_overdue' => MembershipDue::where('status', MembershipDue::STATUS_UNPAID)
                ->where('due_date', '<', now())
                ->sum('amount'),
        ]);
    }
}
