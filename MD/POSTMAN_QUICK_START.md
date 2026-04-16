# Quick Start Guide - Postman Testing

## 1. Import the Collection

### Option A: Import from File
1. Open **Postman**
2. Click **File → Import**
3. Select **PCCI_Dues_API.postman_collection.json**
4. Click **Import**

### Option B: Import from Raw JSON
1. Open **Postman**
2. Click the **"+"** tab
3. Click **"Import"** (top left)
4. Go to **Raw text** tab
5. Paste the collection JSON
6. Click **Resolve**

---

## 2. Set Up Environment Variables

### In Postman:
1. Click **Environments** (left sidebar)
2. Click **"+"** to create new environment
3. Name it: `PCCI Local`
4. Add these variables:

| Key | Value | Type |
|-----|-------|------|
| `base_url` | `http://localhost:8000/api` | string |
| `token` | `your_sanctum_token_here` | string |

5. Select this environment from the dropdown (top right)

### Get Your Sanctum Token:
```bash
# In your Laravel app, run:
php artisan tinker
```

Then in tinker:
```php
$user = App\Models\User::where('email', 'your_email@example.com')->first();
$token = $user->createToken('postman')->plainTextToken;
echo $token;
```

Paste this token into the `token` variable in Postman.

---

## 3. Testing Workflow

### Step 1: Create Members First
Before creating dues, you need members in the system.

```
GET /v1/members
```

If no members exist, you'll need to approve some applicants first.

---

### Step 2: Create Membership Dues
```
POST /v1/membership-dues
```

**Body:**
```json
{
  "member_id": 1,
  "amount": 5000.00,
  "due_year": 2024,
  "due_date": "2024-01-31",
  "status": "pending",
  "notes": "Annual membership dues"
}
```

✅ Should return the created due with status: "pending"

---

### Step 3: Record a Payment
```
POST /v1/dues-payments
```

**⚠️ IMPORTANT RULE:** Payment year must match due year!

**Body (if membership expires July 17, 2024):**
```json
{
  "membership_due_id": 1,
  "or_number": "OR-2024-0001",
  "amount": 5000.00,
  "payment_date": "2024-03-15",
  "payment_method": "cash",
  "notes": "Payment received"
}
```

✅ Payment recorded + Due marked as PAID

---

### Step 4: Check Payment Statistics
```
GET /v1/dues-payments/stats?year=2024
```

✅ Returns collection statistics by payment method

---

## 4. Common Postman Tests

Add these test scripts to verify responses:

### Test 1: Verify Status Code
```javascript
pm.test("Status code is 200/201", function () {
    pm.expect(pm.response.code).to.be.oneOf([200, 201]);
});
```

### Test 2: Verify Response Has ID
```javascript
pm.test("Response has ID", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.data.id).to.exist;
});
```

### Test 3: Verify Amount is Decimal
```javascript
pm.test("Amount is decimal", function () {
    var jsonData = pm.response.json();
    pm.expect(parseFloat(jsonData.data.amount)).to.be.a('number');
});
```

### Test 4: Save ID for Next Request
```javascript
pm.test("Save due ID for next request", function () {
    var jsonData = pm.response.json();
    pm.environment.set("due_id", jsonData.data.id);
});
```

---

## 5. Pre-request Scripts for Automation

### Auto-generate OR Number
In **Pre-request Script** tab of "Record Payment" request:

```javascript
// Generate unique OR number
const timestamp = new Date().getTime();
pm.environment.set("or_number", "OR-2024-" + timestamp);
```

Then in body, use: `"or_number": "{{or_number}}"`

---

## 6. Troubleshooting

### Error: "Unauthorized access"
```json
{
  "message": "Unauthorized access"
}
```
**Solution:** 
- Check your token is valid
- Verify user has correct role (treasurer, admin, or super_admin)

---

