# Membership Dues & Expiration Warnings - Complete Guide

## 📋 Overview

This system tracks membership dues payments and automatically sends expiration warnings to members based on their membership end date.

---

## 🎯 Scenario Example

Let's say a member joins on **July 17, 2023** with a 1-year membership expiring on **July 17, 2024**:

### Timeline of Warnings
```
April 2024 (5 months before) → First Warning ⚠️
May 2024 (3 months before)   → Second Warning ⚠️
June 2024 (1 month before)   → Final Warning ⚠️
July 17, 2024                → Membership Expires 🔴
```

The system automatically sends these warnings by checking the membership during operations.

---

## 💾 Database Tables

### `membership_dues` Table
Tracks the annual dues that need to be paid by each member.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary Key |
| member_id | BIGINT | FK to members - Which member owes the dues |
| amount | DECIMAL(10,2) | Amount owed for that year |
| due_year | YEAR | Which year these dues are for (2023, 2024, etc) |
| due_date | DATE | When the dues were due |
| paid_date | DATE | When they were actually paid (NULL if unpaid) |
| status | VARCHAR | pending \| paid \| overdue \| waived \| expired |
| first_warning_sent_at | TIMESTAMP | When first warning was sent (5 months before expiration) |
| second_warning_sent_at | TIMESTAMP | When second warning was sent (3 months before expiration) |
| final_warning_sent_at | TIMESTAMP | When final warning was sent (1 month before expiration) |
| notes | TEXT | Additional notes |
| created_at | TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | Last update time |
| **Unique Constraint** | - | `(member_id, due_year)` - Only 1 due per member per year |

### `dues_payments` Table
Records individual payment transactions for membership dues.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary Key |
| membership_due_id | BIGINT | FK to membership_dues - Which due this payment is for |
| member_id | BIGINT | FK to members - Who made the payment |
| received_by_user_id | BIGINT | FK to users - Who received/recorded the payment |
| or_number | VARCHAR | Official Receipt number (UNIQUE) |
| amount | DECIMAL(10,2) | Amount paid |
| payment_date | DATE | When payment was received |
| payment_method | VARCHAR | cash \| check \| bank_transfer \| online \| etc |
| reference_number | VARCHAR | Check #, transfer ID, etc |
| notes | TEXT | Additional notes |
| created_at | TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | Last update time |

---

## 🔄 Payment Rules - KEY CONCEPT

### Rule: Only the Year of Payment Counts

**IMPORTANT:** If a member owes dues for multiple years but only pays for one, only that year's due is marked as paid.

#### Example Scenario

**Timeline:**
- Member registered: July 17, 2023
- Owes dues: 2023, 2024, 2025 (3 years)
- Total owed: 5000 × 3 = 15,000

**When they pay on March 15, 2025 with amount 5000:**
- ✅ 2025 due is marked as PAID
- ❌ 2023 due remains PENDING
- ❌ 2024 due remains PENDING
- 💰 Still owes: Previous 2 years = 10,000

**If they later pay again on June 20, 2025 with amount 10,000:**
- ✅ 2024 due is marked as PAID
- ❌ 2023 due remains PENDING (different year, needs separate payment)
- 💰 Still owes: 5,000 (from 2023)

---

## 📊 Eloquent Models

### MembershipDue Model
```php
use App\Models\MembershipDue;

$due = MembershipDue::find(1);

// Relationships
$due->member;              // The member who owes
$due->payments;            // All payments for this due

// Methods
$due->checkAndSendExpirationWarnings();  // Manually trigger warning check
$due->markAsPaid($amount, $date);        // Mark as paid (only if year matches)
MembershipDue::getOverdueDues();         // Get all overdue dues
MembershipDue::getPendingDues();         // Get all pending dues
$due->getUnpaidDuesForYears(2022, 2024); // Get unpaid from year range
```

### DuesPayment Model
```php
use App\Models\DuesPayment;

$payment = DuesPayment::find(1);

// Relationships
$payment->membershipDue();  // The specific due this payment covers
$payment->member;           // The member who paid
$payment->receivedBy;       // The user who recorded payment

// Methods
DuesPayment::recordPayment($due, $user, $paymentData);     // Record a payment
DuesPayment::getPaymentsByMemberAndYear($memberId, 2024);  // Get member's payments for a year
DuesPayment::getTotalCollectedByYear(2024);                // Total collected in a year
DuesPayment::getTotalPaidByMember($memberId);              // Total paid by a member
DuesPayment::getPaymentsReceivedByUser($userId);           // Payments recorded by treasurer
DuesPayment::validatePayment($due, $paymentData);          // Validate before recording
```

---

## 💻 Usage Examples

### Create Annual Dues for a Member

