# Test Cases for Modular Architecture

## Test 1: Webhook Handler

### Test Case 1.1: Valid Webhook with Device Pairing
**Purpose**: Verify device pairing functionality works correctly

**Request**:
```bash
curl -X POST "http://localhost/?webhook=test_webhook_token" \
  -H "User-Agent: mh-piprapay-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "d_model": "SM-G998B",
    "d_brand": "Samsung",
    "d_version": "12",
    "d_api_level": "31"
  }'
```

**Expected Response**:
```json
{
  "status": "true",
  "message": "Device Connected"
}
```

**Expected Behavior**:
- Device should be added to or updated in `pp_devices` table
- Connection status should be set to "Connected"
- Timestamp should be updated

### Test Case 1.2: bKash SMS Parsing
**Purpose**: Verify bKash SMS parsing works correctly

**Request**:
```bash
curl -X POST "http://localhost/?webhook=test_webhook_token" \
  -H "User-Agent: mh-piprapay-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "from": "bKash",
    "text": "Cash In Tk 1,500.00 from 01712345678 successful. Fee Tk 15.00. Balance Tk 10,500.00. TrxID AK47XYZ123 at 15/10/2025 14:30",
    "sim": 1,
    "sentStamp": "2025-10-15 14:30:00",
    "receivedStamp": "2025-10-15 14:30:05"
  }'
```

**Expected Response**:
```json
{
  "status": "true",
  "message": "Device Connected"
}
```

**Expected Behavior**:
- SMS should be parsed and stored in `pp_sms_data` table
- Fields extracted:
  - `payment_method`: "bKash"
  - `mobile_number`: "01712345678"
  - `transaction_id`: "AK47XYZ123"
  - `amount`: "1500.00"
  - `balance`: "10500.00"
  - `sim`: "sim1"
  - `status`: "approved" (because pattern matched)
  - `entry_type`: "automatic"

### Test Case 1.3: Nagad SMS Parsing
**Purpose**: Verify Nagad SMS parsing works correctly

**Request**:
```bash
curl -X POST "http://localhost/?webhook=test_webhook_token" \
  -H "User-Agent: mh-piprapay-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "from": "NAGAD",
    "text": "Cash In Received.\nAmount: Tk 2,000.00\nUddokta: 01812345678\nTxnID: NG123ABC456\nBalance: 15,000.00\n15/10/2025 15:00",
    "sim": 2,
    "sentStamp": "2025-10-15 15:00:00",
    "receivedStamp": "2025-10-15 15:00:05"
  }'
```

**Expected Behavior**:
- `payment_method`: "Nagad"
- `mobile_number`: "01812345678"
- `transaction_id`: "NG123ABC456"
- `amount`: "2000.00"
- `balance`: "15000.00"
- `sim`: "sim2"
- `status`: "approved"

### Test Case 1.4: Invalid Webhook Token
**Purpose**: Verify security - invalid webhook tokens are rejected

**Request**:
```bash
curl -X POST "http://localhost/?webhook=invalid_token" \
  -H "User-Agent: mh-piprapay-api-key" \
  -d '{}'
```

**Expected Response**:
```json
{
  "status": "false",
  "message": "Invalid Webhook"
}
```

### Test Case 1.5: Unrecognized SMS Format
**Purpose**: Verify unrecognized SMS are marked for review

**Request**:
```bash
curl -X POST "http://localhost/?webhook=test_webhook_token" \
  -H "User-Agent: mh-piprapay-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "from": "bKash",
    "text": "This is a random message that does not match any pattern",
    "sim": 1,
    "sentStamp": "2025-10-15 14:30:00",
    "receivedStamp": "2025-10-15 14:30:05"
  }'
```

**Expected Behavior**:
- SMS should be stored with `status`: "review" (not approved)
- Amount defaults to "0"
- Transaction ID defaults to "--"

## Test 2: Cron Handler

### Test Case 2.1: Cron Job Execution
**Purpose**: Verify cron job hook is triggered

**Request**:
```bash
curl "http://localhost/?cron=1"
```

**Expected Response**:
```json
{
  "status": "false",
  "message": "Direct access not allowed"
}
```

**Expected Behavior**:
- `pp_trigger_hook('pp_cron')` should be called
- All active plugins with `pp_cron` hook should execute
- Response indicates cron ran but direct access is not allowed

## Test 3: Request Router

### Test Case 3.1: Authenticated User Redirect
**Purpose**: Verify authenticated users are redirected to dashboard

**Setup**: Set valid authentication cookie

**Request**:
```bash
curl "http://localhost/" \
  -H "Cookie: pp_admin=valid_session_token"
```

**Expected Response**:
```html
<script>
  location.href="https://localhost/admin/dashboard";
</script>
```

### Test Case 3.2: Guest User Redirect
**Purpose**: Verify unauthenticated users are redirected to login

**Request**:
```bash
curl "http://localhost/"
```

