# Postman Requests - Membership Dues API

## Base URL
```
http://localhost:8000/api
```

## Authentication
All requests require:
```
Header: Authorization: Bearer {YOUR_SANCTUM_TOKEN}
```

---

## 1. CREATE MEMBERSHIP DUE

**Endpoint:** `POST /v1/membership-dues`

**Authorization:** Super Admin or Treasurer

**Body (JSON):**
```json
{
  "member_id": 1,
  "amount": 5000.00,
  "due_year": 2024,
  "due_date": "2024-01-31",
  "status": "pending",
  "notes": "Annual membership dues for 2024"
}
```

**Response (201 Created):**
```json
{
  "data": {
    "id": 1,
    "member_id": 1,
    "amount": "5000.00",
    "due_year": 2024,
    "due_date": "2024-01-31",
    "paid_date": null,
    "status": "pending",
    "notes": "Annual membership dues for 2024",
    "first_warning_sent_at": null,
    "second_warning_sent_at": null,
    "final_warning_sent_at": null,
    "created_at": "2024-03-31T10:30:00Z",
    "updated_at": "2024-03-31T10:30:00Z"
  }
}
```

---

## 2. GET ALL MEMBERSHIP DUES

**Endpoint:** `GET /v1/membership-dues`

**Authorization:** Super Admin or Treasurer

**Query Parameters (Optional):**
- `member_id` - Filter by member
- `due_year` - Filter by year
- `status` - Filter by status (pending, paid, overdue, waived, expired)
- `page` - Pagination (default: 1)
- `per_page` - Items per page (default: 20)

**Example:**
```
GET /v1/membership-dues?member_id=1&status=pending
or
GET /v1/membership-dues?due_year=2024&page=1
```

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "member_id": 1,
      "amount": "5000.00",
      "due_year": 2024,
      "due_date": "2024-01-31",
      "paid_date": null,
      "status": "pending",
      "notes": "Annual membership dues for 2024",
      "first_warning_sent_at": null,
      "second_warning_sent_at": null,
      "final_warning_sent_at": null,
      "payments_count": 0,
      "created_at": "2024-03-31T10:30:00Z",
      "updated_at": "2024-03-31T10:30:00Z"
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/v1/membership-dues?page=1",
    "last": "http://localhost:8000/api/v1/membership-dues?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "path": "http://localhost:8000/api/v1/membership-dues",
    "per_page": 20,
    "to": 1,
    "total": 1
  }
}
```

---

## 3. GET PENDING DUES

**Endpoint:** `GET /v1/membership-dues/pending`

**Authorization:** Super Admin or Treasurer

**Response (200 OK):**
Same format as listing all dues, but filtered for `status: pending`

---

## 4. GET OVERDUE DUES

**Endpoint:** `GET /v1/membership-dues/overdue`

**Authorization:** Super Admin or Treasurer

**Response (200 OK):**
Same format as listing all dues, but filtered for `status: overdue`

---

## 5. GET SINGLE MEMBERSHIP DUE

**Endpoint:** `GET /v1/membership-dues/{id}`

**Authorization:** Super Admin or Treasurer

**Example:**
```
GET /v1/membership-dues/1
```

**Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "member_id": 1,
    "member": {
      "id": 1,
      "applicant_id": 1,
      "user_id": null,
      "membership_type_id": 1,
      "induction_date": "2023-07-17",
      "membership_end_date": "2024-07-17",
      "status": "approved"
    },
    "amount": "5000.00",
    "due_year": 2024,
    "due_date": "2024-01-31",
    "paid_date": null,
    "status": "pending",
    "notes": "Annual membership dues for 2024",
    "first_warning_sent_at": null,
    "second_warning_sent_at": null,
    "final_warning_sent_at": null,
    "payments": [],
    "created_at": "2024-03-31T10:30:00Z",
    "updated_at": "2024-03-31T10:30:00Z"
  }
}
```

---

