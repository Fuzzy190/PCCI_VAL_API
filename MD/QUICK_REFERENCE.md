# Quick Reference Guide - Dues & Notifications

## Overview
Complete membership dues system with automatic in-app notifications for PCCI members.

---

## 🚀 Quick Start

### 1. Deploy to Database
```bash
php artisan migrate
```

### 2. Test Notifications
```bash
php artisan tinker

# Create test data
$member = App\Models\Member::first();
$member->membership_end_date = now()->addMonths(4)->endOfDay();
$member->save();

$due = App\Models\MembershipDue::create([
    'member_id' => $member->id,
    'amount' => 5000,
    'due_year' => 2026,
    'status' => 'pending'
]);

# Trigger warning
$due->checkAndSendExpirationWarnings();

# Check notifications
App\Models\DuesNotification::latest()->first();
```

### 3. Test API
```bash
# Get notifications
curl -H "Authorization: Bearer TOKEN" \
  http://api.pcci.local/api/v1/members/1/notifications

# Mark as read
curl -X PUT -H "Authorization: Bearer TOKEN" \
  http://api.pcci.local/api/v1/notifications/1/mark-as-read
```

---

## 📁 Key Files

### Models
| File | Purpose |
|------|---------|
| `MembershipDue.php` | Annual dues tracking + warnings |
| `DuesPayment.php` | Payment recording + validation |
| `DuesNotification.php` | In-app notifications |

### Controllers
| File | Purpose |
|------|---------|
| `MembershipDueController.php` | Dues endpoints |
| `DuesPaymentController.php` | Payment endpoints |
| `MemberNotificationController.php` | Notification endpoints |

### Resources
| File | Purpose |
|------|---------|
| `MembershipDueResource.php` | Dues API response |
| `DuesPaymentResource.php` | Payment API response |
| `DuesNotificationResource.php` | Notification API response |

---

## 🔗 API Endpoints Quick Reference

### Notifications (8 endpoints)
```
GET    /api/v1/members/{id}/notifications
GET    /api/v1/members/{id}/notifications/unread
GET    /api/v1/members/{id}/notifications/by-type?type=first_warning
GET    /api/v1/members/{id}/notifications/stats
PUT    /api/v1/notifications/{id}/mark-as-read
PUT    /api/v1/notifications/{id}/mark-as-unread
PUT    /api/v1/members/{id}/notifications/mark-all-read
DELETE /api/v1/notifications/{id}
```

### Membership Dues (8 endpoints)
```
POST   /api/v1/membership-dues
GET    /api/v1/membership-dues
PUT    /api/v1/membership-dues/{id}
GET    /api/v1/membership-dues/pending
GET    /api/v1/membership-dues/overdue
GET    /api/v1/membership-dues/stats
GET    /api/v1/members/{id}/unpaid-dues
```

### Dues Payments (7+ endpoints)
```
POST   /api/v1/dues-payments
GET    /api/v1/dues-payments
GET    /api/v1/dues-payments/{id}
GET    /api/v1/dues-payments/stats?year=2024
GET    /api/v1/dues-payments/by-year
GET    /api/v1/dues-payments/treasurer-payments
GET    /api/v1/membership-dues/{id}/payments
```

---

## 📊 Database Tables

### membership_dues
```
id | member_id | amount | due_year | due_date | paid_date | status | 
first_warning_sent_at | second_warning_sent_at | final_warning_sent_at | notes

Unique: (member_id, due_year)
```

### dues_payments
```
id | membership_due_id | member_id | user_id (received_by) | amount | 
or_number | payment_method | payment_date

Unique: or_number
```

### dues_notifications
```
id | member_id | membership_due_id | type | title | message | data (JSON) | 
is_read | read_at | created_at | updated_at

Types: first_warning, second_warning, final_warning, expired, payment_received
```

---

## 🎯 Business Logic

### Payment Year Validation
**Rule:** Only payment year counts. 2024 payment can only mark 2024 due as paid.
```php
$paymentYear = Carbon::createFromFormat('Y-m-d', $paidDate)->year;
if ($paymentYear == $this->due_year) {
    // Mark as paid
}
```

### Warning Timeline
| Days | Warning | Type |
|------|---------|------|
| 150 | First | "Membership Renewal Notice" |
| 90 | Second | "Membership Renewal Reminder" |
| 30 | Final | "URGENT: Membership Expiring Soon" |
| 0 | Expired | "Membership Expired" |
| On Payment | - | "Payment Received" |

### Observer Pattern
```php
// Automatic when MembershipDue is accessed
MembershipDueObserver boots on: retrieved, created, saved
Calls: checkAndSendExpirationWarnings()
Creates: In-app notification if threshold crossed
```

---

## 🔐 Authorization

### Member
- [x] View own notifications
- [x] Mark own notifications read/unread
- [x] Cannot view others' notifications

### Treasurer
- [x] View approved members' dues
- [x] Record payments
- [x] Cannot view other treasurers' members

### Admin/Super Admin
- [x] View all members' dues
- [x] View all members' notifications
- [x] Manage all statuses
- [x] Delete any notification

---

## 📝 Common Scenarios

### Create Membership Due
```php
$due = MembershipDue::create([
    'member_id' => $member->id,
    'amount' => 5000,
    'due_year' => 2026,
    'due_date' => now(),
    'status' => 'pending'
]);
```

