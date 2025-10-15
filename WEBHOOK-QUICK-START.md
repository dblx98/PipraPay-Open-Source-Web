# Quick Start: Processing Webhooks with Plugins

This guide shows you how to process webhooks using plugins without modifying `index.php`.

## The Problem (Before)

Previously, all webhook processing was hardcoded in `index.php`. If you wanted to add custom webhook handling, you had to:
1. Edit `index.php` directly
2. Mix your code with core system code
3. Risk breaking updates

## The Solution (Now)

Webhook processing is now hook-based. Plugins can intercept and process webhooks without touching `index.php`.

## Two New Hooks

### 1. `pp_webhook_received`
Fires **before** default processing. Use this to:
- Process webhooks from external services
- Validate signatures
- Exit early for custom webhooks

### 2. `pp_webhook_processed`  
Fires **after** default processing. Use this to:
- Log events
- Send notifications
- Sync to external systems

## Quick Example

Create a plugin that processes Stripe webhooks:

### 1. Create Plugin Directory
```
pp-content/plugins/modules/stripe-webhook/
```

### 2. Create `meta.json`
```json
{
  "type": "plugins",
  "slug": "stripe-webhook",
  "name": "Stripe Webhook Handler",
  "mrdr": "modules"
}
```

### 3. Create `stripe-webhook-class.php`
```php
<?php
if (!defined('pp_allowed_access')) {
    die('Direct access not allowed');
}

$plugin_meta = [
    'Plugin Name'       => 'Stripe Webhook Handler',
    'Description'       => 'Processes Stripe payment webhooks',
    'Version'           => '1.0.0',
    'Author'            => 'Your Name'
];

$funcFile = __DIR__ . '/functions.php';
if (file_exists($funcFile)) {
    require_once $funcFile;
}

function stripe_webhook_admin_page() {
    echo "<div class='alert alert-info'>Stripe webhook handler is active.</div>";
}
```

### 4. Create `functions.php`
```php
<?php
if (!defined('pp_allowed_access')) {
    die('Direct access not allowed');
}

// Register the webhook handler
add_action('pp_webhook_received', 'stripe_webhook_handler');

function stripe_webhook_handler($webhook, $post_data, $raw_input) {
    // Only process if this is a Stripe webhook
    $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    
    if (empty($signature)) {
        return; // Not a Stripe webhook, let others handle it
    }
    
    // Parse the webhook
    $payload = json_decode($raw_input, true);
    
    if (!$payload) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid payload']);
        exit();
    }
    
    // Verify signature (simplified - use Stripe SDK in production)
    $settings = pp_get_plugin_setting('stripe-webhook');
    $secret = $settings['webhook_secret'] ?? '';
    
    // Process the event
    $event_type = $payload['type'] ?? '';
    
    switch ($event_type) {
        case 'payment_intent.succeeded':
            handle_payment_success($payload['data']['object']);
            break;
            
        case 'charge.refunded':
            handle_refund($payload['data']['object']);
            break;
    }
    
    // Return success and exit (prevents default processing)
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    exit();
}

function handle_payment_success($payment_intent) {
    global $db_prefix;
    
    $transaction_id = $payment_intent['metadata']['pp_id'] ?? '';
    
    if ($transaction_id) {
        // Update transaction status
        $columns = ['transaction_status'];
        $values = ['completed'];
        $condition = "pp_id = '$transaction_id'";
        
        updateData($db_prefix . 'transactions', $columns, $values, $condition);
        
        // Trigger IPN for other plugins
        pp_trigger_hook('pp_transaction_ipn', $transaction_id);
    }
}

function handle_refund($charge) {
    global $db_prefix;
    
    $transaction_id = $charge['metadata']['pp_id'] ?? '';
    
    if ($transaction_id) {
        $columns = ['transaction_status'];
        $values = ['refunded'];
        $condition = "pp_id = '$transaction_id'";
        
        updateData($db_prefix . 'transactions', $columns, $values, $condition);
    }
}
```

### 5. Activate the Plugin
1. Go to Admin Panel → More → Modules
2. Find "Stripe Webhook Handler"
3. Click "Activate"

### 6. Configure Stripe
In your Stripe dashboard, set webhook URL to:
```
https://yoursite.com/?webhook=YOUR_WEBHOOK_KEY
```

## Testing Your Plugin

### Using cURL
```bash
curl -X POST "https://yoursite.com/?webhook=your-key" \
  -H "Content-Type: application/json" \
  -H "Stripe-Signature: test-signature" \
  -d '{
    "type": "payment_intent.succeeded",
    "data": {
      "object": {
        "id": "pi_123",
        "amount": 1000,
        "metadata": {
          "pp_id": "TXN123"
        }
      }
    }
  }'
```

### Using the Example Plugin
The `webhook-logger` plugin logs all webhook requests:

1. Activate it from Admin Panel → More → Modules
2. Send test webhook requests
3. Check logs at:
   ```
   pp-content/plugins/modules/webhook-logger/webhook-requests.log
   ```

## Key Benefits

✅ **No core file modifications** - Keep `index.php` untouched  
✅ **Multiple handlers** - Multiple plugins can process different webhooks  
✅ **Easy updates** - System updates don't break your custom code  
✅ **Reusable** - Share plugins with others  
✅ **Testable** - Test webhooks independently  

## Complete Documentation

For comprehensive details, examples, and best practices:

📖 [Webhook Processing Plugin Guide](docs/Webhook-Processing-Plugin-Guide.md)

## Example Plugin

See a working example in:
```
pp-content/plugins/modules/webhook-logger/
```

This plugin logs all webhook requests for debugging.

## Getting Help

- Review the [Module Plugin Developer Guide](docs/PipraPay-Module-Plugin-Developer-Guide.md)
- Check the [Webhook Processing Plugin Guide](docs/Webhook-Processing-Plugin-Guide.md)  
- Examine the webhook-logger example plugin

---

**That's it!** You can now process webhooks using plugins without modifying `index.php`.
