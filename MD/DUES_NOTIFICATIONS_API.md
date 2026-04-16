# Dues Notification System API Documentation

## Overview

The Dues Notification System provides in-app notifications for members about their membership dues. Members receive notifications at key points:
- First Warning (5 months before expiration)
- Second Warning (3 months before expiration)  
- Final Warning (1 month before expiration)
- Expired (after expiration date)
- Payment Received (when dues are paid)

## Base URL
```
/api/v1
```

## Authentication
All notification endpoints require authentication with `auth:sanctum` middleware.

## Endpoints

### 1. Get Member's All Notifications
**GET** `/members/{memberId}/notifications`

Returns paginated list of notifications for a member (newest first).

**Parameters:**
- `memberId` (path, required): Member ID

**Query Parameters:**
- `page` (optional): Page number (default: 1)

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "type": "first_warning",
      "title": "Membership Renewal Notice",
      "message": "Your PCCI membership is expiring in approximately 5 months (July 15, 2026)...",
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
  ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "per_page": 20,
    "to": 1,
    "total": 1
  }
}
```

**Authorization:**
- Members can view their own notifications
- Admins/Super Admins can view any member's notifications

**Example:**
```bash
curl -H "Authorization: Bearer TOKEN" \
  "http://api.pcci.local/api/v1/members/5/notifications"
```

---

### 2. Get Member's Unread Notifications
**GET** `/members/{memberId}/notifications/unread`

Returns only unread notifications for a member.

**Parameters:**
- `memberId` (path, required): Member ID

**Response (200 OK):**
Same as above but filtered to `is_read: false` only.

**Example:**
```bash
curl -H "Authorization: Bearer TOKEN" \
  "http://api.pcci.local/api/v1/members/5/notifications/unread"
```

---

### 3. Filter Notifications by Type
**GET** `/members/{memberId}/notifications/by-type?type=warning`

Returns notifications filtered by type.

**Parameters:**
- `memberId` (path, required): Member ID
- `type` (query, required): Notification type
  - `first_warning`
  - `second_warning`
  - `final_warning`
  - `expired`
  - `payment_received`

**Response (200 OK):**
Same structure as endpoint #1.

**Example:**
```bash
curl -H "Authorization: Bearer TOKEN" \
  "http://api.pcci.local/api/v1/members/5/notifications/by-type?type=first_warning"
```

---

### 4. Get Notification Statistics
**GET** `/members/{memberId}/notifications/stats`

Returns notification count statistics for a member.

**Parameters:**
- `memberId` (path, required): Member ID

**Response (200 OK):**
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

**Example:**
```bash
curl -H "Authorization: Bearer TOKEN" \
  "http://api.pcci.local/api/v1/members/5/notifications/stats"
```

---

### 5. Mark Single Notification as Read
**PUT** `/notifications/{notificationId}/mark-as-read`

Marks a single notification as read and records read timestamp.

**Parameters:**
- `notificationId` (path, required): Notification ID

**Request Body:**
Empty body required.

**Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "type": "first_warning",
    "title": "Membership Renewal Notice",
    "message": "Your PCCI membership is expiring...",
    "data": null,
    "is_read": true,
    "read_at": "2024-03-31 10:35:00",
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
}
```

**Authorization:**
- Members can mark their own notifications
- Admins/Super Admins can mark any notification

**Example:**
```bash
curl -X PUT -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{}' \
  "http://api.pcci.local/api/v1/notifications/1/mark-as-read"
```

---

### 6. Mark Single Notification as Unread
**PUT** `/notifications/{notificationId}/mark-as-unread`

Marks a single notification as unread and clears read timestamp.

**Parameters:**
- `notificationId` (path, required): Notification ID

**Response (200 OK):**
Same as endpoint #5 but with `is_read: false` and `read_at: null`.

**Example:**
```bash
curl -X PUT -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{}' \
  "http://api.pcci.local/api/v1/notifications/1/mark-as-unread"
```

---

### 7. Mark All Notifications as Read
**PUT** `/members/{memberId}/notifications/mark-all-read`

Marks all unread notifications for a member as read.

**Parameters:**
- `memberId` (path, required): Member ID

**Request Body:**
Empty body required.

