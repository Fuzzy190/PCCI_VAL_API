# Deployment & Validation Checklist

## Pre-Deployment Validation

### ✅ Database Layer
- [ ] All 3 migration files exist in `database/migrations/`
  - [ ] `2026_03_26_100000_create_membership_dues_table.php`
  - [ ] `2026_03_26_100001_create_dues_payments_table.php`
  - [ ] `2026_03_31_100000_create_dues_notifications_table.php`
- [ ] Migrations run without errors: `php artisan migrate`
- [ ] Tables created with correct schema:
  - [ ] `membership_dues` table has 12 columns
  - [ ] `dues_payments` table has 10 columns
  - [ ] `dues_notifications` table has 11 columns
- [ ] Foreign key relationships established
- [ ] Indexes created on foreign keys
- [ ] Unique constraints applied

### ✅ Model Layer
- [ ] MembershipDue model exists and is complete
  - [ ] Has relationships: member, payments, notifications
  - [ ] Has methods: checkAndSendExpirationWarnings, sendFirstWarning, etc.
  - [ ] Has scopes: getOverdueDues, getPendingDues
- [ ] DuesPayment model exists and is complete
  - [ ] Has relationships: membershipDue, member, receivedBy (user)
  - [ ] Has methods: recordPayment, validatePayment
  - [ ] Has scopes for filtering
- [ ] DuesNotification model exists and is complete
  - [ ] Has relationships: member, membershipDue
  - [ ] Has methods: markAsRead, markAsUnread
  - [ ] Has scopes: unread, read, byType
- [ ] Observer registered in AppServiceProvider
  - [ ] MembershipDueObserver boots correctly
  - [ ] Observes retrieved, created, saved events

### ✅ API Layer - Controllers
- [ ] MembershipDueController exists with 8+ methods
- [ ] DuesPaymentController exists with 7+ methods
- [ ] MemberNotificationController exists with 8 methods
- [ ] All controllers extend Controller base class
- [ ] Authorization checks in place for all endpoints

### ✅ API Layer - Requests & Resources
- [ ] StoreMembershipDueRequest validates all fields
- [ ] StoreDuesPaymentRequest validates payment data
- [ ] MembershipDueResource formats response correctly
- [ ] DuesPaymentResource formats response correctly
- [ ] DuesNotificationResource formats response correctly

### ✅ Routes
- [ ] All controllers imported in routes/api.php
- [ ] Notification routes registered with correct middleware
- [ ] Route parameters match controller method signatures
- [ ] Routes follow /api/v1 versioning convention
- [ ] Role-based middleware applied correctly

### ✅ Email Notifications
- [ ] 4 email notification classes exist (set aside)
  - [ ] MembershipDuesFirstWarningEmail.php
  - [ ] MembershipDuesSecondWarningEmail.php
  - [ ] MembershipDuesFinalWarningEmail.php
  - [ ] DuesPaymentReceivedEmail.php
- [ ] Email classes have placeholder structure for future use
- [ ] Comments note that email is "SET ASIDE" for later

### ✅ Documentation
- [ ] DUES_PAYMENT_SYSTEM.md created and complete
- [ ] DUES_NOTIFICATIONS_API.md created with all endpoints
- [ ] IMPLEMENTATION_SUMMARY.md created with overview
- [ ] POSTMAN_NOTIFICATIONS_GUIDE.md created for Postman setup

---

## Runtime Validation

### ✅ Connection Tests
- [ ] Database connection working
- [ ] Run: `php artisan tinker` → `DB::connection()->getPdo()`
- [ ] All tables accessible
- [ ] Foreign keys not causing constraint errors

### ✅ Model Tests
```bash
# In Tinker
$due = App\Models\MembershipDue::first();
$due->member                    # Should return Member
$due->payments                  # Should return collection of DuesPayment
$due->notifications             # Should return collection of DuesNotification
```

