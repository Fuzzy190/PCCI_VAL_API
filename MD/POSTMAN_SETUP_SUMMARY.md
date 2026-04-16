# Postman API Setup - Complete Summary

## ✅ What Was Created

### 1. **API Controllers** (2 files)
- [MembershipDueController.php](app/Http/Controllers/Api/V1/MembershipDueController.php)
  - Create, read, update membership dues
  - Filter by member, year, status
  - Get statistics
  - Get pending/overdue dues

- [DuesPaymentController.php](app/Http/Controllers/Api/V1/DuesPaymentController.php)  
  - Record dues payments
  - List payments with filters
  - Get collections by year
  - Treasurer-specific queries
  - Payment statistics

### 2. **API Resources** (2 files)
- [MembershipDueResource.php](app/Http/Resources/MembershipDueResource.php)
  - Formats membership due data for API responses
  - Includes relationships (member, payments)

- [DuesPaymentResource.php](app/Http/Resources/DuesPaymentResource.php)
  - Formats payment data for API responses
  - Includes relationships (due, member, receiver)

### 3. **Form Requests** (2 files)
- [StoreMembershipDueRequest.php](app/Http/Requests/StoreMembershipDueRequest.php)
  - Validates input for creating dues
  - Checks authorization (treasurer/admin)
  - Validates fields: member_id, amount, due_year, due_date, status

- [StoreDuesPaymentRequest.php](app/Http/Requests/StoreDuesPaymentRequest.php)
  - Validates payment inputs
  - Checks authorization
  - Ensures OR number is unique
  - Validates payment_method, amount, date

### 4. **API Routes**
Updated [routes/api.php](routes/api.php) with:
```
RESOURCE ENDPOINTS:
  POST   /v1/membership-dues              → Create due
  GET    /v1/membership-dues              → List dues (paginated)
  GET    /v1/membership-dues/{id}         → Get single due
  PUT    /v1/membership-dues/{id}         → Update due

CUSTOM ENDPOINTS:
  GET    /v1/membership-dues/pending       → Pending dues
  GET    /v1/membership-dues/overdue       → Overdue dues
  GET    /v1/membership-dues/stats         → Statistics
  GET    /v1/members/{id}/unpaid-dues      → Member's unpaid dues

PAYMENT ENDPOINTS:
  POST   /v1/dues-payments                 → Record payment
  GET    /v1/dues-payments                 → List payments
  GET    /v1/dues-payments/{id}            → Get single payment
  GET    /v1/dues-payments/by-year         → Collections by year
  GET    /v1/dues-payments/treasurer-payments → Treasurer's payments
  GET    /v1/dues-payments/stats           → Payment statistics
  GET    /v1/membership-dues/{id}/payments → Payments for a due
```

### 5. **Documentation** (4 files)
- [POSTMAN_QUICK_START.md](POSTMAN_QUICK_START.md)
  - How to import collection
  - Setup environment variables
  - Testing workflow
  - Troubleshooting guide

- [POSTMAN_REQUESTS.md](POSTMAN_REQUESTS.md)
  - All 15 endpoint details
  - Request/response examples
  - Query parameters
  - Error responses

- [PCCI_Dues_API.postman_collection.json](PCCI_Dues_API.postman_collection.json)
  - Ready-to-import Postman collection
  - All endpoints pre-configured
  - Variables for base_url and token

- [DUES_PAYMENT_SYSTEM.md](DUES_PAYMENT_SYSTEM.md)
  - System architecture
  - Usage examples
  - Payment year rules
  - Warning system details

---

## 🚀 Quick Start (30 seconds)

### Step 1: Get Your API Token
```bash
cd your-laravel-project
php artisan tinker
```
```php
$user = App\Models\User::find(1);  // Your user
$token = $user->createToken('postman')->plainTextToken;
echo $token;
```

### Step 2: Import Postman Collection
1. Open **Postman**
2. **File → Import**
3. Select **PCCI_Dues_API.postman_collection.json**
4. Click **Import**

### Step 3: Set Environment Variables
In Postman:
- Click **Environments** → **Create**
- Name: `PCCI Local`
- Variables:
  - `base_url` = `http://localhost:8000/api`
  - `token` = Your token from Step 1
- Select environment from dropdown

### Step 4: Start Testing! ✅
- Expand **Membership Dues** → **Create New Due**
- Click **Send**
- See response!

---

## 📊 API Overview

### Membership Dues Table
Tracks annual dues that members must pay

| Field | Type | Notes |
|-------|------|-------|
| id | BIGINT | Primary key |
| member_id | FK | Links to member |
| amount | DECIMAL | Amount owed |
| due_year | YEAR | Which year (2023, 2024) |
| due_date | DATE | When due |
| paid_date | DATE | When paid |
| status | STRING | pending, paid, overdue, waived, expired |
| Unique | - | (member_id, due_year) - one per member per year |

### Dues Payments Table
Records individual payment transactions

| Field | Type | Notes |
|-------|------|-------|
| id | BIGINT | Primary key |
| membership_due_id | FK | Which due this pays for |
| member_id | FK | Who paid |
| received_by_user_id | FK | Who recorded payment (treasurer) |
| or_number | STRING | Official Receipt number (UNIQUE) |
| amount | DECIMAL | Amount paid |
| payment_date | DATE | When paid |
| payment_method | STRING | cash, check, bank_transfer, etc |
| reference_number | STRING | Check #, transfer ID |

---

## 💰 Payment Year Rule (CRITICAL)

**Only the payment year counts. Not retroactive.**

### Example:
- Member owes: 2023 (5000), 2024 (5000), 2025 (5000)
- Member pays on **2025-03-15** with amount **5000**
- **Result:**
  - ✅ 2025 marked as PAID
  - ❌ 2023 stays PENDING
  - ❌ 2024 stays PENDING
  - Still owes: 10,000

