# Postman Collections - Notification Endpoints Integration Guide

## Overview
This guide shows how to add the new notification endpoints to your existing Postman collections.

## Collections to Update
- LOCAL Treasurer - Dues API.postman_collection.json
- LOCAL Admin - Dues API.postman_collection.json
- LOCAL Super Admin - Dues API.postman_collection.json

---

## New Notification Endpoints

### 1. List Member Notifications

**Method:** GET  
**URL:** `{{base_url}}/api/v1/members/{{member_id}}/notifications`  
**Auth:** Bearer Token  

**Tests Tab:**
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response contains data array", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('data');
    pm.expect(jsonData.data).to.be.an('array');
});

pm.test("Each notification has required fields", function () {
    var jsonData = pm.response.json();
    jsonData.data.forEach(function(notification) {
        pm.expect(notification).to.have.property('id');
        pm.expect(notification).to.have.property('type');
        pm.expect(notification).to.have.property('title');
        pm.expect(notification).to.have.property('message');
        pm.expect(notification).to.have.property('is_read');
    });
});
```

---

### 2. Get Unread Notifications

**Method:** GET  
**URL:** `{{base_url}}/api/v1/members/{{member_id}}/notifications/unread`  

**Tests Tab:**
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("All notifications are unread", function () {
    var jsonData = pm.response.json();
    jsonData.data.forEach(function(notification) {
        pm.expect(notification.is_read).to.equal(false);
    });
});

pm.test("Stats show unread count", function () {
    var jsonData = pm.response.json();
    pm.environment.set("unread_notification_count", jsonData.meta.total);
});
```

---

### 3. Filter Notifications by Type

**Method:** GET  
**URL:** `{{base_url}}/api/v1/members/{{member_id}}/notifications/by-type?type=first_warning`  

**Query Params:**
| Key | Value |
|-----|-------|
| type | first_warning \| second_warning \| final_warning \| expired \| payment_received |

**Tests Tab:**
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("All notifications match filter type", function () {
    var jsonData = pm.response.json();
    var requestType = pm.request.url.query.get("type");
    jsonData.data.forEach(function(notification) {
        pm.expect(notification.type).to.equal(requestType);
    });
});
```

---

### 4. Get Notification Statistics

**Method:** GET  
**URL:** `{{base_url}}/api/v1/members/{{member_id}}/notifications/stats`  

**Tests Tab:**
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Stats object has required properties", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('total');
    pm.expect(jsonData).to.have.property('unread');
    pm.expect(jsonData).to.have.property('by_type');
});

pm.test("Stats are numeric", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.total).to.be.a('number');
    pm.expect(jsonData.unread).to.be.a('number');
});
```

---

### 5. Mark Notification as Read

**Method:** PUT  
**URL:** `{{base_url}}/api/v1/notifications/{{notification_id}}/mark-as-read`  

**Headers:**
| Key | Value |
|-----|-------|
| Content-Type | application/json |

**Body (raw JSON):**
```json
{}
```

**Tests Tab:**
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Notification is marked as read", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.data.is_read).to.equal(true);
    pm.expect(jsonData.data.read_at).to.not.be.null;
});
```

---

### 6. Mark Notification as Unread

**Method:** PUT  
**URL:** `{{base_url}}/api/v1/notifications/{{notification_id}}/mark-as-unread`  

**Headers:**
| Key | Value |
|-----|-------|
| Content-Type | application/json |

**Body (raw JSON):**
```json
{}
```

**Tests Tab:**
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Notification is marked as unread", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.data.is_read).to.equal(false);
    pm.expect(jsonData.data.read_at).to.be.null;
});
```

---

### 7. Mark All Notifications as Read

**Method:** PUT  
**URL:** `{{base_url}}/api/v1/members/{{member_id}}/notifications/mark-all-read`  

**Headers:**
| Key | Value |
|-----|-------|
| Content-Type | application/json |

**Body (raw JSON):**
```json
{}
```

**Tests Tab:**
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("All returned notifications are read", function () {
    var jsonData = pm.response.json();
    jsonData.data.forEach(function(notification) {
        pm.expect(notification.is_read).to.equal(true);
    });
});
```

---

### 8. Delete Notification

**Method:** DELETE  
**URL:** `{{base_url}}/api/v1/notifications/{{notification_id}}`  

**Tests Tab:**
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response contains success message", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.message).to.include("deleted");
});
```

---

## Environment Variables

Add these to your Postman environment (if not already present):

| Variable | Example | Description |
|----------|---------|-------------|
| base_url | http://api.pcci.local | API base URL |
| access_token | your_bearer_token | Sanctum bearer token |
| member_id | 5 | Member ID for testing |
| notification_id | 1 | Notification ID for testing |

---

## Collection Structure

Add a new folder to each collection called "Notifications" with these subfolders:

```
Notifications/
├── List Member Notifications
├── View/Filter/
│   ├── Get Unread Notifications
│   ├── Filter by Type (first_warning)
│   ├── Filter by Type (second_warning)
│   ├── Filter by Type (final_warning)
│   ├── Filter by Type (expired)
│   ├── Filter by Type (payment_received)
│   └── Get Statistics
└── Update/Delete/
    ├── Mark as Read
    ├── Mark as Unread
    ├── Mark All as Read
    └── Delete Notification
```