## 6. UPDATE MEMBERSHIP DUE

**Endpoint:** `PUT /v1/membership-dues/{id}`

**Authorization:** Super Admin only

**Body (JSON):**
```json
{
  "status": "paid",
  "notes": "Payment received on 2024-03-15"
}
```

**Response (200 OK):**
Same as getting single due with updated values

---

## 7. GET DUES STATISTICS

**Endpoint:** `GET /v1/membership-dues/stats`

**Authorization:** Super Admin or Treasurer

**Response (200 OK):**
```json
{
  "total_pending": 15,
  "total_paid": 45,
  "total_overdue": 8,
  "total_waived": 2,
  "total_amount_pending": 75000.00,
  "total_amount_paid": 225000.00,
  "total_amount_overdue": 40000.00
}
```

---

## 8. GET MEMBER'S UNPAID DUES

**Endpoint:** `GET /v1/members/{member_id}/unpaid-dues`

**Authorization:** Super Admin or Treasurer

**Example:**
```
GET /v1/members/1/unpaid-dues
```

**Response (200 OK):**
Shows all unpaid dues for the member (2023, 2024, 2025, etc)
```json
{
  "data": [
    {
      "id": 1,
      "member_id": 1,
      "amount": "5000.00",
      "due_year": 2023,
      "status": "pending",
      ...
    },
    {
      "id": 2,
      "member_id": 1,
      "amount": "5000.00",
      "due_year": 2024,
      "status": "pending",
      ...
    }
  ]
}
```

---

## 9. RECORD DUES PAYMENT ⚠️ KEY ENDPOINT

**Endpoint:** `POST /v1/dues-payments`

**Authorization:** Super Admin or Treasurer

**Important Rule:** Only the payment year counts. If member paid on 2024-03-15, only 2024 due is marked as PAID.

**Body (JSON):**
```json
{
  "membership_due_id": 1,
  "or_number": "OR-2024-0001",
  "amount": 5000.00,
  "payment_date": "2024-03-15",
  "payment_method": "cash",
  "reference_number": null,
  "notes": "Payment received from member - cash"
}
```

**Response (201 Created):**
```json
{
  "data": {
    "id": 1,
    "membership_due_id": 1,
    "member_id": 1,
    "received_by_user_id": 5,
    "or_number": "OR-2024-0001",
    "amount": "5000.00",
    "payment_date": "2024-03-15",
    "payment_method": "cash",
    "reference_number": null,
    "notes": "Payment received from member - cash",
    "created_at": "2024-03-31T10:35:00Z",
    "updated_at": "2024-03-31T10:35:00Z"
  }
}
```

**Error Response if year doesn't match:**
```json
{
  "message": "Payment validation failed",
  "errors": [
    "Payment year 2023 does not match due year 2024"
  ]
}
```

---

## 10. GET ALL DUES PAYMENTS

**Endpoint:** `GET /v1/dues-payments`

**Authorization:** Super Admin or Treasurer

**Query Parameters (Optional):**
- `membership_due_id` - Filter by specific due
- `member_id` - Filter by member
- `year` - Filter by payment year (e.g., ?year=2024)
- `page` - Pagination