### Testing This:
```json
POST /v1/dues-payments
{
  "membership_due_id": 3,
  "or_number": "OR-2025-001",
  "amount": 5000,
  "payment_date": "2025-03-15",
  "payment_method": "cash"
}
```

Payment date year (2025) matches only the 2025 due!

---

## ⚠️ Warning System

Automatic warnings sent when member's membership expires:
- **First Warning** (~5 months before) - Logged in system
- **Second Warning** (~3 months before) - Logged in system
- **Final Warning** (~1 month before) - Logged in system

Example (member expires July 17, 2024):
- April 2024 → First warning
- May 2024 → Second warning
- June 2024 → Final warning

Tracked in database:
- `first_warning_sent_at`
- `second_warning_sent_at`
- `final_warning_sent_at`

---

## 🔐 Security & Access Control

### Roles:
- **Super Admin**: Full access to all endpoints
- **Admin**: Full access (except delete some resources)
- **Treasurer**: Read/Write dues and payments for approved members only

### Features:
- Role-based middleware on all endpoints
- Activities logged with `received_by_user_id`
- Treasurers can't access unapproved members
- Unique OR numbers prevent duplicate entries

---

## 📋 Top 5 Most Important Endpoints

### 1. Create Dues
```
POST /v1/membership-dues
```
Sets up annual dues for a member

### 2. Record Payment ⚠️
```
POST /v1/dues-payments
```
Most important! Marks dues as paid based on payment year

### 3. Get Unpaid Dues
```
GET /v1/members/{id}/unpaid-dues
```
See all dues a member still owes across years

### 4. Collection Statistics
```
GET /v1/dues-payments/stats?year=2024
```
Treasurer dashboard: total collected, by payment method

### 5. Get Pending Dues
```
GET /v1/membership-dues/pending
```
Quick view of all unpaid dues in system

---

## 🧪 Test Scenarios

### Scenario 1: Create and Pay Single Year
1. Create due for 2024 → `POST /v1/membership-dues`
2. Record payment → `POST /v1/dues-payments`
3. Check status → `GET /v1/membership-dues/1`

### Scenario 2: Multi-Year Arrears
1. Create 3 dues (2023, 2024, 2025) → `POST /v1/membership-dues` (3x)
2. Get unpaid → `GET /v1/members/1/unpaid-dues` → Shows 3 pending
3. Pay 2025 → `POST /v1/dues-payments` with date 2025-XX-XX
4. Check unpaid → `GET /v1/members/1/unpaid-dues` → Shows 2 pending (2023, 2024)

### Scenario 3: Collect by Year
1. Record 5 payments with 2024 dates
2. Get collection → `GET /v1/dues-payments/stats?year=2024`
3. See total for 2024

---

## 📁 Files Changed/Created

```
✅ NEW FILES:
   app/Http/Controllers/Api/V1/MembershipDueController.php
   app/Http/Controllers/Api/V1/DuesPaymentController.php
   app/Http/Resources/MembershipDueResource.php
   app/Http/Resources/DuesPaymentResource.php
   app/Http/Requests/StoreMembershipDueRequest.php
   app/Http/Requests/StoreDuesPaymentRequest.php
   POSTMAN_QUICK_START.md
   POSTMAN_REQUESTS.md
   PCCI_Dues_API.postman_collection.json
   DUES_PAYMENT_SYSTEM.md

✅ UPDATED FILES:
   routes/api.php (added route imports and endpoints)
   database/migrations/2026_03_26_100000_create_membership_dues_table.php
   app/Providers/AppServiceProvider.php
   app/Models/Member.php
```

---

## 🎯 Next Steps

1. ✅ Run Laravel migrations (if not done):
   ```bash
   php artisan migrate
   ```

2. Start Postman and import collection

3. Get your Sanctum token and set environment

4. Test endpoints in this order:
   - Create Membership Due
   - List All Dues
   - Get Single Due
   - Record Payment
   - Get Payment Statistics

5. Try multi-year scenario

6. Explore filtering and statistics

---

## 🆘 Troubleshooting

### "Unauthorized" Error
- Check token is valid
- Verify user has correct role

### "Member does not exist"
- Verify member_id in request
- Member must be in members table

### "This OR number already exists"
- Use unique OR numbers
- Each receipt must be different

### "Payment year does not match due year"
- Ensure payment_date year matches due year
- If due is 2024, payment must be dated 2024

### Collection not showing up
- Check pagination (default page=1, per_page=20)
- Use query parameters to filter

---

## 📞 Quick Reference

| Need | Endpoint |
|------|----------|
| Create due | `POST /v1/membership-dues` |
| List dues | `GET /v1/membership-dues` |
| Record payment | `POST /v1/dues-payments` |
| Pending dues | `GET /v1/membership-dues/pending` |
| Overdue dues | `GET /v1/membership-dues/overdue` |
| Member unpaid | `GET /v1/members/{id}/unpaid-dues` |
| Stats | `GET /v1/dues-payments/stats?year=YYYY` |
| Treasurer payments | `GET /v1/dues-payments/treasurer-payments` |

---

## ✨ You're All Set!

Everything is ready for Postman testing. Import the collection and start exploring! 🎉

**For detailed request/response examples, see:** [POSTMAN_REQUESTS.md](POSTMAN_REQUESTS.md)

**For system details, see:** [DUES_PAYMENT_SYSTEM.md](DUES_PAYMENT_SYSTEM.md)

**For quick start help, see:** [POSTMAN_QUICK_START.md](POSTMAN_QUICK_START.md)
