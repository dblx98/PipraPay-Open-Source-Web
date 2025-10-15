# Webhook Processing Plugin Guide

* **Version**: v1.0
* **Summary**: This guide explains how to create plugins that process incoming webhook requests without modifying `index.php`. PipraPay now triggers hooks when webhooks are received, allowing plugins to intercept, process, or extend webhook functionality.

## Overview

Previously, webhook processing logic was hardcoded in `index.php`. Now, the system triggers two hooks during webhook processing:

1. **`pp_webhook_received`** - Fired immediately after webhook validation, before default processing
2. **`pp_webhook_processed`** - Fired after default processing completes

This allows plugins to:
- Add custom webhook endpoints for third-party integrations
- Process webhook data from external services
- Extend or override default webhook behavior
- Log webhook events
- Transform webhook data before storage

## Webhook Hooks Reference

### Hook: `pp_webhook_received`

**Trigger Location:** `index.php` (after webhook key validation, before device/SMS processing)

**Arguments:**
- `$webhook` (string) - The webhook key from the URL parameter
- `$post_data` (array) - The `$_POST` superglobal array
- `$raw_input` (string) - Raw POST body from `php://input`

**Typical Use:**
- Validate webhook signatures from third-party services
- Parse custom webhook payloads
- Route webhooks to appropriate handlers based on content
- Early exit for custom webhook endpoints

**Example:**
```php
add_action('pp_webhook_received', 'my_custom_webhook_handler');

function my_custom_webhook_handler($webhook, $post_data, $raw_input) {
    // Check if this is a webhook for your plugin
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    if ($user_agent === 'MyService/1.0') {
        $data = json_decode($raw_input, true);
        
        // Process your webhook data
        process_my_service_webhook($data);
        
        // Return response and exit
        echo json_encode(['status' => 'success', 'message' => 'Webhook processed']);
        exit();
    }
}
```

### Hook: `pp_webhook_processed`

**Trigger Location:** `index.php` (after default device/SMS processing completes)

**Arguments:**
- `$webhook` (string) - The webhook key from the URL parameter
- `$device_status` (string) - Device connection status (e.g., "Connected", "Pairing")

**Typical Use:**
- Send notifications after webhook processing
- Log successful webhook events
- Sync data to external systems
- Update analytics

**Example:**
```php
add_action('pp_webhook_processed', 'notify_webhook_processed');

function notify_webhook_processed($webhook, $device_status) {
    // Send notification to admin
    $settings = pp_get_settings();
    
    $message = "Webhook processed: $webhook - Status: $device_status";
    
    // Log or send notification
    error_log($message);
}
```

## Creating a Webhook Processing Plugin

### Step 1: Create Plugin Structure

Create your plugin directory:
```
pp-content/plugins/modules/my-webhook-handler/
├── meta.json
├── my-webhook-handler-class.php
├── functions.php
└── assets/
    └── icon.png
```

### Step 2: Create `meta.json`

```json
{
  "type": "plugins",
  "slug": "my-webhook-handler",
  "name": "My Webhook Handler",
  "mrdr": "modules"
}
```

### Step 3: Create `my-webhook-handler-class.php`

```php
<?php
if (!defined('pp_allowed_access')) {
    die('Direct access not allowed');
}

$plugin_meta = [
    'Plugin Name'       => 'My Webhook Handler',
    'Description'       => 'Custom webhook processor for external services',
    'Version'           => '1.0.0',
    'Author'            => 'Your Name',
    'Author URI'        => 'https://yoursite.com/',
    'License'           => 'GPL-2.0+',
    'License URI'       => 'http://www.gnu.org/licenses/gpl-2.0.txt',
    'Requires at least' => '1.0.0',
    'Plugin URI'        => '',
    'Text Domain'       => '',
    'Domain Path'       => '',
    'Requires PHP'      => '7.4'
];

$funcFile = __DIR__ . '/functions.php';
if (file_exists($funcFile)) {
    require_once $funcFile;
}

function my_webhook_handler_admin_page() {
    $viewFile = __DIR__ . '/views/admin-ui.php';
    if (file_exists($viewFile)) {
        include $viewFile;
    } else {
        echo "<div class='alert alert-warning'>Admin UI not found.</div>";
    }
}
```