### ✅ Observer Tests
```bash
# In Tinker
$due = App\Models\MembershipDue::find(1);
$due->checkAndSendExpirationWarnings();  # Should not error
# Check logs and dues_notifications table for results
```

### ✅ Payment Year Validation
```bash
# In Tinker
$due = App\Models\MembershipDue::create([
    'member_id' => 1,
    'amount' => 5000,
    'due_year' => 2024,
    'due_date' => '2024-03-31'
]);

# Test payment year validation
$result = $due->markAsPaid(5000, '2024-06-15');  # Should return true
$result = $due->markAsPaid(5000, '2025-06-15');  # Should return false
$due->refresh();
$due->status;  # After 2024 payment, should be 'paid'
```

### ✅ Notification Creation
```bash
# In Tinker
$member = App\Models\Member::first();
$member->membership_end_date = now()->addMonths(4)->endOfDay();
$member->save();

# Create due expiring soon
$due = App\Models\MembershipDue::create([
    'member_id' => $member->id,
    'amount' => 5000,
    'due_year' => 2026,
    'status' => 'pending'
]);

# Trigger warning check
$due->checkAndSendExpirationWarnings();

# Verify notification created
$notification = App\Models\DuesNotification::where('membership_due_id', $due->id)->first();
echo $notification->type;      # Should print 'first_warning'
echo $notification->message;   # Should contain warning text
```

### ✅ API Endpoint Tests

**Requires:** Bearer token from user authentication

```bash
# Get user with bearer token
curl -H "Authorization: Bearer TOKEN" \
  http://api.pcci.local/api/v1/user

# Get member ID from user
curl -H "Authorization: Bearer TOKEN" \
  http://api.pcci.local/api/v1/user | jq '.data.member_id'

# Test notification endpoints
curl -H "Authorization: Bearer TOKEN" \
  http://api.pcci.local/api/v1/members/1/notifications

curl -H "Authorization: Bearer TOKEN" \
  http://api.pcci.local/api/v1/members/1/notifications/stats
```

---

## Pre-Production Checklist

### ✅ Code Quality
- [ ] No debug statements left in code
- [ ] No dump()/dd() statements in controllers
- [ ] No logging of sensitive data
- [ ] All TODOs documented for future work
- [ ] Code follows Laravel conventions
- [ ] Proper error handling for all endpoints

### ✅ Security
- [ ] Authorization checks in all controllers
- [ ] Input validation on all request data
- [ ] SQL injection prevented (using Eloquent)
- [ ] XSS protection via JSON responses
- [ ] CORS properly configured
- [ ] Rate limiting considered

### ✅ Performance
- [ ] Database indexes on foreign keys
- [ ] Relationships eager-loaded where needed
- [ ] Pagination implemented (20 items/page)
- [ ] No N+1 queries
- [ ] Query optimization reviewed

### ✅ Testing
- [ ] Sample data created for testing
- [ ] All endpoints tested with Postman
- [ ] Error cases tested (404, 403, 422)
- [ ] Edge cases considered (unread, empty, expired)
- [ ] Role-based access verified

---

## Deployment Steps

### Step 1: Backup Current Database
```bash
# Backup current database
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Step 2: Deploy Code
```bash
# Pull latest code
git pull origin main

# Install any new dependencies
composer install

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Step 3: Run Migrations
```bash
# Run pending migrations
php artisan migrate

# Verify tables created
php artisan tinker
>>> DB::table('membership_dues')->count()
>>> DB::table('dues_payments')->count()
>>> DB::table('dues_notifications')->count()
```

### Step 4: Verify Observer Registration
```bash
# Check AppServiceProvider
grep -n "MembershipDueObserver" app/Providers/AppServiceProvider.php

# Should see: boot() method with observer registration
```

### Step 5: Clear Caches
```bash
php artisan cache:clear
php artisan config:cache
php artisan route:cache
```

### Step 6: Test API
```bash
# Get bearer token from user
# Test notification endpoints with Postman
# Verify all data returned correctly
```

