<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Member;
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
use Carbon\Carbon;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;
use App\Notifications\SystemAlertNotification;
use Illuminate\Support\Facades\Notification;

class MemberController extends Controller
{
    public function index()
    {
        return MemberResource::collection(
            Member::with('applicant')->latest()->get()
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
            if(trim($applicantName) === '') $applicantName = $applicant->registered_business_name ?? 'Member';
            
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
                Mail::send('emails.member_welcome', $emailData, function($message) use ($applicant, $applicantName) {
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
        return new MemberResource($member);
    }

    public function update(UpdateMemberRequest $request, Member $member)
    {
        if ($request->filled('induction_date')) {
            $inductionDate = Carbon::parse($request->induction_date);
            $membershipType = MembershipType::findOrFail($member->membership_type_id);
            
            // Calculate the anniversary date based on duration
            $duration = $membershipType->duration_in_months ?? 12;
            $membershipEndDate = $inductionDate->copy()->addMonths($duration);
            
            // Determine status based on the NEW end date
            $newStatus = $membershipEndDate->isPast() ? 'inactive' : 'active';

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

        return new MemberResource($member->fresh());
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

    public function getMyPayments()
    {
        $user = Auth::user();
        $member = $user->member;
        if (!$member) return response()->json(['message' => 'Member not found'], 404);

        $payments = DuesPayment::where('member_id', $member->id)->with('membershipDue')->get();
        return DuesPaymentResource::collection($payments);
    }

    public function getMyProfile()
    {
        $user = Auth::user();
        $member = $user->member;
        if (!$member) return response()->json(['message' => 'You are not registered as a member'], 404);

        return response()->json([
            'member' => new MemberResource($member->load(['membershipType', 'membershipDues', 'user'])),
            'membership_status' => $member->status,
            'membership_end_date' => $member->membership_end_date,
            'has_active_dues' => $member->hasPaidCurrentYearDues(),
            'has_overdue_dues' => $member->hasOverdueDues(),
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

    public function requestPayment(Request $request)
    {
        $user = Auth::user();
        $member = $user->member;
        if (!$member) return response()->json(['message' => 'Member not found'], 404);

        $validated = $request->validate([
            'membership_due_id' => 'nullable|exists:membership_dues,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:cash,check,transfer,online,gcash',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'receipt_image' => 'nullable|image|max:10240',
        ]);

        if (!empty($validated['membership_due_id'])) {
            $due = MembershipDue::find($validated['membership_due_id']);
        } else {
            // Find the OLDEST pending due to ensure chronological payment
            $due = $member->membershipDues()
                ->where('status', 'pending')
                ->orderBy('due_date', 'asc')
                ->first();
        }

        if (!$due) return response()->json(['message' => 'No unpaid membership due found for this member.'], 404);
        if ($due->member_id !== $member->id) return response()->json(['message' => 'Unauthorized: Due does not belong to you'], 403);
        if ($due->status === 'paid') return response()->json(['message' => 'This membership due is already paid'], 422);

        $receiptUrl = null;
        if ($request->hasFile('receipt_image')) {
            $path = $request->file('receipt_image')->store('payment_receipts', 's3');
            $disk = Storage::disk('s3');
            $receiptUrl = $disk->temporaryUrl($path, now()->addDays(7));
        }

        $referenceNumber = $validated['reference_number'] ?? null;
        if ($referenceNumber && DuesPayment::where('reference_number', $referenceNumber)->exists()) {
            return response()->json(['message' => 'Reference number already exists. Please use a unique identifier.'], 422);
        }

        $orNumber = 'REQ-' . strtoupper(Str::random(8));

        $paymentRequest = DuesPayment::create([
            'membership_due_id' => $due->id,    
            'member_id' => $member->id,
            'submitted_by_user_id' => $user->id,
            'or_number' => $orNumber,
            'amount' => $validated['amount'],
            'payment_date' => now()->toDateString(),
            'payment_method' => $validated['payment_method'],
            'reference_number' => $referenceNumber,
            'notes' => $validated['notes'] ?? null,
            'receipt_image_url' => $receiptUrl,
            'status' => 'pending_review',
        ]);

        return response()->json([
            'message' => 'Payment request submitted',
            'payment_request' => new DuesPaymentResource($paymentRequest->load(['membershipDue', 'member', 'submittedBy'])),
        ], 201);
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
    public function approveRenewalPayment(Request $request, $paymentId)
    {
        $payment = DuesPayment::findOrFail($paymentId);
        $due = MembershipDue::findOrFail($payment->membership_due_id);
        $member = Member::findOrFail($payment->member_id);

        // 1. Mark Payment and Due as Paid
        $payment->update(['status' => 'approved']);
        $due->update([
            'status' => 'paid', 
            'paid_date' => now()
        ]);

        $nextYear = $due->due_year + 1;

        // 2. Reactivate Member & Extend Expiry Date strictly anchored to Induction Date
        $induction = $member->induction_date ? Carbon::parse($member->induction_date) : now();
        $newEndDate = $induction->copy()->year($nextYear);

        $member->update([
            'status' => 'active',
            'membership_end_date' => $newEndDate
        ]);

        // 3. Generate the next year's pending due perfectly on schedule
        MembershipDue::updateOrCreate(
            [
                'member_id' => $member->id,
                'due_year' => $nextYear,
            ],
            [
                'amount' => $payment->amount, 
                'due_date' => $newEndDate,
                'status' => 'pending',
                'notes' => 'Automatically generated for next billing cycle',
            ]
        );

        // 4. Notify the Member
        if ($member->user) {
            $member->user->notify(new SystemAlertNotification(
                'Membership Renewed!',
                'Your renewal payment was approved. Your membership is now active until ' . $newEndDate->format('F j, Y'),
                'fa-check-circle',
                'text-success'
            ));
        }

        return response()->json([
            'message' => 'Renewal approved! Member is now active for another year.',
            'new_end_date' => $newEndDate->format('Y-m-d')
        ], 200);
    }
}