### Step 4: Create `functions.php`

```php
<?php
if (!defined('pp_allowed_access')) {
    die('Direct access not allowed');
}

// Register webhook processing hooks
add_action('pp_webhook_received', 'my_webhook_handler_process');

function my_webhook_handler_process($webhook, $post_data, $raw_input) {
    global $conn, $db_prefix;
    
    // Check if this webhook is for your service
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Example: Handle Stripe webhooks
    if ($user_agent === 'Stripe/1.0' || strpos($raw_input, 'stripe') !== false) {
        $payload = json_decode($raw_input, true);
        
        if (!$payload) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
            exit();
        }
        
        // Verify webhook signature (implement your verification logic)
        $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        if (!verify_stripe_signature($raw_input, $signature)) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
            exit();
        }
        
        // Process the webhook event
        $event_type = $payload['type'] ?? '';
        
        switch ($event_type) {
            case 'payment_intent.succeeded':
                handle_payment_success($payload['data']['object']);
                break;
                
            case 'payment_intent.payment_failed':
                handle_payment_failure($payload['data']['object']);
                break;
                
            default:
                // Log unhandled event
                error_log("Unhandled Stripe event: $event_type");
        }
        
        // Return success response
        echo json_encode(['status' => 'success']);
        exit();
    }
}

function verify_stripe_signature($payload, $signature) {
    // Implement Stripe signature verification
    // This is a placeholder - use actual Stripe webhook verification
    $plugin_settings = pp_get_plugin_setting('my-webhook-handler');
    $secret = $plugin_settings['webhook_secret'] ?? '';
    
    // Verify signature here
    return true; // Replace with actual verification
}

function handle_payment_success($payment_intent) {
    global $conn, $db_prefix;
    
    // Update transaction in database
    $transaction_id = $payment_intent['metadata']['transaction_id'] ?? null;
    
    if ($transaction_id) {
        // Update transaction status
        $columns = ['transaction_status'];
        $values = ['completed'];
        $condition = "pp_id = '$transaction_id'";
        
        updateData($db_prefix . 'transactions', $columns, $values, $condition);
        
        // Trigger transaction IPN hook
        if (function_exists('pp_trigger_hook')) {
            pp_trigger_hook('pp_transaction_ipn', $transaction_id);
        }
    }
}

function handle_payment_failure($payment_intent) {
    global $conn, $db_prefix;
    
    // Handle failed payment
    $transaction_id = $payment_intent['metadata']['transaction_id'] ?? null;
    
    if ($transaction_id) {
        // Update transaction status
        $columns = ['transaction_status'];
        $values = ['failed'];
        $condition = "pp_id = '$transaction_id'";
        
        updateData($db_prefix . 'transactions', $columns, $values, $condition);
    }
}
```

## Complete Example: Payment Gateway Webhook Handler

Here's a complete example showing how to create a webhook handler for a payment gateway:

