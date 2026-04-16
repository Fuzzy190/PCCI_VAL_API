<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DuesNotification extends Model
{
    use HasFactory;

    protected $table = 'dues_notifications';

    protected $fillable = [
        'member_id',
        'membership_due_id',
        'type',
        'title',
        'message',
        'data',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'data' => 'json',
        'read_at' => 'datetime',
    ];

    // Relationships
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function membershipDue()
    {
        return $this->belongsTo(MembershipDue::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Methods
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function markAsUnread()
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Create a notification for a subscription
     */
    public static function create_notification($memberId, $type, $title, $message, $membershipDueId = null, $data = null)
    {
        return self::create([
            'member_id' => $memberId,
            'membership_due_id' => $membershipDueId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Get notification count for member
     */
    public static function getUnreadCount($memberId)
    {
        return self::where('member_id', $memberId)->unread()->count();
    }
}
