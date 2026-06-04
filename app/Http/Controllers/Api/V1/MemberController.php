<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\MembershipDue;
use App\Models\MembershipType;
use App\Models\Payment;
use App\Models\DuesPayment;
use App\Http\Requests\StoreMemberRequest;
use App\Http\Resources\MemberResource;
use App\Http\Requests\UpdateMemberRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\MembershipDueResource;
use App\Http\Resources\DuesPaymentResource;
use App\Models\Transaction; // We added this earlier
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use App\Notifications\RenewalApprovedNotification;
use App\Notifications\RenewalRejectedNotification;
use App\Notifications\RenewalRequestSubmittedNotification;
use App\Notifications\SystemAlertNotification;

class MemberController extends Controller
{
    public function index()
    {
        return MemberResource::collection(
            Member::with(['applicant', 'membershipType', 'user'])->latest()->get()
        );
    }

    public function store(StoreMemberRequest $request)
    {
        $applicant = Applicant::findOrFail($request->applicant_id);

        if (!in_array($applicant->status, ['paid', 'approved'])) {
            return response()->json([
                'message' => 'Only applicants with status "paid" or "approved" can be added as members.'
            ], 422);
        }

        if (Member::where('applicant_id', $request->applicant_id)->exists()) {
            return response()->json([
                'message' => 'This applicant is already a member.'
            ], 422);
        }

        $payment = Payment::with('receivedBy')->where('applicant_id', $request->applicant_id)->first();
        $membershipTypeId = $payment ? $payment->membership_type_id : ($applicant->membership_type_id ?? 1);
        $membershipType = MembershipType::find($membershipTypeId);

        // STRICTLY ANCHOR TO INDUCTION DATE
        $inductionDate = $request->induction_date ? Carbon::parse($request->induction_date) : now();
        $duration = $membershipType->duration_in_months ?? 12;

        // Exact anniversary calculation
        $membershipEndDate = $inductionDate->copy()->addMonths($duration);

        $generatedPassword = null;
        $user = User::where('email', $applicant->email)->first();

        if ($user && Member::where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'A member account with this email already exists.'
            ], 422);
        }

        if (!$user) {
            $generatedPassword = Str::random(8);

            $user = User::create([
                'first_name' => $applicant->rep_first_name ?? $applicant->registered_business_name ?? 'Business',
                'last_name' => $applicant->rep_surname ?? 'Member',
                'email' => $applicant->email,
                'password' => Hash::make($generatedPassword),
                'requires_password_change' => true,
                'is_member' => true
            ]);

            $user->assignRole('member');

            $applicantName = ($applicant->rep_first_name ?? '') . ' ' . ($applicant->rep_surname ?? '');
            if (trim($applicantName) === '') $applicantName = $applicant->registered_business_name ?? 'Member';

            $emailData = [
                'applicantName' => $applicantName,
                'email' => $applicant->email,
                'password' => $generatedPassword,
                'membershipType' => $membershipType->name ?? 'Regular Member',
                'inductionDate' => $inductionDate ? $inductionDate->format('F j, Y') : 'To be announced',
                'expiryDate' => $membershipEndDate ? $membershipEndDate->format('F j, Y') : 'Pending Induction',
                'orNumber' => $payment->or_number ?? 'Verified by Treasurer',
                'amount' => number_format($payment->amount ?? $membershipType->price ?? 0, 2),
                'paymentDate' => $payment->payment_date ? Carbon::parse($payment->payment_date)->format('F j, Y') : now()->format('F j, Y'),
                'receivedBy' => $payment->receivedBy->first_name ?? 'PCCI Treasurer'
            ];

            try {
                Mail::send('emails.member_welcome', $emailData, function ($message) use ($applicant, $applicantName) {
                    $message->to($applicant->email, $applicantName)
                        ->subject('Welcome to PCCI - Membership & Account Details');
                });
            } catch (\Exception $e) {
                // Failsafe so the DB save still happens even if Gmail is disconnected locally
            }
        } else {
            $user->update(['is_member' => true]);
        }

        $status = 'active';
        if ($membershipEndDate && $membershipEndDate->isPast()) {
            $status = 'inactive';
        }

        $member = Member::create([
            'applicant_id' => $request->applicant_id,
            'user_id' => $user->id,
            'membership_type_id' => $membershipTypeId,
            'induction_date' => $inductionDate,
            'membership_end_date' => $membershipEndDate,
            'status' => $status,
            'created_by_user_id' => auth()->id(),
        ]);

        if ($membershipEndDate) {
            // EXACT ANNIVERSARY DUE CREATION
            MembershipDue::create([
                'member_id' => $member->id,
                'amount' => $membershipType->price ?? $payment->amount ?? 0,
                'due_year' => $membershipEndDate->year,
                'due_date' => $membershipEndDate,
                'status' => 'pending',
                'notes' => 'Automatically generated from membership activation',
            ]);
        }

        if ($status === 'inactive') {
            $admins = User::role(['admin', 'super_admin'])->get();
            $businessName = $applicant->registered_business_name ?? $user->first_name;

            Notification::send($admins, new SystemAlertNotification(
                'Membership Inactive',
                "The membership for {$businessName} is already inactive based on the set induction date.",
                'fa-exclamation-triangle',
                'text-warning'
            ));

            $user->notify(new SystemAlertNotification(
                'Membership Inactive',
                'Your PCCI Valenzuela membership has become inactive. Please process your renewal.',
                'fa-clock',
                'text-warning'
            ));
        }

        return response()->json([
            'message' => 'Member created and saved to database successfully!',
            'member' => new MemberResource($member),
            'generated_password' => $generatedPassword,
        ], 201);
    }

    public function show(Member $member)
    {
        return new MemberResource($member->load(['applicant', 'membershipType', 'createdBy']));
    }

    public function update(UpdateMemberRequest $request, Member $member)
    {
        // =========================================================================
        // NEW FEATURE: Update Basic Profile (Company Name, Email, Status Override)
        // =========================================================================
        if ($request->filled('company_name') || $request->filled('email') || $request->filled('status')) {
            if ($member->applicant) {
                $applicantUpdates = [];

                if ($request->filled('company_name')) {
                    $applicantUpdates['registered_business_name'] = $request->company_name;
                }
                if ($request->filled('email')) {
                    $applicantUpdates['email'] = $request->email;
                }

                if (!empty($applicantUpdates)) {
                    $member->applicant->update($applicantUpdates);
                }
            }

            // Update manual status if they changed it without changing the date
            if ($request->filled('status')) {
                $member->update(['status' => $request->status]);
            }
        }


        // =========================================================================
        // YOUR ORIGINAL LOGIC: Induction Date, End Date, and Pending Due Syncing
        // =========================================================================
        if ($request->filled('induction_date')) {
            $inductionDate = Carbon::parse($request->induction_date);
            $membershipType = MembershipType::findOrFail($member->membership_type_id);

            // Calculate the anniversary date based on duration
            $duration = $membershipType->duration_in_months ?? 12;
            $membershipEndDate = $inductionDate->copy()->addMonths($duration);

            // Determine status based on the NEW end date 
            // (Unless the Super Admin explicitly overrode the status in the modal)
            $newStatus = $request->filled('status') ? $request->status : ($membershipEndDate->isPast() ? 'inactive' : 'active');

            // 1. Update the Member record
            $member->update([
                'induction_date' => $inductionDate,
                'membership_end_date' => $membershipEndDate,
                'status' => $newStatus, // Force the status here
            ]);

            // 2. Sync the Pending Due
            $pendingDue = $member->membershipDues()->where('status', 'pending')->first();
            if ($pendingDue) {
                $pendingDue->update([
                    'due_year' => $membershipEndDate->year,
                    'due_date' => $membershipEndDate
                ]);
            }

            // 3. FINAL OVERRIDE
            // We call save again specifically for the status to ensure 
            // no observers flipped it back to 'pending' during the process.
            $member->status = $newStatus;
            $member->save();

            // 4. Handle Inactive Notifications
            if ($newStatus === 'inactive') {
                $admins = User::role(['admin', 'super_admin'])->get();
                $businessName = data_get($member, 'applicant.registered_business_name', 'A member');

                Notification::send($admins, new SystemAlertNotification(
                    'Membership Inactive',
                    "{$businessName}'s membership is now inactive based on the updated induction date.",
                    'fa-exclamation-triangle',
                    'text-warning'
                ));

                if ($member->user) {
                    $member->user->notify(new SystemAlertNotification(
                        'Membership Inactive',
                        'Your PCCI membership has become inactive. Please process your renewal.',
                        'fa-clock',
                        'text-warning'
                    ));
                }
            }
        }

        return response()->json([
            'message' => 'Member profile updated successfully.',
            'data' => new MemberResource($member->fresh())
        ]);
    }

    public function destroy(Member $member)
    {
        $member->delete();
        return response()->json(['message' => 'Member deleted successfully.']);
    }

    public function getMyDues()
    {
        $user = Auth::user();
        $member = $user->member;
        if (!$member) return response()->json(['message' => 'Member not found'], 404);

        $dues = $member->membershipDues()->with('payments')->get();
        return MembershipDueResource::collection($dues);
    }

    /**
     * Fetch the member's personal transaction history
     * GET /api/v1/member/payments
     */
    public function getMyPayments(Request $request)
    {
        $member = $request->user()->member;

        if (!$member) return response()->json([]);

        // Fetch ONLY this member's transactions, newest first
        $transactions = Transaction::where('member_id', $member->id)
            ->orWhere('applicant_id', $member->applicant_id) // Catch their initial registration too!
            ->latest()
            ->get();

        return response()->json($transactions);
    }

    public function getMyProfile()
    {
        $user = Auth::user();
        $member = $user->member;

        if (!$member) {
            return response()->json(['message' => 'You are not registered as a member'], 404);
        }

        // Load relationships we may need
        $member->load(['membershipType', 'membershipDues', 'applicant']);

        // Use resource to build canonical payload
        $memberData = (new MemberResource($member))->resolve();

        // 1) If membership name is missing, try to recover from applicant
        $membershipName = $memberData['membership_type']['name'] ?? null;
        $membershipDebug = [
            'resource_has' => !empty($membershipName),
            'applicant_has' => false,
            'transaction_has' => false,
            'relation_has' => false,
            'final_name' => $membershipName,
        ];
        if (empty($membershipName)) {
            $applicant = $member->applicant;
            if ($applicant) {
                // Applicant may store a membership_type_id or a free-text membership_type
                if (!empty($applicant->membership_type_id)) {
                    $mt = MembershipType::find($applicant->membership_type_id);
                    if ($mt) {
                        $memberData['membership_type'] = [
                            'id' => $mt->id,
                            'name' => $mt->name,
                        ];
                        $membershipName = $mt->name;
                        $membershipDebug['applicant_has'] = true;
                        $membershipDebug['final_name'] = $membershipName;
                    }
                } elseif (!empty($applicant->membership_type)) {
                    $memberData['membership_type'] = [
                        'id' => null,
                        'name' => $applicant->membership_type,
                    ];
                    $membershipName = $applicant->membership_type;
                    $membershipDebug['applicant_has'] = true;
                    $membershipDebug['final_name'] = $membershipName;
                }
            }
        }

        // 2) Still missing? Try latest approved/paid transaction (member or applicant)
        if (empty($membershipName)) {
            $txn = Transaction::where(function ($q) use ($member) {
                $q->where('member_id', $member->id)
                    ->orWhere('applicant_id', $member->applicant_id);
            })
                ->whereIn('status', ['approved', 'paid', 'completed'])
                ->latest()
                ->first();

            if ($txn) {
                if (!empty($txn->membership_type_id)) {
                    $mt = MembershipType::find($txn->membership_type_id);
                    if ($mt) {
                        $memberData['membership_type'] = [
                            'id' => $mt->id,
                            'name' => $mt->name,
                        ];
                        $membershipName = $mt->name;
                        $membershipDebug['transaction_has'] = true;
                        $membershipDebug['final_name'] = $membershipName;
                    }
                } elseif (!empty($txn->membership_type)) {
                    $memberData['membership_type'] = [
                        'id' => null,
                        'name' => $txn->membership_type,
                    ];
                    $membershipName = $txn->membership_type;
                    $membershipDebug['transaction_has'] = true;
                    $membershipDebug['final_name'] = $membershipName;
                } elseif (!empty($txn->membershipType->name)) {
                    $memberData['membership_type'] = [
                        'id' => $txn->membershipType->id,
                        'name' => $txn->membershipType->name,
                    ];
                    $membershipName = $txn->membershipType->name;
                    $membershipDebug['transaction_has'] = true;
                    $membershipDebug['final_name'] = $membershipName;
                }
            }
        }

        // 3) Final fallback: if still empty, keep a safe 'N/A'
        if (empty($memberData['membership_type']['name'])) {
            // If model relation has a name, use it
            if ($member->membershipType && !empty($member->membershipType->name)) {
                $memberData['membership_type'] = [
                    'id' => $member->membershipType->id ?? null,
                    'name' => $member->membershipType->name ?? 'N/A',
                ];
                $membershipDebug['relation_has'] = true;
                $membershipDebug['final_name'] = $memberData['membership_type']['name'];
            } else {
                $memberData['membership_type'] = [
                    'id' => $memberData['membership_type']['id'] ?? null,
                    'name' => 'N/A',
                ];
            }
        }

        // FORCE-INJECT user info so frontend gets photo_url etc.
        $memberData['user'] = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'contact_number' => $user->contact_number,
            'photo_url' => $user->photo_url,
            'profile_photo_path' => $user->profile_photo_path,
        ];

        // Compatibility shim: ensure both snake_case and camelCase keys are present
        if (!empty($memberData['membership_type']) && empty($memberData['membershipType'])) {
            $memberData['membershipType'] = $memberData['membership_type'];
        }

        // If membership_type is missing but model relation exists, populate both keys
        if (empty($memberData['membership_type']) && $member->membershipType) {
            $memberData['membership_type'] = [
                'id' => $member->membershipType->id ?? null,
                'name' => $member->membershipType->name ?? 'N/A',
            ];
            if (empty($memberData['membershipType'])) {
                $memberData['membershipType'] = $memberData['membership_type'];
            }
        }

        // Also inject into nested applicant so frontend fallback paths work too
        if (!empty($memberData['membership_type']['name']) && isset($memberData['applicant'])) {
            $memberData['applicant']['membershipType'] = $memberData['membership_type'];
            $memberData['applicant']['membership_type'] = $memberData['membership_type'];
        }

        return response()->json([
            'member' => $memberData,
            'membershipType' => $memberData['membershipType'] ?? $memberData['membership_type'] ?? null,
            'membership_type' => $memberData['membership_type'] ?? $memberData['membershipType'] ?? null,
            'membership_status' => $member->status,
            'membership_end_date' => $member->membership_end_date,
            'has_active_dues' => $member->hasPaidCurrentYearDues(),
            'has_overdue_dues' => $member->hasOverdueDues(),
            'membership_debug' => $membershipDebug,
        ]);
    }

    public function getRenewalStatus()
    {
        $user = Auth::user();
        $member = $user->member;
        if (!$member) return response()->json(['message' => 'Member not found'], 404);

        // ALWAYS FETCH THE NEXT PENDING DUE (Regardless of calendar year)
        $targetDue = $member->membershipDues()
            ->where('status', 'pending')
            ->orderBy('due_date', 'asc')
            ->first();

        // Fallback to the latest paid due if nothing is pending
        if (!$targetDue) {
            $targetDue = $member->membershipDues()->orderBy('due_date', 'desc')->first();
        }

        if (!$targetDue) {
            return response()->json([
                'message' => 'No dues record found.',
                'renewal_needed' => true,
                'membership_status' => $member->status,
            ]);
        }

        return response()->json([
            'current_year' => $targetDue->due_year,
            'due_id' => $targetDue->id,
            'amount_due' => $targetDue->amount,
            'due_date' => $targetDue->due_date,
            'paid_date' => $targetDue->paid_date,
            'status' => $targetDue->status,
            'member_status' => $member->status,
            'paid' => $targetDue->status === 'paid',
            'overdue' => $targetDue->status === 'pending' && Carbon::parse($targetDue->due_date)->isPast(),
        ]);
    }

    /**
     * Submit a proof of payment for Renewal
     * POST /api/v1/member/request-payment
     */
    public function requestPayment(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|string',
            'proof_of_payment' => 'required|image|max:5000', // Max 5MB
        ]);

        $user = $request->user();
        $member = $user->member;

        if (!$member) {
            return response()->json(['message' => 'Membership record not found.'], 404);
        }

        // 1. Upload the image
        $path = $request->file('proof_of_payment')->store('proofs/renewals', 'public');

        // 2. Determine the correct renewal price
        $type = $member->membershipType;
        $amount = $type->renewal_price ? $type->renewal_price : $type->price;

        $pendingDue = $member->membershipDues()
            ->where('status', MembershipDue::STATUS_PENDING)
            ->latest('due_year')
            ->first();

        if (!$pendingDue) {
            return response()->json([
                'message' => 'No pending renewal due found. Please contact the Treasurer.'
            ], 422);
        }

        $result = DB::transaction(function () use ($member, $pendingDue, $request, $amount, $path) {
            $temporaryOr = 'PENDING-' . strtoupper(substr(uniqid('', true), -12));

            $payment = DuesPayment::create([
                'membership_due_id' => $pendingDue->id,
                'member_id' => $member->id,
                'submitted_by_user_id' => auth()->id(),
                'or_number' => $temporaryOr,
                'amount' => $amount,
                'payment_date' => now()->toDateString(),
                'payment_method' => $request->payment_method,
                'receipt_image_url' => $path,
                'status' => DuesPayment::STATUS_PENDING_REVIEW,
                'notes' => 'Renewal proof submitted by member for Treasurer review.',
            ]);

            $transaction = Transaction::create([
                'transaction_type' => 'renewal',
                'member_id' => $member->id,
                'membership_due_id' => $pendingDue->id,
                'amount' => $amount,
                'payment_method' => $request->payment_method,
                'status' => 'pending',
                'proof_of_payment_path' => $path,
                'notes' => 'Member submitted renewal via dashboard.',
            ]);

            return ['payment' => $payment, 'transaction' => $transaction];
        });

        Notification::send(User::role('treasurer')->get(), new RenewalRequestSubmittedNotification($result['payment']));

        return response()->json([
            'message' => 'Renewal request submitted successfully. Treasurer will review it soon.',
            'payment' => $result['payment'],
            'transaction' => $result['transaction'],
        ]);
    }

    public function triggerExpiryCheck(Request $request)
    {
        Artisan::call('memberships:check-expiring');
        $output = Artisan::output();

        return response()->json([
            'message' => 'Expiry check executed successfully.',
            'terminal_output' => trim($output)
        ], 200);
    }

    /**
     * Approve a renewal payment and reactivate the inactive member.
     */
    public function approveRenewalPayment(Request $request, $identifier)
    {
        // 1. Lookup the Payment or Transaction
        $payment = \App\Models\DuesPayment::find($identifier);
        $transaction = \App\Models\Transaction::find($identifier);

        $dueId = null;
        $memberId = null;
        $amount = 0;

        if ($payment) {
            $dueId = $payment->membership_due_id;
            $memberId = $payment->member_id;
            $amount = $payment->amount;

            $linkedTxn = \App\Models\Transaction::where('or_number', $payment->or_number)->first();
            if ($linkedTxn) $transaction = $linkedTxn;
        } elseif ($transaction) {
            $memberId = $transaction->member_id;
            $dueId = $transaction->membership_due_id;
            $amount = $transaction->amount;

            $payment = \App\Models\DuesPayment::where('or_number', $transaction->or_number)->first();
            if ($payment && !$dueId) {
                $dueId = $payment->membership_due_id;
            }
        }

        if (!$dueId && $memberId) {
            $pendingDue = \App\Models\MembershipDue::where('member_id', $memberId)->where('status', 'pending')->first();
            if ($pendingDue) $dueId = $pendingDue->id;
        }

        if (!$dueId || !$memberId) {
            return response()->json(['message' => 'Cannot process: Invalid renewal transaction. Missing due reference.'], 400);
        }

        $due = \App\Models\MembershipDue::findOrFail($dueId);
        $member = \App\Models\Member::findOrFail($memberId);

        // ==========================================
        // AUTO-GENERATE OR NUMBER IF MISSING
        // ==========================================
        $existingOr = ($transaction && $transaction->or_number) ? $transaction->or_number : ($payment ? $payment->or_number : null);
        $finalOrNumber = $existingOr ?: 'OR-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));

        // 3. Mark Everything as Approved and attach the OR Number
        DB::transaction(function () use ($transaction, $payment, $due, $member, $finalOrNumber, $amount) {
            if ($transaction) {
                $transaction->update(['status' => 'approved', 'or_number' => $finalOrNumber]);
            }

            if ($payment) {
                $payment->update(['status' => 'approved', 'or_number' => $finalOrNumber]);
            }

            DuesPayment::where('membership_due_id', $due->id)
                ->whereIn('status', [DuesPayment::STATUS_PENDING_REVIEW, 'pending'])
                ->update(['status' => 'approved', 'or_number' => $finalOrNumber]);

            $due->update(['status' => 'paid', 'paid_date' => now()]);

            $newEndDate = $member->membership_end_date
                ? $member->membership_end_date->copy()->addYear()
                : now()->copy()->addYear();

            $member->update(['status' => 'active', 'membership_end_date' => $newEndDate]);

            MembershipDue::updateOrCreate(
                ['member_id' => $member->id, 'due_year' => $newEndDate->year],
                ['amount' => $amount, 'due_date' => $newEndDate, 'status' => MembershipDue::STATUS_PENDING, 'notes' => 'Automatically generated']
            );

            if ($member->user) {
                $member->user->notify(new RenewalApprovedNotification(
                    $member,
                    $transaction,
                    $payment,
                    $newEndDate
                ));
            }
        });

        return response()->json(['message' => 'Renewal approved! OR Generated.', 'or_number' => $finalOrNumber], 200);
    }

    /**
     * Reject a renewal payment and notify the member.
     */
    public function rejectRenewalPayment(Request $request, $identifier)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:255'
        ]);

        $payment = \App\Models\DuesPayment::find($identifier);
        $transaction = \App\Models\Transaction::find($identifier);

        $memberId = null;

        if ($payment) {
            $memberId = $payment->member_id;
            $linkedTxn = \App\Models\Transaction::where('or_number', $payment->or_number)->first();
            if ($linkedTxn) $transaction = $linkedTxn;
        } elseif ($transaction) {
            $memberId = $transaction->member_id;
            $payment = \App\Models\DuesPayment::where('or_number', $transaction->or_number)->first();
        }

        if (!$memberId) {
            return response()->json(['message' => 'Renewal payment record not found or invalid.'], 404);
        }

        $member = \App\Models\Member::find($memberId);

        // Mark as rejected
        DB::transaction(function () use ($transaction, $payment, $member, $validated) {
            if ($transaction) {
                $transaction->update(['status' => 'rejected', 'notes' => 'Rejected: ' . $validated['rejection_reason']]);
            }

            if ($payment) {
                $payment->update(['status' => DuesPayment::STATUS_REJECTED, 'notes' => 'Rejected: ' . $validated['rejection_reason']]);
            }

            if ($member && $member->user) {
                $member->user->notify(new RenewalRejectedNotification(
                    $member,
                    $payment,
                    $transaction,
                    $validated['rejection_reason']
                ));
            }
        });

        return response()->json(['message' => 'Payment rejected successfully.'], 200);
    }
}