```php
<?php
if (!defined('pp_allowed_access')) {
    die('Direct access not allowed');
}

add_action('pp_webhook_received', 'payment_gateway_webhook_handler');

function payment_gateway_webhook_handler($webhook, $post_data, $raw_input) {
    global $conn, $db_prefix;
    
    // Check for specific header that identifies your gateway
    $gateway_signature = $_SERVER['HTTP_X_GATEWAY_SIGNATURE'] ?? '';
    
    if (empty($gateway_signature)) {
        return; // Not our webhook, let other handlers process it
    }
    
    // Parse the webhook payload
    $payload = json_decode($raw_input, true);
    
    if (!$payload) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON payload']);
        exit();
    }
    
    // Verify the webhook signature
    $plugin_settings = pp_get_plugin_setting('payment-gateway-webhook');
    $secret_key = $plugin_settings['secret_key'] ?? '';
    
    $expected_signature = hash_hmac('sha256', $raw_input, $secret_key);
    
    if (!hash_equals($expected_signature, $gateway_signature)) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid signature']);
        exit();
    }
    
    // Process different event types
    $event_type = $payload['event'] ?? '';
    $transaction_data = $payload['transaction'] ?? [];
    
    switch ($event_type) {
        case 'payment.completed':
            process_payment_completed($transaction_data);
            break;
            
        case 'payment.refunded':
            process_payment_refunded($transaction_data);
            break;
            
        case 'payment.disputed':
            process_payment_disputed($transaction_data);
            break;
            
        default:
            error_log("Unknown webhook event: $event_type");
    }
    
    // Return success response
    http_response_code(200);
    echo json_encode(['status' => 'success', 'event' => $event_type]);
    exit();
}

function process_payment_completed($transaction_data) {
    global $conn, $db_prefix;
    
    $reference_id = $transaction_data['reference_id'] ?? '';
    $amount = $transaction_data['amount'] ?? 0;
    $gateway_transaction_id = $transaction_data['transaction_id'] ?? '';
    
    // Find the transaction in database
    $result = json_decode(
        getData($db_prefix . 'transactions', "WHERE pp_id = '$reference_id'"),
        true
    );
    
    if ($result['status'] !== true) {
        error_log("Transaction not found: $reference_id");
        return;
    }
    
    $transaction = $result['response'][0];
    
    // Verify amount matches
    if ((float)$transaction['transaction_amount'] !== (float)$amount) {
        error_log("Amount mismatch for transaction: $reference_id");
        return;
    }
    
    // Update transaction
    $columns = ['transaction_status', 'gateway_transaction_id', 'updated_at'];
    $values = ['completed', $gateway_transaction_id, date('Y-m-d H:i:s')];
    $condition = "pp_id = '$reference_id'";
    
    updateData($db_prefix . 'transactions', $columns, $values, $condition);
    
    // Trigger IPN hook for other plugins to react
    if (function_exists('pp_trigger_hook')) {
        pp_trigger_hook('pp_transaction_ipn', $reference_id);
    }
}

function process_payment_refunded($transaction_data) {
    global $conn, $db_prefix;
    
    $reference_id = $transaction_data['reference_id'] ?? '';
    $refund_amount = $transaction_data['refund_amount'] ?? 0;
    
    // Update transaction status to refunded
    $columns = ['transaction_status', 'refund_amount', 'updated_at'];
    $values = ['refunded', $refund_amount, date('Y-m-d H:i:s')];
    $condition = "pp_id = '$reference_id'";
    
    updateData($db_prefix . 'transactions', $columns, $values, $condition);
    
    // Trigger IPN hook
    if (function_exists('pp_trigger_hook')) {
        pp_trigger_hook('pp_transaction_ipn', $reference_id);
    }
}

function process_payment_disputed($transaction_data) {
    global $conn, $db_prefix;
    
    $reference_id = $transaction_data['reference_id'] ?? '';
    $dispute_reason = $transaction_data['dispute_reason'] ?? '';
    
    // Update transaction status
    $columns = ['transaction_status', 'notes', 'updated_at'];
    $values = ['disputed', "Dispute: $dispute_reason", date('Y-m-d H:i:s')];
    $condition = "pp_id = '$reference_id'";
    
    updateData($db_prefix . 'transactions', $columns, $values, $condition);
}
```

## Best Practices

### 1. Identify Your Webhooks
Use unique identifiers to determine if a webhook is for your plugin:
- Check `User-Agent` header
- Look for specific HTTP headers
- Check payload structure or content

### 2. Verify Webhook Authenticity
Always verify webhooks are from legitimate sources:
```php
function verify_webhook_signature($payload, $signature, $secret) {
    $expected = hash_hmac('sha256', $payload, $secret);
    return hash_equals($expected, $signature);
}
```

### 3. Exit After Processing
If your plugin handles the webhook, exit to prevent default processing:
```php
echo json_encode(['status' => 'success']);
exit();
```

### 4. Return Early if Not Your Webhook
Let other plugins process webhooks they're designed for:
```php
if (!is_my_webhook()) {
    return; // Let other handlers process it
}
```

### 5. Use Proper HTTP Status Codes
```php
// Success
http_response_code(200);

// Bad request (invalid payload)
http_response_code(400);

// Unauthorized (invalid signature)
http_response_code(401);

// Internal error
http_response_code(500);
```

### 6. Log Important Events
```php
error_log("Webhook received from: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
error_log("Event type: $event_type");
```

### 7. Handle Errors Gracefully
```php
try {
    process_webhook($payload);
} catch (Exception $e) {
    error_log("Webhook processing error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Processing failed']);
    exit();
}
```