**Expected Response**:
```html
<script>
  location.href="https://localhost/admin/login";
</script>
```

## Test 4: Architecture Validation

### Test Case 4.1: index.php Unchanged
**Purpose**: Verify index.php was not modified

**Command**:
```bash
git diff 9859492 HEAD index.php
```

**Expected Output**: Empty (no differences)

### Test Case 4.2: Handler Execution Order
**Purpose**: Verify handlers execute before index.php logic

**Method**: Add temporary logging to verify execution flow

**Expected Order**:
1. pp-controller.php included
2. pp-model.php included
3. pp-webhook-handler.php included
4. pp-request-router.php included
5. Handler processes request and exits
6. Old index.php logic NEVER executes

### Test Case 4.3: PHP Syntax Validation
**Purpose**: Verify all files have valid PHP syntax

**Commands**:
```bash
php -l index.php
php -l pp-include/pp-controller.php
php -l pp-include/pp-model.php
php -l pp-include/pp-webhook-handler.php
php -l pp-include/pp-request-router.php
```

**Expected Output**: "No syntax errors detected" for all files

## Test 5: SMS Provider Coverage

### Test Case 5.1: All Supported Providers
**Purpose**: Verify SMS parsing for all supported providers

**Providers to Test**:
- ✅ bKash (5 formats)
- ✅ Nagad (2 formats)
- ✅ Rocket/16216 (2 formats)
- ✅ Upay (2 formats)
- ⚠️ Tap (basic detection only)
- ⚠️ OkWallet (basic detection only)
- ⚠️ Cellfin (basic detection only)
- ⚠️ Ipay (basic detection only)
- ⚠️ Pathao Pay (basic detection only)

**Note**: Providers marked with ⚠️ are detected by name but may need format patterns added.

## Performance Tests

### Test Case 6.1: Response Time
**Purpose**: Verify handlers respond quickly

**Method**: Measure response time for webhook requests

**Expected**: < 500ms for typical webhook request

**Command**:
```bash
time curl -X POST "http://localhost/?webhook=test_token" \
  -H "User-Agent: mh-piprapay-api-key" \
  -d '{}'
```

### Test Case 6.2: Concurrent Requests
**Purpose**: Verify system handles multiple simultaneous webhook requests

**Method**: Use Apache Bench or similar tool

**Command**:
```bash
ab -n 100 -c 10 -H "User-Agent: mh-piprapay-api-key" \
  -p webhook_data.json \
  "http://localhost/?webhook=test_token"
```

**Expected**: All requests complete successfully with no errors

## Security Tests

### Test Case 7.1: Direct Handler Access
**Purpose**: Verify handlers cannot be accessed directly

**Request**:
```bash
curl "http://localhost/pp-include/pp-webhook-handler.php"
```

**Expected Behavior**: 
- Should be blocked by web server (403 Forbidden) OR
- Should exit with "Direct access not allowed" if pp_allowed_access not defined

### Test Case 7.2: SQL Injection Prevention
**Purpose**: Verify inputs are properly sanitized

**Request**:
```bash
curl -X POST "http://localhost/?webhook=test'; DROP TABLE pp_sms_data;--" \
  -H "User-Agent: mh-piprapay-api-key" \
  -d '{}'
```

**Expected**: Should be safely escaped and not execute SQL

### Test Case 7.3: XSS Prevention
**Purpose**: Verify HTML/JS in SMS messages is properly handled

**Request**:
```bash
curl -X POST "http://localhost/?webhook=test_token" \
  -H "User-Agent: mh-piprapay-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "from": "bKash",
    "text": "<script>alert('XSS')</script>",
    "sim": 1
  }'
```

**Expected**: Script tags should be stored safely without execution

## Integration Tests

### Test Case 8.1: Plugin Hook Integration
**Purpose**: Verify plugins can hook into pp_cron

**Setup**: Create test plugin that logs when pp_cron fires

**Expected**: Plugin callback should execute when cron runs

### Test Case 8.2: Database Integration
**Purpose**: Verify all database operations work correctly

**Expected**: 
- Device records created/updated properly
- SMS data stored correctly
- No orphaned records
- Proper timestamps

## Rollback Tests

### Test Case 9.1: Handler Removal
**Purpose**: Verify system gracefully handles missing handlers

**Method**: Temporarily rename handler file

**Expected**: System should continue to function (falls back to old code in index.php)

---

## Test Execution Checklist

Before deploying to production:

- [ ] All syntax validation tests pass
- [ ] Webhook handler tests pass
- [ ] Cron handler tests pass
- [ ] Request routing tests pass
- [ ] All SMS provider tests pass
- [ ] Security tests pass
- [ ] Performance tests pass
- [ ] index.php unchanged verified
- [ ] Documentation reviewed
- [ ] Backup created
- [ ] Rollback plan prepared

---

**Test Suite Version**: 1.0  
**Last Updated**: 2025-10-15  
**Status**: Ready for Testing ✓
