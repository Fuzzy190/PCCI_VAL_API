# Payment vs Dues - Quick Reference

## 💳 Existing Payment System (Initial Fee)

**Purpose**: Track initial membership joining fees

| Property | Value |
|----------|-------|
| Table | `payments` |
| Model | `App\Models\Payment` |
| Triggered When | Applicant pays to join |
| Frequency | ONE TIME (one per applicant) |
| Relationship | `Applicant → Payment` |
| Unique Constraint | One payment per applicant |
| Fields | or_number, amount, payment_date, membership_type_id |

**Database Schema**:
```
payments
├─ applicant_id (FK) ← Links to applicant
├─ membership_type_id (FK) ← Which membership tier
├─ or_number (UNIQUE)
├─ amount
├─ received_by_user_id (FK)
└─ payment_date
```

---

## 🔄 New Dues System (Recurring Fee)

**Purpose**: Track annual/recurring membership dues

| Property | Value |
|----------|-------|
| Tables | `membership_dues` + `dues_payments` |
| Models | `App\Models\MembershipDue` + `App\Models\DuesPayment` |
| Triggered When | Each year/period for active member |
| Frequency | RECURRING (annually) |
| Relationship | `Member → MembershipDue → DuesPayment` |
| Unique Constraint | One due per member per year |
| Fields | amount, due_year, status, paid_date |

**Database Schema**:
```
membership_dues
├─ member_id (FK) ← Links to member
├─ amount
├─ due_year (UNIQUE with member_id)
├─ due_date
├─ paid_date
├─ status (pending/paid/overdue/waived)
└─ notes

dues_payments
├─ membership_due_id (FK) ← Links to the due
├─ member_id (FK)
├─ or_number (UNIQUE)
├─ amount
├─ received_by_user_id (FK)
├─ payment_date
├─ payment_method
└─ reference_number
```

---

## 🔀 Side-by-Side Comparison

| Aspect | Payment | Dues |
|--------|---------|------|
| **Who records it?** | Applicant (joining) | Treasurer (collecting annual) |
| **Record count** | 1 per applicant | 1 per member per year |
| **Can have issues** | No - one and done | Yes - pending, overdue, waived |
| **Payment method tracked** | No | Yes (cash, check, transfer) |
| **Used for** | New member fees | Annual maintenance |
| **Query example** | `Payment::where('applicant_id', X)` | `MembershipDue::where('member_id', X)->where('due_year', 2026)` |

---

## 📈 Controller Examples

### Get Member's Payment History
```php
// Initial joining fee
$payment = Payment::where('applicant_id', $applicant->id)->first();

// Annual dues across years
$dues = MembershipDue::where('member_id', $member->id)->get();

// All dues payments this year
$duesCollected = $member->duesPayments()
    ->whereYear('payment_date', 2026)
    ->sum('amount');
```

---

## 🎯 Workflow Examples

### Applicant Joins (Initial Payment)
```
1. Applicant submits form
2. Admin approves → Creates Payment record
3. Applicant/Treasurer records Payment (or_number, amount)
4. Applicant becomes Member
```

### Annual Dues Collection (New System)
```
1. Admin/System creates MembershipDue for active members (due_year = 2026)
2. Member receives notice due_date is approaching
3. Treasurer records DuesPayment when member pays
4. MembershipDue status changes from 'pending' → 'paid'
5. If not paid by due_date → status becomes 'overdue'
```