**Response (200 OK):**
Returns paginated list of all notifications (same as endpoint #1).

**Example:**
```bash
curl -X PUT -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{}' \
  "http://api.pcci.local/api/v1/members/5/notifications/mark-all-read"
```

---

### 8. Delete Notification
**DELETE** `/notifications/{notificationId}`

Permanently deletes a notification.

**Parameters:**
- `notificationId` (path, required): Notification ID

**Response (200 OK):**
```json
{
  "message": "Notification deleted successfully"
}
```

**Authorization:**
- Members can delete their own notifications
- Admins/Super Admins can delete any notification

**Example:**
```bash
curl -X DELETE -H "Authorization: Bearer TOKEN" \
  "http://api.pcci.local/api/v1/notifications/1"
```

---

## Notification Types

### first_warning
- **When:** ~5 months (150 days) before membership expiration
- **Title:** "Membership Renewal Notice"
- **Purpose:** Initial reminder to renew membership

### second_warning
- **When:** ~3 months (90 days) before membership expiration
- **Title:** "Membership Renewal Reminder"
- **Purpose:** Second reminder with more urgency

### final_warning
- **When:** ~1 month (30 days) before membership expiration
- **Title:** "URGENT: Membership Expiring Soon"
- **Purpose:** Final warning before expiration

### expired
- **When:** After membership expiration date
- **Title:** "Membership Expired"
- **Purpose:** Notify member that membership is now expired

### payment_received
- **When:** When dues payment is recorded
- **Title:** "Payment Received"
- **Message:** Includes amount paid and year
- **Purpose:** Confirmation of payment

---

## Error Responses

### 403 Forbidden
```json
{
  "message": "Unauthorized"
}
```
User does not have permission to view/modify this notification.

### 404 Not Found
```json
{
  "message": "No query results found for model [App\\Models\\DuesNotification]."
}
```
Notification or Member not found.

### 422 Unprocessable Entity
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "type": [
      "The type field is required."
    ]
  }
}
```
Validation error on query parameters.

---

## Postman Collection Example

### Import Collection
A Postman collection is available at:
- `PCCI COLLECTION JSON/LOCAL [Role] - Dues API.postman_collection.json`

### Example Requests

**Get Unread Notifications:**
```
GET {{base_url}}/api/v1/members/{{member_id}}/notifications/unread
Authorization: Bearer {{access_token}}
```

**Mark All as Read:**
```
PUT {{base_url}}/api/v1/members/{{member_id}}/notifications/mark-all-read
Authorization: Bearer {{access_token}}
Content-Type: application/json

{}
```

**Get Statistics:**
```
GET {{base_url}}/api/v1/members/{{member_id}}/notifications/stats
Authorization: Bearer {{access_token}}
```

---

## Database Schema

**Table:** `dues_notifications`

| Field | Type | Description |
|-------|------|-------------|
| id | BIGINT | Primary key |
| member_id | BIGINT FK | Reference to members table |
| membership_due_id | BIGINT FK | Reference to membership_dues table |
| type | VARCHAR(50) | Notification type (first_warning, etc.) |
| title | VARCHAR(255) | Notification title |
| message | TEXT | Notification message |
| data | JSON | Additional JSON data (optional) |
| is_read | BOOLEAN | Whether notification has been read |
| read_at | TIMESTAMP | When notification was marked as read |
| created_at | TIMESTAMP | When notification was created |
| updated_at | TIMESTAMP | When notification was last updated |

**Relationships:**
- Belongs to `members` table via `member_id`
- Belongs to `membership_dues` table via `membership_due_id`
- Cascades on delete from both parent tables

---

## Implementation Notes

### How Notifications Are Created

1. **Warning Checks:** When `MembershipDue::checkAndSendExpirationWarnings()` is called (usually via Observer), it compares current date to membership expiration date.

2. **Notification Trigger:** If the due crosses a warning threshold (150, 90, or 30 days), a DuesNotification record is created with:
   - Type (first_warning, second_warning, final_warning, or expired)
   - Member and Membership Due relationships
   - Title and message specific to the warning

3. **Payment Notifications:** When a payment is recorded via `DuesPayment::create()`, the associated `MembershipDue::markAsPaid()` is called, which creates a `payment_received` notification.

### Future: Email Notifications

Email classes are provided but set aside for later:
- `MembershipDuesFirstWarningEmail`
- `MembershipDuesSecondWarningEmail`
- `MembershipDuesFinalWarningEmail`
- `DuesPaymentReceivedEmail`

To enable emails, uncomment the `notify()` calls in the MembershipDue model warning methods.

---

## Testing

### Manual Testing Steps

1. **Create a membership due** expiring in the future
2. **Trigger warning checks** via observer or manual call
3. **Verify notifications created** in database
4. **API call to list notifications**
5. **Mark as read** and verify timestamp
6. **Check statistics** endpoint

### Test Scenarios

- Member with no notifications → empty collection
- Member with mixed read/unread → statistics correct
- Filter by type → only matching notifications returned
- Delete notification → verify cascading relationships

---

## Related Resources

- [Membership Dues System Documentation](DUES_PAYMENT_SYSTEM.md)
- [MembershipDue Model](app/Models/MembershipDue.php)
- [DuesNotification Model](app/Models/DuesNotification.php)
- [MemberNotificationController](app/Http/Controllers/Api/V1/MemberNotificationController.php)