### Step 7: Monitor Logs
```bash
# Watch logs for errors
tail -f storage/logs/laravel.log

# Test warning checks
php artisan tinker
>>> $due = App\Models\MembershipDue::first();
>>> $due->checkAndSendExpirationWarnings();

# Check logs and notifications table
```

---

## Post-Deployment Validation

### ✅ Live Testing
- [ ] Can create member notifications
- [ ] Can retrieve member notifications
- [ ] Can mark notifications as read
- [ ] Can filter notifications
- [ ] Can get statistics
- [ ] Payment received notifications create automatically
- [ ] Warning checks trigger automatically
- [ ] Admins can view all member notifications
- [ ] Members can only view own notifications

### ✅ Data Consistency
- [ ] Payment year validation works in production
- [ ] Payment creates correct notification type
- [ ] Warning checks don't create duplicates
- [ ] Cascading deletes work correctly
- [ ] Unique constraints enforced

### ✅ Performance Verification
```bash
# Check slow queries
# Should see response times < 500ms for list endpoints
# Should see response times < 1000ms for bulk operations
```

### ✅ Error Handling
- [ ] 404 returned for non-existent resources
- [ ] 403 returned for unauthorized access
- [ ] 422 returned for validation errors
- [ ] 500 errors logged but don't crash API

### ✅ Monitoring Setup
- [ ] Error tracking enabled (Sentry, etc.)
- [ ] Database monitoring active
- [ ] API response times monitored
- [ ] Notification queue monitored (if queued)
- [ ] Logs rotated regularly

---

## Rollback Plan

### If Issues Occur

**Step 1: Identify Issue**
```bash
# Check logs
tail -f storage/logs/laravel.log

# Check database
php artisan tinker
>>> App\Models\DuesNotification::count()
```

**Step 2: Rollback Migrations**
```bash
# Rollback last migration batch
php artisan migrate:rollback

# Or specific migration
php artisan migrate:rollback --step=3
```

**Step 3: Restore Backup**
```bash
# Restore from backup
mysql -u username -p database_name < backup_YYYYMMDD_HHMMSS.sql
```

**Step 4: Revert Code**
```bash
# Revert to previous version
git reset --hard previous_commit

# Clear caches
php artisan cache:clear
```

---

## Monitoring & Maintenance

### Daily Checks
- [ ] Check for database errors in logs
- [ ] Verify warning checks triggering
- [ ] Check notification queue (if implemented)
- [ ] Monitor API response times

### Weekly Checks
- [ ] Review error logs for patterns
- [ ] Verify payment year calculations still correct
- [ ] Check database growth (dues_notifications table size)
- [ ] Verify backups running

### Monthly Checks
- [ ] Review performance metrics
- [ ] Archive old notifications (optional)
- [ ] Update documentation if needed
- [ ] Plan future enhancements

---

## Known Limitations & Future Work

### Current Limitations
1. Email notifications deferred (set aside for later)
2. No SMS notifications
3. No bulk dues import
4. No UI dashboard for notifications

### Future Enhancements
1. Enable email notifications
2. Add SMS notifications
3. Create admin dashboard
4. Add bulk dues import
5. Add payment installments
6. Add delinquency reports

---

## Support Documentation

**For troubleshooting, refer to:**
- DUES_NOTIFICATIONS_API.md - API endpoint reference
- DUES_PAYMENT_SYSTEM.md - System architecture
- IMPLEMENTATION_SUMMARY.md - Component overview
- MembershipDue model - Business logic

**Contact:** [Your contact info]

---

## Sign-Off

- [ ] Database Architect: _________________ Date: _______
- [ ] Backend Lead: _________________ Date: _______
- [ ] QA Lead: _________________ Date: _______
- [ ] DevOps Lead: _________________ Date: _______

---

**Deployment Date:** _______________  
**Deployed By:** _______________  
**Reviewed By:** _______________