### Record Payment
```php
$payment = DuesPayment::create([
    'membership_due_id' => $due->id,
    'member_id' => $member->id,
    'user_id' => auth()->id(),
    'amount' => 5000,
    'or_number' => 'OR-001',
    'payment_method' => 'cash',
    'payment_date' => now()
]);

// Automatically creates payment_received notification
// Marks due as paid if year matches
```

### Check Expiration & Send Warnings
```php
$due->checkAndSendExpirationWarnings();
// Creates notifications if within warning thresholds
```

### Get Statistics
```php
$stats = [
    'total' => DuesNotification::where('member_id', $memberId)->count(),
    'unread' => DuesNotification::where('member_id', $memberId)->unread()->count(),
    'by_type' => DuesNotification::where('member_id', $memberId)
        ->selectRaw('type, count(*) as count')
        ->groupBy('type')
        ->pluck('count', 'type')
];
```

---

## 🐛 Debugging

### Check if Observer is Registered
```bash
grep -n "MembershipDueObserver" app/Providers/AppServiceProvider.php
# Should show: ->observe(MembershipDue::class, MembershipDueObserver::class);
```

### Check Notifications Created
```sql
SELECT * FROM dues_notifications 
WHERE member_id = 1 
ORDER BY created_at DESC;
```

### Check Warning Timestamps
```sql
SELECT id, due_year, first_warning_sent_at, second_warning_sent_at, final_warning_sent_at 
FROM membership_dues 
WHERE member_id = 1;
```

### Manual Warning Check
```bash
php artisan tinker
>>> $due = App\Models\MembershipDue::find(1);
>>> $due->checkAndSendExpirationWarnings();
>>> App\Models\DuesNotification::latest()->first();
```

---

## 📚 Documentation

| File | Content |
|------|---------|
| `SYSTEM_COMPLETE.md` | **📍 START HERE** - Complete overview |
| `DUES_NOTIFICATIONS_API.md` | **Full API reference** with examples |
| `DUES_PAYMENT_SYSTEM.md` | Architecture & business logic |
| `IMPLEMENTATION_SUMMARY.md` | Component breakdown |
| `POSTMAN_NOTIFICATIONS_GUIDE.md` | Postman setup |
| `DEPLOYMENT_CHECKLIST.md` | Deploy & validation steps |

---

## 🚨 Critical Remember

### ⚠️ Payment Year Rule
```
Member owes: 2023, 2024, 2025 dues
Payment in 2024: Only 2024 marked paid
Result: 2023 & 2025 still unpaid
```

### ⚠️ Cascading Deletes
```
Deleting Member → Deletes Dues → Deletes Payments → Deletes Notifications
```

### ⚠️ Unique Constraint
```
Each member can only have 1 due per year
(member_id, due_year) = UNIQUE
```

---

## 🔗 Relationships

### MembershipDue
```
belongsTo: Member
hasMany: DuesPayment
hasMany: DuesNotification (new)
```

### DuesPayment
```
belongsTo: MembershipDue
belongsTo: Member
belongsTo: User (as received_by)
```

### DuesNotification
```
belongsTo: Member
belongsTo: MembershipDue
scopes: unread(), read(), byType()
```

---

## 🎨 Response Format

### Notification Response
```json
{
  "id": 1,
  "type": "first_warning",
  "title": "Membership Renewal Notice",
  "message": "Your PCCI membership is expiring...",
  "data": null,
  "is_read": false,
  "read_at": null,
  "created_at": "2024-03-31 10:30:00",
  "member": {
    "id": 5,
    "name": "John Doe"
  },
  "membership_due": {
    "id": 12,
    "due_year": 2026,
    "amount": "5000.00",
    "status": "pending"
  }
}
```

### Statistics Response
```json
{
  "total": 5,
  "unread": 2,
  "by_type": {
    "first_warning": 1,
    "second_warning": 1,
    "final_warning": 1,
    "expired": 1,
    "payment_received": 1
  }
}
```

---

## 📦 Installation Checklist

- [ ] Run migrations
- [ ] Verify tables created
- [ ] Test API endpoints
- [ ] Check observer registered
- [ ] Verify notifications creating
- [ ] Test role-based access
- [ ] Update Postman collections
- [ ] Monitor logs

---

## 🎯 What's Next?

**Immediate:**
1. Run migrations: `php artisan migrate`
2. Test with Postman
3. Verify notifications creating

**Soon:**
1. Enable email notifications (uncomment in MembershipDue)
2. Set up scheduler for notification checks
3. Create admin dashboard

**Future:**
1. SMS notifications
2. Bulk dues import
3. Payment installments
4. Delinquency reports

---

## 💡 Tips

### Get latest notification
```bash
App\Models\DuesNotification::latest()->first()
```

### Count unread for member
```bash
App\Models\DuesNotification::where('member_id', $id)->unread()->count()
```

### Filter by type
```bash
App\Models\DuesNotification::where('member_id', $id)
    ->byType('first_warning')
    ->get()
```

### Mark member's notifications as read
```bash
App\Models\DuesNotification::where('member_id', $id)
    ->unread()
    ->update(['is_read' => true, 'read_at' => now()])
```

---

**Quick Reference v1.0 | March 31, 2024 | Status: ✅ COMPLETE**