---

## Testing Workflow

### Pre-requisites
1. Ensure member has at least one membership due
2. Manually trigger warning checks or wait for automatic trigger
3. Have notification IDs available

### Test Sequence
1. **Get Unread Notifications** - See pending notifications
2. **Get Statistics** - Verify counts
3. **Filter by Type** - Get specific notification type
4. **Mark as Read** - Single notification
5. **Get Unread Notifications** - Verify count decreased
6. **Mark All as Read** - All at once
7. **Delete Notification** - Clean up

---

## Sample Test Data Setup

Run in Postman pre-request script:

```javascript
// Generate test member ID if not set
if (!pm.environment.get("member_id")) {
    pm.environment.set("member_id", 1);
}

// Generate timestamp for logging
pm.environment.set("test_run", new Date().toISOString());

// Log test run
console.log("Starting notification tests at: " + pm.environment.get("test_run"));
```

---

## Error Handling Tests

### Unauthorized Access (Non-Admin Member)

**Expected:** 403 Forbidden

```javascript
pm.test("Non-admin cannot access other member's notifications", function () {
    pm.response.to.have.status(403);
    var jsonData = pm.response.json();
    pm.expect(jsonData.message).to.include("Unauthorized");
});
```

### Invalid Notification ID

**Expected:** 404 Not Found

```javascript
pm.test("Invalid notification ID returns 404", function () {
    pm.response.to.have.status(404);
});
```

### Missing Query Parameters

**Expected:** 422 Unprocessable Entity

```javascript
pm.test("Missing type parameter returns validation error", function () {
    pm.response.to.have.status(422);
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('errors');
    pm.expect(jsonData.errors).to.have.property('type');
});
```

---

## Role-Based Testing

### Treasurer Role
- ✅ Can view own member's notifications
- ✅ Can mark own member's notifications
- ❌ Cannot view other members' notifications (unless own)
- ❌ Cannot delete others' notifications

### Admin Role
- ✅ Can view any member's notifications
- ✅ Can mark any member's notifications
- ✅ Can delete any notification
- ✅ Can see all statistics

### Super Admin Role
- ✅ Same as Admin (full access)

---

## Performance Testing

### Load Test: Get All Notifications
```
URL: {{base_url}}/api/v1/members/{{member_id}}/notifications
Expected Response Time: < 500ms
Expected Dataset: Up to 1000 notifications
```

### Load Test: Mark All as Read
```
URL: {{base_url}}/api/v1/members/{{member_id}}/notifications/mark-all-read
Expected Response Time: < 1000ms
Expected Updates: Up to 1000 notifications
```

---

## Integration with Other Tests

### After Payment Test
```javascript
// In post-test of payment endpoint
pm.test("Payment created payment_received notification", function () {
    var paymentResponse = pm.response.json();
    var notificationUrl = pm.environment.get("base_url") + 
        "/api/v1/members/" + paymentResponse.data.member.id + 
        "/notifications/by-type?type=payment_received";
    
    // Verify notification exists
    pm.environment.set("verify_notification_url", notificationUrl);
});
```

### Before Membership Expiration Test
```javascript
// In pre-test of expiration check
pm.test("Warning notifications exist", function () {
    var warningTypes = ['first_warning', 'second_warning', 'final_warning'];
    warningTypes.forEach(function(type) {
        // Verify notification for each warning type
    });
});
```

---

## Export/Import Instructions

### To Share Collection
1. In Postman, click collection name → Export
2. Select JSON format
3. Save and share file

### To Import New Endpoints
1. Get the new collection JSON
2. In Postman, click Import
3. Select the JSON file
4. Select workspace and import

---

## Troubleshooting

### Notifications not appearing
- Check member_id is correct
- Verify membership due exists
- Trigger warning checks manually
- Check database dues_notifications table

### Cannot access endpoints
- Verify bearer token is valid
- Check user role (admin required for other members)
- Ensure auth:sanctum middleware is working

### Unexpected response format
- Verify API version matches (/v1/)
- Check parameter names (notificationId not id)
- Verify request method (PUT not POST for mark-as-read)

---

## Quick Copy-Paste

### All Endpoints (for quick reference)
```
GET    /api/v1/members/{{member_id}}/notifications
GET    /api/v1/members/{{member_id}}/notifications/unread
GET    /api/v1/members/{{member_id}}/notifications/by-type?type=first_warning
GET    /api/v1/members/{{member_id}}/notifications/stats
PUT    /api/v1/notifications/{{notification_id}}/mark-as-read
PUT    /api/v1/notifications/{{notification_id}}/mark-as-unread
PUT    /api/v1/members/{{member_id}}/notifications/mark-all-read
DELETE /api/v1/notifications/{{notification_id}}
```

---

**Last Updated:** March 31, 2024  
**Postman Version:** v9.0 or later  
**API Version:** v1
