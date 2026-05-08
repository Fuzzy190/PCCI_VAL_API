# Admin Notifications - Diagnostic & Setup Guide

## Issues Found & Fixed

### 1. ✅ Status Mismatch (FIXED)

**Problem:** I created notification conditions for `admin_approved` and `treasurer_approved` statuses that don't exist in your workflow.

**Fix:** Updated ApplicantController to use your actual statuses:

- `pending` → (no notification)
- `approved` → **Treasurers notified** (review payment)
- `paid` → **Admins notified** (ready for member creation)
- `rejected` → Admins notified
- `cancelled` → Admins notified

### 2. ⚠️ Frontend Not Polling Notifications (REQUIRES FRONTEND WORK)

**Problem:** The admin frontend isn't calling `/v1/notifications` endpoint to fetch notifications.

**Solution:** Frontend needs to poll this endpoint periodically.

---

## How to Verify Notifications Work

### Step 1: Test the endpoint

```bash
# Using Postman or curl - as authenticated admin
GET http://localhost:8000/api/v1/test-notif
```

**Expected response:**

```json
{
  "message": "Test notification sent!",
  "sent_to": "admin@example.com",
  "role": ["admin"],
  "check_endpoint": "/api/v1/notifications"
}
```

### Step 2: Fetch notifications

```bash
# Using the token from Step 1's auth
GET http://localhost:8000/api/v1/notifications

# Response should show:
{
  "unread_count": 1,
  "notifications": [
    {
      "id": "uuid",
      "type": "App\\Notifications\\SystemAlertNotification",
      "notifiable_id": 1,
      "data": {
        "title": "Connection Success!",
        "message": "The backend notification was saved to the database...",
        "icon": "fa-rocket",
        "tone": "text-primary"
      },
      "read_at": null,
      "created_at": "2026-05-08T10:30:00Z"
    }
  ]
}
```

### Step 3: Test with new applicant

1. Create a new applicant via `/api/v1/apply` (POST)
2. As admin, fetch notifications: `GET /v1/notifications`
3. Should see: **"A new applicant has been submitted..."** notification

### Step 4: Test with approval flow

1. Update applicant to `approved` status (PUT `/v1/applicants/{id}`)
2. Treasurers should get notification (check `/v1/notifications` as treasurer)
3. Update applicant to `paid` status (PUT `/v1/applicants/{id}`)
4. Admins should get notification (check `/v1/notifications` as admin)

---

## Admin Frontend - Implementation Guide

### Required Frontend Changes

Add this to your admin layout/dashboard to poll notifications:

```javascript
// In your admin.blade.php or main admin JS file

// Poll notifications every 5 seconds
setInterval(async function () {
  const token = localStorage.getItem("auth_token"); // Your auth token key

  try {
    const response = await fetch("/api/v1/notifications", {
      headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "application/json",
      },
    });

    const data = await response.json();

    if (data.unread_count > 0) {
      // Update notification badge
      document.getElementById("notif-badge").textContent = data.unread_count;
      document.getElementById("notif-badge").style.display = "block";

      // Display notifications in dropdown/list
      displayNotifications(data.notifications);
    }
  } catch (error) {
    console.error("Failed to fetch notifications:", error);
  }
}, 5000); // 5 seconds

function displayNotifications(notifications) {
  const notifContainer = document.getElementById("notifications-list");
  notifContainer.innerHTML = "";

  notifications.forEach((notif) => {
    const data = notif.data;
    const notifEl = document.createElement("div");
    notifEl.className =
      "notification-item " + (notif.read_at ? "read" : "unread");
    notifEl.innerHTML = `
            <div class="notification-title">${data.title}</div>
            <div class="notification-message">${data.message}</div>
            <div class="notification-time">${new Date(notif.created_at).toLocaleString()}</div>
        `;
    notifContainer.appendChild(notifEl);
  });
}
```

### Endpoints for Frontend

| Method | Endpoint                          | Purpose                                  |
| ------ | --------------------------------- | ---------------------------------------- |
| GET    | `/api/v1/notifications`           | Fetch all notifications for current user |
| PATCH  | `/api/v1/notifications/{id}/read` | Mark single notification as read         |
| POST   | `/api/v1/notifications/read-all`  | Mark all as read                         |
| DELETE | `/api/v1/notifications/clear`     | Clear all notifications                  |

---

## New Notifications Created

### 1. NewApplicantNotification

- **When:** New applicant is created
- **Sent to:** All admins
- **Channel:** Database only
- **Data:**
  ```json
  {
    "title": "New Applicant Submitted",
    "message": "A new applicant has been submitted: [Business Name]",
    "applicant_id": 1,
    "business_name": "ACME Corp"
  }
  ```

### 2. RenewalRequestSubmittedNotification

- **When:** Member submits renewal payment proof
- **Sent to:** All treasurers
- **Channels:** Mail + Database
- **Email:** `renewal_request_submitted.blade.php`

### 3. RenewalApprovedNotification

- **When:** Treasurer approves renewal payment
- **Sent to:** The member
- **Channels:** Mail + Database
- **Email:** `renewal_approved.blade.php`

### 4. RenewalRejectedNotification

- **When:** Treasurer rejects renewal payment
- **Sent to:** The member
- **Channels:** Mail + Database
- **Email:** `renewal_rejected.blade.php`

---

## Troubleshooting

### Notifications not appearing?

1. **Check database:**

   ```sql
   SELECT * FROM notifications WHERE notifiable_id = 1;
   ```

2. **Check logs:**

   ```bash
   tail -f storage/logs/laravel.log
   ```

3. **Verify user has Notifiable trait:**
   - ✅ Already confirmed in User model

4. **Verify migrations ran:**

   ```bash
   php artisan migrate
   ```

5. **Test endpoint:**
   ```bash
   php artisan tinker
   >>> $user = \App\Models\User::role('admin')->first();
   >>> $user->notify(new \App\Notifications\SystemAlertNotification('Test', 'Test message', 'fa-bell', 'text-info'));
   >>> $user->notifications()->count(); // Should return 1+
   ```

---

## Summary

✅ **Fixed:** Status mismatch in ApplicantController notifications
⚠️ **Required:** Frontend implementation to poll `/api/v1/notifications`
✅ **Added:** Test endpoint to verify notifications work
✅ **Created:** 4 new notification classes
✅ **Created:** 3 new email templates