```php
use App\Models\Member;
use App\Models\MembershipDue;
use Carbon\Carbon;

$member = Member::find(1);  // Find the member

// Create due for 2024
$due = MembershipDue::create([
    'member_id' => $member->id,
    'amount' => 5000.00,
    'due_year' => 2024,
    'due_date' => '2024-01-31',
    'status' => 'pending',
    'notes' => 'Annual membership dues for 2024'
]);

// The observer will automatically check for expiration warnings
```

### Record a Dues Payment

```php
use App\Models\DuesPayment;
use App\Models\MembershipDue;

$due = MembershipDue::where('member_id', 1)->where('due_year', 2024)->first();
$treasurerUser = auth()->user();

$paymentData = [
    'amount' => 5000.00,
    'or_number' => 'OR-2024-0001',
    'payment_date' => '2024-03-15',
    'payment_method' => 'cash',
    'reference_number' => null,
    'notes' => 'Payment received in person'
];

// Validate first
$errors = DuesPayment::validatePayment($due, $paymentData);
if (!empty($errors)) {
    return response()->json(['errors' => $errors], 422);
}

// Record the payment
$success = DuesPayment::recordPayment($due, $treasurerUser, $paymentData);

if ($success) {
    // Payment recorded and due marked as paid
    // The due->checkAndSendExpirationWarnings() is automatically triggered
} else {
    // Payment year didn't match due year
    return response()->json([
        'error' => 'Payment year does not match the due year'
    ], 422);
}
```

### Get Member's Unpaid Dues from Multiple Years

```php
$member = Member::find(1);
$upaidDues = $member->membershipDues()
    ->whereIn('status', ['pending', 'overdue'])
    ->orderBy('due_year')
    ->get();

foreach ($unpaidDues as $due) {
    echo "Year {$due->due_year}: {$due->amount} (Status: {$due->status})\n";
}
```

### Generate Dues Report for Treasurer

```php
// Total collected in 2024
$totalCollected = DuesPayment::getTotalCollectedByYear(2024);
echo "Total Collected in 2024: {$totalCollected}\n";

// Payments received by current treasurer
$treasurerPayments = DuesPayment::getPaymentsReceivedByUser(auth()->id());
echo "Total recorded by you: " . $treasurerPayments->sum('amount') . "\n";

// All overdue dues
$overdues = MembershipDue::getOverdueDues();
echo "Number of overdue accounts: " . $overdues->count() . "\n";
```

---

## ⚠️ Expiration Warning System

### How It Works

1. **Automatic Detection**: When a `MembershipDue` is retrieved or updated, the observer automatically checks if warnings should be sent.

2. **Warning Timeline** (relative to membership end date):
   - **First Warning**: ~5 months before (April if July expiration)
   - **Second Warning**: ~3 months before (May if July expiration)
   - **Final Warning**: ~1 month before (June if July expiration)

3. **Tracking**: Each warning is tracked in the `membership_dues` table with timestamps:
   ```
   first_warning_sent_at
   second_warning_sent_at
   final_warning_sent_at
   ```

4. **Implementation**: The `checkAndSendExpirationWarnings()` method in `MembershipDue` model:
   - Calculates days until membership expiration
   - Sends warnings at appropriate intervals
   - Logs the warnings (TODO: integrate with email/SMS)

### Current Implementation (TODO)

In `MembershipDue` model, the warning methods are logged:
```php
private function sendFirstWarning()
{
    \Log::info("First Warning - Membership expiring soon for Member ID: {$this->member_id}");
}
```

**To Implement Notifications:**
1. Create notification classes (e.g., `MembershipExpiringFirstWarning`)
2. Use Laravel's notification system in the warning methods
3. Send email/SMS to the member and/or treasurer

---

## 🔐 Security & Role-Based Access

The existing role-based access control applies:
- **Treasurer Role**: Can only view and record payments for approved members
- **Admin/Super Admin**: Full access
- Always record `received_by_user_id` to track who processed each payment

---

## 📋 Checklist for Implementation

- [x] Created `MembershipDue` model with expiration logic
- [x] Created `DuesPayment` model with payment recording
- [x] Created `MembershipDueObserver` for automatic warning checks
- [x] Registered observer in `AppServiceProvider`
- [x] Updated migrations with warning tracking fields
- [ ] Create `DuesController` API endpoint
- [ ] Create `DuesResource` for API responses
- [ ] Create `StoreDuesRequest` validation
- [ ] Create `StoreDuesPaymentRequest` validation
- [ ] Add API routes in `routes/api.php`
- [ ] Implement email/SMS notifications for warnings
- [ ] Create tests for dues functionality
- [ ] Create scheduled task to generate annual dues (artisan command or scheduler)

