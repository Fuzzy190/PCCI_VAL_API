<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DuesPayment extends Model
{
    use HasFactory;

    public const STATUS_PAID = 'paid';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_REJECTED = 'rejected';

    protected $table = 'dues_payments';

    protected $fillable = [
        'membership_due_id',
        'member_id',
        'submitted_by_user_id',
        'received_by_user_id',
        'or_number',
        'amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'receipt_image_url',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    // ==================== RELATIONSHIPS ====================
    public function membershipDue()
    {
        return $this->belongsTo(MembershipDue::class, 'membership_due_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    // ==================== PAYMENT LOGIC ====================
    /**
     * Record a dues payment and mark the corresponding due as paid
     * 
     * IMPORTANT: Only the year of payment is counted!
     * 
     * Example:
     * - Member owes dues for 2022, 2023, 2024
     * - Member pays on 2024-03-15 for amount 5000
     * - Only the 2024 due is marked as paid
     * - 2022 and 2023 remain unpaid until separately paid
     * 
     * @param MembershipDue $membershipDue
     * @param User $receivedByUser
     * @param array $paymentData ['amount', 'or_number', 'payment_date', 'payment_method', 'reference_number', 'notes']
     * @return bool
     */
    public static function recordPayment(MembershipDue $membershipDue, User $receivedByUser, array $paymentData): bool
    {
        $paymentDate = $paymentData['payment_date'] ?? now()->toDateString();
        $paymentYear = Carbon::createFromFormat('Y-m-d', $paymentDate)->year;

        // Check if payment year matches the due year
        if ($paymentYear != $membershipDue->due_year) {
            Log::warning(
                "Payment year {$paymentYear} does not match due year {$membershipDue->due_year} for Member ID: {$membershipDue->member_id}"
            );
            return false;
        }

        // Create the payment record
        $payment = self::create([
            'membership_due_id' => $membershipDue->id,
            'member_id' => $membershipDue->member_id,
            'received_by_user_id' => $receivedByUser->id,
            'or_number' => $paymentData['or_number'] ?? null,
            'amount' => $paymentData['amount'],
            'payment_date' => $paymentDate,
            'payment_method' => $paymentData['payment_method'] ?? null,
            'reference_number' => $paymentData['reference_number'] ?? null,
            'notes' => $paymentData['notes'] ?? null,
            'status' => self::STATUS_PAID,
        ]);

        // Mark the due as paid (only for the matching year).
        // Expiration reminders are now sent by CheckExpiringMemberships/ExpiringMembershipNotification.
        $membershipDue->markAsPaid($paymentData['amount'], $paymentDate);

        return true;
    }

    /**
     * Get all payments for a specific member
     */
    public function getMemberPayments()
    {
        return self::where('member_id', $this->member_id)
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * Get all payments for a specific member in a given year
     */
    public static function getPaymentsByMemberAndYear($memberId, $year)
    {
        return self::whereHas('membershipDue', function ($query) use ($year) {
            $query->where('due_year', $year);
        })
        ->where('member_id', $memberId)
        ->orderBy('payment_date', 'desc')
        ->get();
    }

    /**
     * Get total payments collected in a given year
     */
    public static function getTotalCollectedByYear($year)
    {
        return self::whereYear('payment_date', $year)->sum('amount');
    }

    /**
     * Get total payments collected from a specific member
     */
    public static function getTotalPaidByMember($memberId)
    {
        return self::where('member_id', $memberId)->sum('amount');
    }

    /**
     * Get payments received by a specific user (treasurer)
     */
    public static function getPaymentsReceivedByUser($userId)
    {
        return self::where('received_by_user_id', $userId)
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * Validate if a payment can be applied to a due
     * 
     * Rules:
     * - Payment date must match the due year
     * - Amount must be positive
     * - Due must not already be paid
     */
    public static function validatePayment(MembershipDue $membershipDue, array $paymentData): array
    {
        $errors = [];

        if (!isset($paymentData['amount']) || $paymentData['amount'] <= 0) {
            $errors[] = 'Payment amount must be greater than 0';
        }

        if ($membershipDue->status === MembershipDue::STATUS_PAID) {
            $errors[] = 'This due has already been paid';
        }

        $paymentDate = $paymentData['payment_date'] ?? now()->toDateString();
        $paymentYear = Carbon::createFromFormat('Y-m-d', $paymentDate)->year;

        if ($paymentYear != $membershipDue->due_year) {
            $errors[] = "Payment year {$paymentYear} does not match due year {$membershipDue->due_year}";
        }

        if (!isset($paymentData['or_number']) || empty($paymentData['or_number'])) {
            $errors[] = 'OR (Official Receipt) number is required';
        } else {
            // Check for duplicate OR number
            if (self::where('or_number', $paymentData['or_number'])->exists()) {
                $errors[] = 'This OR number already exists';
            }
        }

        return $errors;
    }
}
