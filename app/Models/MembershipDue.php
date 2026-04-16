<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\Member;
use App\Notifications\MembershipDuesFirstWarningEmail;
use App\Notifications\MembershipDuesSecondWarningEmail;
use App\Notifications\MembershipDuesFinalWarningEmail;

class MembershipDue extends Model
{
    use HasFactory;

    protected $table = 'membership_dues';

    protected $fillable = [
        'member_id',
        'amount',
        'due_year',
        'due_date',
        'paid_date',
        'status',
        'notes',
    ];

    public const STATUS_PAID = 'paid';
    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PENDING = 'pending';

    protected $casts = [
        'amount' => 'decimal:2',
        'due_year' => 'integer',
        'due_date' => 'date',
        'paid_date' => 'date',
    ];

    protected $appends = [
        'amount_due',
        'paid_amount',
    ];

    // ==================== RELATIONSHIPS ====================
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function payments()
    {
        return $this->hasMany(DuesPayment::class, 'membership_due_id');
    }

    public function notifications()
    {
        return $this->hasMany(DuesNotification::class, 'membership_due_id');
    }

    public function getAmountDueAttribute()
    {
        return $this->amount;
    }

    public function getPaidAmountAttribute()
    {
        return $this->payments()->sum('amount');
    }

    // ==================== PAYMENT LOGIC ====================
    /**
     * Mark due as paid only for the specific year it was paid for
     * * Important: Only the year of payment is counted
     * If someone owes dues for 2022 and 2023 but only pays in 2023,
     * only the 2023 due is marked as paid. The 2022 due remains unpaid.
     */
    public function markAsPaid($paidAmount, $paidDate = null)
    {
        $paidDate = $paidDate ?? now()->toDateString();

        // Extract year from paid date
        $paymentYear = Carbon::createFromFormat('Y-m-d', $paidDate)->year;

        // Only mark as paid if payment year matches due year
        if ($paymentYear == $this->due_year) {
            $this->update([
                'status' => 'paid',
                'paid_date' => $paidDate,
                'amount' => $paidAmount,
            ]);
            
            // Update member's membership status
            $this->member->updateMembershipStatus();
            
            // Create payment received notification
            DuesNotification::create_notification(
                $this->member->id,
                'payment_received',
                'Payment Received',
                "Your membership dues payment of ₱" . number_format($paidAmount, 2) . " for {$this->due_year} has been received. Thank you!",
                $this->id
            );

            return true;
        }

        // If payment year doesn't match due year, don't mark as paid
        return false;
    }

    /**
     * Get dues that are overdue (unpaid and past due date)
     */
    public static function getOverdueDues()
    {
        return self::where('status', self::STATUS_UNPAID)
            ->where('due_date', '<', now())
            ->get();
    }

    /**
     * Get unpaid dues
     */
    public static function getPendingDues()
    {
        return self::where('status', self::STATUS_UNPAID)->get();
    }

    /**
     * Ensure existing members with a membership_end_date have a due record.
     * This is useful when members were imported or seeded before dues were auto-generated.
     */
    public static function generateMissingDuesForExistingMembers()
    {
        $members = Member::whereNotNull('membership_end_date')
            ->whereDoesntHave('membershipDues')
            ->get();

        foreach ($members as $member) {
            if (!$member->membership_end_date) {
                continue;
            }

            self::create([
                'member_id' => $member->id,
                'amount' => $member->membershipType?->price ?? 0,
                'due_year' => $member->membership_end_date->year,
                'due_date' => $member->membership_end_date,
                'status' => self::STATUS_UNPAID,
                'notes' => 'Automatically generated for existing member',
            ]);
        }
    }

    /**
     * Ensure an expired member has a membership due record.
     * This is used by the expiry notification command when membership_end_date has passed.
     */
    public static function ensureExpiredDueForMember(Member $member)
    {
        if (! $member->membership_end_date) {
            return null;
        }

        $dueYear = $member->membership_end_date->year;
        $amount = $member->membershipType?->price ?? 0;

        $due = self::firstOrNew([
            'member_id' => $member->id,
            'due_year' => $dueYear,
        ]);

        $due->amount = $amount;
        $due->due_date = $member->membership_end_date;

        if (! $due->exists) {
            $due->status = self::STATUS_UNPAID;
            $due->notes = 'Created after membership expired by expiry notification check.';
        } else {
            // Normalize any expired due that has not been paid (e.g., from pending to unpaid)
            if ($due->status !== self::STATUS_PAID) {
                $due->status = self::STATUS_UNPAID;
            }
        }

        // Save unconditionally to ensure database insertion and state updates
        $due->save();

        return $due;
    }

    /**
     * Get unpaid dues for a specific year range
     * (e.g., all unpaid dues from 2022 and 2023)
     */
    public function getUnpaidDuesForYears($startYear, $endYear)
    {
        return self::whereBetween('due_year', [$startYear, $endYear])
            ->where('status', '!=', 'paid')
            ->orderBy('due_year')
            ->get();
    }
}