### Error: "Payment year does not match due year"
```json
{
  "message": "Payment validation failed",
  "errors": ["Payment year 2023 does not match due year 2024"]
}
```
**Solution:** 
- Make sure `payment_date` year matches the `membership_due.due_year`
- Example: If due year is 2024, payment date must be in 2024

---

### Error: "This OR number already exists"
```json
{
  "errors": ["This OR number already exists"]
}
```
**Solution:** 
- Use a unique OR number
- Each receipt number must be unique in the system

---

### Error: "Member does not exist"
```json
{
  "errors": ["Member does not exist"]
}
```
**Solution:** 
- Verify member_id exists in members table
- Check that applicant was approved and converted to member

---

## 7. Full Testing Example - Multi-Year Dues Scenario

**Scenario:** Member registered 2023-07-17, expires 2024-07-17. Owes 3 years.

**Step 1:** Create dues for 2023
```
POST /v1/membership-dues
{
  "member_id": 1,
  "amount": 5000,
  "due_year": 2023,
  "due_date": "2023-12-31"
}
```

**Step 2:** Create dues for 2024
```
POST /v1/membership-dues
{
  "member_id": 1,
  "amount": 5000,
  "due_year": 2024,
  "due_date": "2024-01-31"
}
```

**Step 3:** Create dues for 2025
```
POST /v1/membership-dues
{
  "member_id": 1,
  "amount": 5000,
  "due_year": 2025,
  "due_date": "2025-01-31"
}
```

**Step 4:** Check unpaid dues
```
GET /v1/members/1/unpaid-dues
```
Response: Shows 3 pending dues (2023, 2024, 2025)

**Step 5:** Pay only 2025 (dated 2025-03-15)
```
POST /v1/dues-payments
{
  "membership_due_id": 3,
  "or_number": "OR-2025-0001",
  "amount": 5000,
  "payment_date": "2025-03-15",
  "payment_method": "cash"
}
```

**Step 6:** Check unpaid dues again
```
GET /v1/members/1/unpaid-dues
```
Response: Shows 2 pending dues (2023, 2024)
- 2025 is now PAID ✅
- 2023, 2024 still PENDING ❌

**Step 7:** Pay 2024 separately
```
POST /v1/dues-payments
{
  "membership_due_id": 2,
  "or_number": "OR-2025-0002",
  "amount": 5000,
  "payment_date": "2025-06-20",
  "payment_method": "bank_transfer"
}
```

**Step 8:** Check stats
```
GET /v1/dues-payments/stats?year=2025
```
Response: Total collected = 10,000 (2 payments × 5000)

---

## 8. Collection Organization Tips

### Create Folders:
1. **Membership Dues**
   - Create
   - List
   - Get Single
   - List Pending
   - List Overdue
   - Statistics

2. **Dues Payments**
   - Record Payment
   - List All
   - Get Single
   - By Year
   - Treasurer Payments
   - Statistics

3. **Test Scenarios**
   - Multi-year Payment Test
   - Single Payment Test
   - Overdue Payment Test

### Save Requests:
- Right-click request → Add to collection

---

## 9. Common Request Patterns

### Filter by Member and Year
```
GET /v1/membership-dues?member_id=1&due_year=2024
```

### Paginate Results
```
GET /v1/membership-dues?page=2&per_page=10
```

### Get Multiple Filters
```
GET /v1/dues-payments?member_id=1&year=2024&page=1
```

---

## 10. Performance Tips

- Use **pagination** for large datasets
- Use **query parameters** to filter before returning
- Avoid fetching all records if you only need specific ones

Example - DON'T do this:
```
GET /v1/membership-dues  (returns all dues)
```

DO this instead:
```
GET /v1/membership-dues?member_id=1  (returns only member 1's dues)
```

---

## 11. Useful Resources

- API Documentation: [POSTMAN_REQUESTS.md](POSTMAN_REQUESTS.md)
- System Guide: [DUES_PAYMENT_SYSTEM.md](DUES_PAYMENT_SYSTEM.md)
- Laravel Docs: https://laravel.com/docs
- Postman Docs: https://learning.postman.com/