### 8. Store Webhook Settings Securely
Use the plugin settings API to store sensitive data:
```php
// Save settings
pp_set_plugin_setting('my-webhook-handler', [
    'webhook_secret' => $secret_key,
    'api_key' => $api_key
]);

// Retrieve settings
$settings = pp_get_plugin_setting('my-webhook-handler');
$secret = $settings['webhook_secret'] ?? '';
```

## Testing Your Webhook Plugin

### 1. Using cURL
```bash
curl -X POST "https://yoursite.com/?webhook=your-webhook-key" \
  -H "Content-Type: application/json" \
  -H "User-Agent: MyService/1.0" \
  -H "X-Signature: your-signature" \
  -d '{"event":"payment.completed","transaction":{"reference_id":"TXN123","amount":100.00}}'
```

### 2. Using Postman
1. Set method to POST
2. URL: `https://yoursite.com/?webhook=your-webhook-key`
3. Add headers:
   - `Content-Type: application/json`
   - `User-Agent: MyService/1.0`
   - Custom headers for authentication
4. Add JSON body with test data
5. Send request and check response

### 3. Using PHP Test Script
```php
<?php
$webhook_url = 'https://yoursite.com/?webhook=your-webhook-key';

$payload = [
    'event' => 'payment.completed',
    'transaction' => [
        'reference_id' => 'TXN123',
        'amount' => 100.00
    ]
];

$ch = curl_init($webhook_url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'User-Agent: MyService/1.0',
        'X-Signature: ' . hash_hmac('sha256', json_encode($payload), 'secret-key')
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Response: $response\n";
```

## Webhook URL Format

Webhooks are accessed via:
```
https://yoursite.com/?webhook=YOUR_WEBHOOK_KEY
```

The webhook key is stored in the `settings` table and can be generated from:
- Admin Panel → Settings → API & Webhook Settings
- Using the "Generate New Webhook Key" button

## Debugging Webhook Issues

### Enable Error Logging
Add to your `functions.php`:
```php
function log_webhook_data($webhook, $post_data, $raw_input) {
    $log_file = __DIR__ . '/webhook-debug.log';
    $timestamp = date('Y-m-d H:i:s');
    
    $log_entry = "[$timestamp] Webhook: $webhook\n";
    $log_entry .= "Headers: " . json_encode(getallheaders()) . "\n";
    $log_entry .= "POST: " . json_encode($post_data) . "\n";
    $log_entry .= "Body: $raw_input\n";
    $log_entry .= str_repeat('-', 80) . "\n";
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

add_action('pp_webhook_received', 'log_webhook_data');
```

### Check Plugin Activation
Ensure your plugin is activated in the admin panel:
- Go to Admin → More → Modules
- Find your plugin
- Click "Activate" if not already active

### Verify Hook Registration
Add a test to confirm hooks are registered:
```php
error_log("Webhook handler registered: " . (function_exists('my_webhook_handler_process') ? 'yes' : 'no'));
```

## Security Considerations

1. **Always verify webhook signatures** - Never trust incoming data without verification
2. **Use HTTPS** - Always use secure connections for webhooks
3. **Validate all input** - Sanitize and validate all incoming data
4. **Rate limiting** - Consider implementing rate limiting for webhook endpoints
5. **IP whitelisting** - If possible, restrict webhooks to known IP addresses
6. **Store secrets securely** - Use plugin settings API, never hardcode secrets
7. **Log suspicious activity** - Track failed authentication attempts

## Migration from Hardcoded Webhooks

If you have custom webhook code in `index.php`, migrate it to a plugin:

1. Create a new module plugin as shown above
2. Move your webhook processing logic to `functions.php`
3. Register the logic with `add_action('pp_webhook_received', 'your_handler')`
4. Test thoroughly
5. Activate the plugin
6. Remove custom code from `index.php` (now unnecessary)

## Additional Resources

- [PipraPay Module Plugin Developer Guide](./PipraPay-Module-Plugin-Developer-Guide.md)
- [Payment Gateway Plugins Developer Guide](./Payment-Gateway-Plugins-Developer-Guide.md)
- [Plugin Directory](./Plugin-Directory.md)

---

© [PipraPay](https://piprapay.com) — Webhook Processing Plugin Documentation
