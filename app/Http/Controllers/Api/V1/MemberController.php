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
use App\Services\MailtrapApiService;

class MemberController extends Controller
{
    public function index()
    {
        return MemberResource::collection(
            Member::with('applicant')->latest()->get()
        );
    }

    public function store(StoreMemberRequest $request, MailtrapApiService $mailtrap)
    {
        // 1. Find applicant
        $applicant = Applicant::findOrFail($request->applicant_id);

        if ($applicant->status !== 'paid') {
            return response()->json([
                'message' => 'Only applicants with status "paid" can be added as members.'
            ], 422);
        }

        // 2. Prevent duplicate member
        if (Member::where('applicant_id', $request->applicant_id)->exists()) {
            return response()->json([
                'message' => 'This applicant is already a member.'
            ], 422);
        }

        // 3. Get the original payment for this applicant (including who received it)
        $payment = Payment::with('receivedBy')->where('applicant_id', $request->applicant_id)->first();

        if (!$payment) {
            return response()->json([
                'message' => 'Payment record not found for this applicant.'
            ], 422);
        }

        // 4. Get membership type from payment
        $membershipType = MembershipType::findOrFail($payment->membership_type_id);

        // Handle induction date (nullable)
        $inductionDate = $request->induction_date ? Carbon::parse($request->induction_date) : null;

        // Auto-calculate membership_end_date if induction date is set
        $membershipEndDate = $inductionDate ? $inductionDate->copy()->addMonths($membershipType->duration_in_months) : null;

        // Create user account for member (if not exists)
        $generatedPassword = null;
        $user = User::where('email', $applicant->email)->first();

        if (!$user) {
            $generatedPassword = Str::random(8);
            $user = User::create([
                'name' => $applicant->registered_business_name,
                'email' => $applicant->email,
                'password' => Hash::make($generatedPassword),
            ]);
            $user->assignRole('member');

            // ==================== SEND FULL CREDENTIALS & RECEIPT VIA MAILTRAP ====================
            $applicantName = $applicant->rep_first_name . ' ' . $applicant->rep_surname;
            
            $html = view('emails.member_welcome', [
                'applicantName' => $applicantName,
                'email' => $applicant->email,
                'password' => $generatedPassword,
                'membershipType' => $membershipType->name,
                'inductionDate' => $inductionDate ? $inductionDate->format('F j, Y') : 'To be announced',
                'expiryDate' => $membershipEndDate ? $membershipEndDate->format('F j, Y') : 'Pending Induction',
                'orNumber' => $payment->or_number ?? 'N/A',
                'amount' => number_format($payment->amount, 2),
                'paymentDate' => $payment->payment_date ? Carbon::parse($payment->payment_date)->format('F j, Y') : 'N/A',
                'receivedBy' => $payment->receivedBy->name ?? 'PCCI Treasurer'
            ])->render();

            $mailtrap->sendMail(
                $applicant->email,
                $applicantName,
                'Welcome to PCCI - Membership & Receipt Details',
                'Your PCCI member account has been created. Use the credentials provided to log in.',
                $html
            );
        }

        // Now create the member with user_id
        $member = Member::create([
            'applicant_id' => $request->applicant_id,
            'user_id' => $user->id,
            'membership_type_id' => $payment->membership_type_id,
            'induction_date' => $inductionDate,
            'membership_end_date' => $membershipEndDate,
            'status' => 'active', 
        ]);

        if ($membershipEndDate) {
            MembershipDue::create([
                'member_id' => $member->id,
                'amount' => $membershipType->price ?? $payment->amount,
                'due_year' => $membershipEndDate->year,
                'due_date' => $membershipEndDate,
                'status' => 'pending',
                'notes' => 'Automatically generated from membership activation',
            ]);
        }

        return response()->json([
            'message' => 'Member created successfully',
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
            $membershipEndDate = $inductionDate->copy()->addMonths($membershipType->duration_in_months);
 
            $member->update([
                'induction_date' => $inductionDate,
                'membership_end_date' => $membershipEndDate,
                'status' => 'active',
            ]);
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

        $currentYear = now()->year;
        $currentYearDue = $member->membershipDues()->where('due_year', $currentYear)->first();

        if (!$currentYearDue) {
            return response()->json([
                'message' => 'No dues record for current year',
                'renewal_needed' => true,
                'current_year' => $currentYear,
                'membership_status' => $member->status,
            ]);
        }

        return response()->json([
            'current_year' => $currentYear,
            'due_id' => $currentYearDue->id,
            'amount_due' => $currentYearDue->amount,
            'due_date' => $currentYearDue->due_date,
            'paid_date' => $currentYearDue->paid_date,
            'status' => $currentYearDue->status,
            'member_status' => $member->status,
            'paid' => $currentYearDue->status === MembershipDue::STATUS_PAID,
            'overdue' => $currentYearDue->status === MembershipDue::STATUS_UNPAID && $currentYearDue->due_date->isPast(),
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
            $due = $member->membershipDues()
                ->where('status', MembershipDue::STATUS_UNPAID)
                ->orderBy('due_year', 'desc')
                ->first();
        }

        if (!$due) return response()->json(['message' => 'No unpaid membership due found for this member.'], 404);
        if ($due->member_id !== $member->id) return response()->json(['message' => 'Unauthorized: Due does not belong to you'], 403);
        if ($due->status === MembershipDue::STATUS_PAID) return response()->json(['message' => 'This membership due is already paid'], 422);

        $receiptUrl = null;
        if ($request->hasFile('receipt_image')) {
            $path = $request->file('receipt_image')->store('payment_receipts', 's3');
            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
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
            'status' => DuesPayment::STATUS_PENDING_REVIEW,
        ]);

        return response()->json([
            'message' => 'Payment request submitted',
            'payment_request' => new DuesPaymentResource($paymentRequest->load(['membershipDue', 'member', 'submittedBy'])),
        ], 201);
    }
}