**Example:**
```
GET /v1/dues-payments?member_id=1&year=2024
or
GET /v1/dues-payments?membership_due_id=1
```

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "membership_due_id": 1,
      "member_id": 1,
      "received_by_user_id": 5,
      "or_number": "OR-2024-0001",
      "amount": "5000.00",
      "payment_date": "2024-03-15",
      "payment_method": "cash",
      "reference_number": null,
      "notes": "Payment received from member - cash",
      "created_at": "2024-03-31T10:35:00Z",
      "updated_at": "2024-03-31T10:35:00Z"
    }
  ],
  "meta": { ... }
}
```

---

## 11. GET SINGLE DUES PAYMENT

**Endpoint:** `GET /v1/dues-payments/{id}`

**Authorization:** Super Admin or Treasurer

**Example:**
```
GET /v1/dues-payments/1
```

**Response (200 OK):**
Full payment details with relationships

---

## 12. GET COLLECTION BY YEAR

**Endpoint:** `GET /v1/dues-payments/by-year`

**Authorization:** Super Admin or Treasurer

**Query Parameters:**
- `year` - Year to get collections for (default: current year)

**Example:**
```
GET /v1/dues-payments/by-year?year=2024
```

**Response (200 OK):**
```json
{
  "year": 2024,
  "total_collected": 125000.00,
  "payments": [
    { ... },
    { ... }
  ],
  "meta": { ... }
}
```

---

## 13. GET TREASURER'S PAYMENTS

**Endpoint:** `GET /v1/dues-payments/treasurer-payments`

**Authorization:** 
- Super Admin (can view any treasurer's payments)
- Treasurer (can only view their own)

**Query Parameters:**
- `user_id` - For admin to filter by specific treasurer

**Example:**
```
GET /v1/dues-payments/treasurer-payments
or (Admin only)
GET /v1/dues-payments/treasurer-payments?user_id=5
```

**Response (200 OK):**
Payments recorded by the specified treasurer

---

## 14. GET DUES PAYMENTS STATISTICS

**Endpoint:** `GET /v1/dues-payments/stats`

**Authorization:** Super Admin or Treasurer

**Query Parameters:**
- `year` - Year for statistics (default: current year)

**Example:**
```
GET /v1/dues-payments/stats?year=2024
```

**Response (200 OK):**
```json
{
  "year": 2024,
  "total_collected": 125000.00,
  "total_payments": 25,
  "by_payment_method": [
    {
      "payment_method": "cash",
      "count": 15,
      "total": "75000.00"
    },
    {
      "payment_method": "bank_transfer",
      "count": 8,
      "total": "40000.00"
    },
    {
      "payment_method": "check",
      "count": 2,
      "total": "10000.00"
    }
  ]
}
```

---

## 15. GET PAYMENTS FOR A SPECIFIC DUE

**Endpoint:** `GET /v1/membership-dues/{membership_due_id}/payments`

**Authorization:** Super Admin or Treasurer

**Example:**
```
GET /v1/membership-dues/1/payments
```

**Response (200 OK):**
All payments recorded for that specific due

---

## Error Responses

### 403 - Unauthorized
```json
{
  "message": "Unauthorized access"
}
```

### 422 - Validation Failed
```json
{
  "message": "Payment validation failed",
  "errors": [
    "Payment year 2025 does not match due year 2024",
    "This OR number already exists"
  ]
}
```

### 409 - Conflict (Due already exists)
```json
{
  "message": "Due already exists for this member in 2024"
}
```

### 404 - Not Found
```json
{
  "message": "Not found"
}
```

---

## Payment Method Options

Valid values for `payment_method`:
- `cash`
- `check`
- `bank_transfer`
- `online`
- `mobile_money`

---

## Status Options

Valid values for `status`:
- `pending` - Due is pending payment
- `paid` - Due has been paid
- `overdue` - Payment is past due date
- `waived` - Payment was waived
- `expired` - Membership expired

---

## Postman Tips

1. **Create Environment Variables:**
   - `base_url` = http://localhost:8000/api
   - `token` = Your Sanctum token
   - `member_id` = 1
   - `due_id` = 1
   - `user_id` = 5

2. **Use Variables in Requests:**
   - URL: `{{base_url}}/v1/membership-dues/{{due_id}}`
   - Header: `Bearer {{token}}`
   - Body: `"member_id": {{member_id}}`

3. **Collection Organization:**
   - Create folders for each resource (Membership Dues, Dues Payments)
   - Create sub-folders for (GET, POST, PUT, DELETE)

4. **Pre-request Scripts:**
   - Automatically set variables based on responses
   - Add timestamps to OR numbers

5. **Tests:**
   - Verify status codes
   - Check response structure
   - Validate data types
