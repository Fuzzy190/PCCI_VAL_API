# Membership Dues Implementation - Complete Overview

## 📋 What Was Created

### 1. **Migrations** (2 new tables)

#### `membership_dues` table
- Tracks recurring annual/periodic dues for each member
- **Key Fields:**
  - `member_id` (FK) - Links to the member
  - `amount` - Amount owed for that period
  - `due_year` - Which year the dues are for
  - `due_date` - When payment is due
  - `paid_date` - When it was actually paid (nullable)
  - `status` - pending | paid | overdue | waived
  - Unique constraint: One dues record per member per year

#### `dues_payments` table
- Records individual payment transactions for membership dues
- **Key Fields:**
  - `membership_due_id` (FK) - Links to the specific due
  - `member_id` (FK) - Links to the member
  - `received_by_user_id` (FK) - Who collected the payment
  - `or_number` - Official Receipt number (unique)
  - `amount` - Amount paid
  - `payment_date` - When payment was received
  - `payment_method` - cash | check | bank_transfer | etc
  - `reference_number` - Check #, transfer ID, etc

### 2. **Models** (2 new Eloquent models)

#### `App\Models\MembershipDue`
```php
// Relationships:
- member() → belongsTo Member
- payments() → hasMany DuesPayment
```

#### `App\Models\DuesPayment`
```php
// Relationships:
- membershipDue() → belongsTo MembershipDue
- member() → belongsTo Member
- receivedBy() → belongsTo User
```

### 3. **Updated Models**

#### `App\Models\Member`
Added relationships:
```php
- membershipDues() → hasMany MembershipDue
- duesPayments() → hasMany DuesPayment
```

---

## 🔄 Complete Data Flow

```
APPLICANT → MEMBER (1-to-1 relationship)
   ↓
   ├─→ PAYMENT (Initial membership fee)
   │   └── MembershipType
   │
   └─→ MEMBERSHIP_DUE (Annual/Recurring)
       ├─→ DUES_PAYMENT (Payment transaction)
       └── User (received_by)
```

---

## 👥 Role-based Flow (super_admin, admin, treasurer, member)

1. super_admin
   - Full access to all resources and APIs via middleware `role:super_admin`.
   - Can manage users, roles, applicants, members, membership types, payments, dues, and settings.
   - Can delete trustees/positions and perform DB refresh (dev route).
   - Can view and edit application status, approve/reject, and inspect any member’s dues/payment history.

2. admin
   - Access to applicant lifecycle management, membership type CRUD, activities, events, categories, trustees/positions (create/update).
   - Can view members and applicants (via `role:super_admin|admin|treasurer` endpoint group).
   - Cannot delete trustees/positions (super_admin only) and not in `role:super_admin|treasurer` payment-specific group.
   - Should be able to review and approve membership dues eligibility and trigger member creation for paid applicants.

3. treasurer
   - Limited to financial workflows: payment CRUD (`v1/payments`), payment reports, and `membership-types` read.
   - Applicant access only for approved/paid statuses (in `ApplicantController@index` and `show`).
   - In dues design, should create/update `MembershipDue` and `DuesPayment` records (collect dues, mark due status paid/overdue/waived) and audit via `received_by_user_id`.
   - Can view members and applicants (no full admin-only updates on applicants).

4. member
   - Self-service endpoints: own application (`v1/application` GET/PUT) and product resources.
   - Can view current user profile (`/v1/user`) and own dues/payment status once member record exists.
   - In dues design, should view own pending/overdue/paid dues and submit payments; no direct modification of other member records.

5. common user data flow for dues
   - MembershipDue record created (likely by super_admin/admin/treasurer or scheduled job) per member/year.
   - Member (or treasurer) records DuesPayment with `received_by_user_id` set to the authenticated user.
   - After payment, update MembershipDue status to `paid`, set `paid_date`, and optionally send notifications (overdue alerts via `ExpiringMembershipNotification`).

---

## 📊 Usage Example

### Creating a Membership Due
```php
$member = Member::find(1);

$due = MembershipDue::create([
    'member_id' => $member->id,
    'amount' => 5000.00,
    'due_year' => 2026,
    'due_date' => '2026-06-30',
    'status' => 'pending',
    'notes' => 'Annual membership dues'
]);
```

### Recording a Dues Payment
```php
$payment = DuesPayment::create([
    'membership_due_id' => $due->id,
    'member_id' => $member->id,
    'received_by_user_id' => auth()->id(),
    'or_number' => 'OR-2026-001',
    'amount' => 5000.00,
    'payment_date' => now(),
    'payment_method' => 'cash',
    'notes' => 'Full payment received'
]);

// Update due status
$due->update([
    'status' => 'paid',
    'paid_date' => now()
]);
```

### Querying Dues
```php
// Get all pending dues for a member
$member->membershipDues()->where('status', 'pending')->get();

// Get all payments for a due
$due->payments;

// Get overdue memberships
MembershipDue::where('status', 'overdue')->get();

// Total collected (with treasurer role check)
$collected = DuesPayment::whereYear('payment_date', 2026)
    ->sum('amount');
```

---

## 🔐 Security Considerations

- **Treasurer Role**: Existing security model already restricts treasurers to approved/paid records
- Apply same role-based filters when building API endpoints for dues
- Ensure `received_by_user_id` always records who entered the payment

---

## 🚀 Next Steps

1. **Create API Controllers**: `DuesController`, `DuesPaymentController`
2. **Create API Resources**: `DuesResource`, `DuesPaymentResource`
3. **Create Requests**: `StoreDuesRequest`, `StoreDuesPaymentRequest`
4. **Add API Routes**: In `routes/api.php`
5. **Create Tests**: Feature & Unit tests for dues logic
6. **Add Notifications**: For overdue dues (similar to `ExpiringMembershipNotification`)

