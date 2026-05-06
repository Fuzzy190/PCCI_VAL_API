<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'transactions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'or_number',
        'transaction_type',
        'applicant_id',
        'member_id',
        'membership_due_id',
        'amount',
        'payment_method',
        'proof_of_payment_path',
        'status',
        'processed_by_user_id',
        'notes',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the applicant associated with this transaction (if it's an initial registration).
     */
    public function applicant()
    {
        return $this->belongsTo(Applicant::class, 'applicant_id');
    }

    /**
     * Get the member associated with this transaction (if it's a renewal).
     */
    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * Get the specific membership due this transaction is paying for.
     */
    public function membershipDue()
    {
        return $this->belongsTo(MembershipDue::class, 'membership_due_id');
    }

    /**
     * Get the user (admin/treasurer) who processed/approved this transaction.
     */
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }
}