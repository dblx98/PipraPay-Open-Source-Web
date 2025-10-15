# MFS Provider Manager - Webhook Integration Guide

## Overview

The MFS Provider Manager module now includes a powerful webhook handler that processes incoming SMS notifications from mobile devices without requiring any modifications to the core `index.php` or other system files.

## Features

✅ **Zero Core Modification**: All webhook handling logic is contained within the module  
✅ **Custom Provider Support**: Add/edit MFS providers without touching code  
✅ **Dynamic SMS Parsing**: Configure regex patterns through the admin UI  
✅ **Automatic Processing**: SMS data is automatically parsed and stored  
✅ **Hook System Integration**: Works seamlessly with PipraPay's plugin system  

## How It Works

### 1. **Automatic Integration (Recommended)**

The webhook handler automatically integrates with PipraPay's plugin system using hooks:

```php
// The module automatically hooks into 'pp_init' action
add_action('pp_init', 'mfs_handle_webhook_request', 1);
```

When a webhook request comes in (`?webhook=your_key`), the MFS Provider Manager:
1. Intercepts the request early in the processing chain
2. Validates the webhook key
3. Handles device pairing/status updates
4. Processes SMS data using custom providers and formats
5. Stores parsed data in the database
6. Returns appropriate response

### 2. **Manual Integration (If Hooks Don't Work)**

If your PipraPay installation doesn't support the hook system, you can manually integrate by adding this single line to your `index.php`:

**Option A: Direct Include (at the top of webhook handling section)**

```php
// In index.php, inside the webhook handling block:
if(isset($_GET['webhook'])){
    $webhook = escape_string($_GET['webhook']);
    
    // Add this line to load MFS Provider Manager webhook handler
    if (file_exists(__DIR__.'/pp-content/plugins/modules/MFS-Provider-Manager/webhook-handler.php')) {
        include_once(__DIR__.'/pp-content/plugins/modules/MFS-Provider-Manager/webhook-handler.php');
        
        // Use the standalone handler
        mfs_standalone_webhook_handler($webhook);
        // This function will handle everything and exit
    }
    
    // ... rest of your existing webhook code (will only run if MFS handler is not available)
}
```

**Option B: Function Call (if already loaded)**

```php
// If functions.php is already loaded, just call:
if (function_exists('mfs_process_webhook')) {
    $webhook_data = [
        'from' => $decoded['from'] ?? ($_POST['from'] ?? ''),
        'text' => $decoded['text'] ?? ($_POST['text'] ?? ''),
        'sentStamp' => $decoded['sentStamp'] ?? ($_POST['sentStamp'] ?? ''),
        'receivedStamp' => $decoded['receivedStamp'] ?? ($_POST['receivedStamp'] ?? ''),
        'sim' => $decoded['sim'] ?? ($_POST['sim'] ?? '')
    ];
    
    $result = mfs_process_webhook($webhook_data);
}
```

## Webhook Handler Functions

### Core Functions

#### `mfs_process_webhook($webhook_data)`

Processes incoming SMS webhook data using MFS Provider Manager settings.

**Parameters:**
- `$webhook_data` (array): Contains webhook request data
  - `from` (string): SMS sender
  - `text` (string): SMS message content
  - `sentStamp` (string): When SMS was sent
  - `receivedStamp` (string): When SMS was received
  - `sim` (int|string): SIM card number (1 or 2)

**Returns:**
```php
[
    'status' => true|false,
    'message' => 'SMS processed successfully',
    'provider' => 'bKash',
    'parsed' => true|false,
    'sms_status' => 'approved|review',
    'data' => [
        'amount' => '1000.00',
        'mobile' => '01712345678',
        'trxid' => 'ABC123XYZ',
        'balance' => '5000.00',
        'datetime' => '2025-10-15 14:30:00'
    ]
]
```

#### `mfs_handle_webhook_request()`

Hook function that automatically intercepts webhook requests. Called by the plugin system on `pp_init` action.

#### `mfs_standalone_webhook_handler($webhook_key)`

Standalone function for manual integration. Handles complete webhook processing and exits.

**Parameters:**
- `$webhook_key` (string): The webhook authentication key

## Configuration

### 1. **Add Custom Providers**

Navigate to: **Admin Panel → Plugins → MFS Provider Manager**

Add your custom providers:
- **Short Name**: The identifier used in SMS (e.g., "NAGAD", "16216")
- **Full Name**: Display name (e.g., "Nagad", "Rocket")

### 2. **Add SMS Formats**

For each provider, add regex patterns to parse SMS messages:

**Example for bKash:**
```regex
/You have received Tk (?<amount>[\d,]+\.\d{2}) from (?<mobile>\d+)\. Fee Tk (?<fee>[\d,]+\.\d{2})\. Balance Tk (?<balance>[\d,]+\.\d{2})\. TrxID (?<trxid>\w+) at (?<datetime>\d{2}\/\d{2}\/\d{4} \d{2}:\d{2})/
```

**Named Groups:**
- `amount`: Transaction amount
- `mobile`: Sender's mobile number
- `trxid`: Transaction ID
- `balance`: Current balance
- `datetime` or `date`+`time`: Transaction timestamp
- `fee` (optional): Transaction fee

### 3. **Test Patterns**

Use the built-in regex tester to validate your patterns before saving.

## Webhook Flow

```
┌─────────────────┐
│ Mobile Device   │
│ Sends SMS       │
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│ PipraPay        │
│ index.php       │
│ ?webhook=key    │
└────────┬────────┘
         │
         ↓
┌─────────────────────────┐
│ MFS Provider Manager    │
│ webhook-handler.php     │
├─────────────────────────┤
│ 1. Validate webhook     │
│ 2. Handle device status │
│ 3. Extract SMS data     │
│ 4. Match provider       │
│ 5. Parse with regex     │
│ 6. Store in database    │
└────────┬────────────────┘
         │
         ↓
┌─────────────────┐
│ Response        │
│ {"status":true} │
└─────────────────┘
```

## SMS Processing Logic

### 1. **Provider Matching**

The handler first identifies the MFS provider:

```php
// Exact match in 'from' field
if (array_key_exists($from, $mfs_providers)) {
    $matchedFullName = $mfs_providers[$from];
}

// Partial match in 'from' or 'text'
foreach ($mfs_providers as $short => $full) {
    if (stripos($from, $short) !== false || stripos($text, $short) !== false) {
        $matchedFullName = $full;
        break;
    }
}
```

### 2. **SMS Parsing**

Once provider is matched, the handler tries all configured regex patterns:

```php
foreach ($provider_formats[$matchedFullName] as $formatData) {
    if (preg_match($formatData['format'], $text, $matches)) {
        // Extract data from named groups
        $amount = $matches['amount'];
        $mobile = $matches['mobile'];
        $trxid = $matches['trxid'];
        // ... etc
        
        $sms_status = "approved"; // Successfully parsed
        break;
    }
}
```

### 3. **Data Storage**

Parsed data is stored in the `sms_data` table:

```php
insertData($db_prefix . 'sms_data', [
    'entry_type' => 'automatic',
    'sim' => 'sim1',
    'payment_method' => 'bKash',
    'mobile_number' => '01712345678',
    'transaction_id' => 'ABC123XYZ',
    'amount' => '1000.00',
    'balance' => '5000.00',
    'message' => 'Full SMS text...',
    'status' => 'approved', // or 'review' if parsing failed
    'created_at' => '2025-10-15 14:30:00'
]);
```

## Status Codes

- **`approved`**: SMS was successfully parsed using a regex pattern
- **`review`**: Provider matched but SMS couldn't be parsed (manual review needed)
- **`false`**: No provider matched (not stored in database)

## Hooks & Filters

### Available Hooks

```php
// Called after SMS is processed
pp_trigger_hook('mfs_after_webhook_process', $result);
```

### Using Hooks in Other Plugins

```php
add_action('mfs_after_webhook_process', 'my_custom_function');

function my_custom_function($result) {
    if ($result['parsed'] && $result['sms_status'] === 'approved') {
        // Send notification
        // Update order status
        // etc.
    }
}
```

## Debugging

### Enable Debug Mode

Add this to your webhook handler for debugging:

```php
// In webhook-handler.php, add at the top of mfs_process_webhook():
error_log("MFS Webhook Debug: " . print_r($webhook_data, true));
error_log("Matched Provider: " . $matchedFullName);
error_log("Parsed Data: " . print_r($matched_data, true));
```

### Check Logs

- Check your PHP error log
- Review database `sms_data` table for entries with status = "review"
- Test regex patterns using the built-in tester

## Troubleshooting

### Webhook not being processed

1. **Check if module is active**: Admin Panel → Plugins → ensure MFS Provider Manager is enabled
2. **Verify webhook key**: Ensure the `?webhook=key` parameter matches your settings
3. **Check user agent**: Must be `mh-piprapay-api-key`
4. **Review provider list**: Make sure provider is added in module settings

### SMS status is "review" (not parsed)

1. **Test your regex**: Use the pattern tester in admin UI
2. **Check SMS format**: Compare actual SMS with expected format
3. **Escape special characters**: Use `\` before special regex characters
4. **Handle newlines**: The handler converts `\n` to `\s*` automatically

### Provider not matching

1. **Add variations**: Add multiple short names for the same provider
2. **Check case sensitivity**: Provider matching is case-insensitive
3. **Add to both places**: Check 'from' field AND message text

## Security Notes

- ✅ Webhook key validation is performed
- ✅ User agent verification (`mh-piprapay-api-key`)
- ✅ SQL injection prevention using `escape_string()`
- ✅ No direct file execution allowed
- ✅ All inputs are sanitized before database insertion

## Version History

- **v1.0.3**: Added comprehensive webhook handler with zero core modification
- **v1.0.2**: Enhanced provider management
- **v1.0.1**: Initial release

## Support

For issues or questions:
- GitHub: https://github.com/dblx98/MFS-Provider-Manager
- Author: Saimun Bepari
- Website: https://saimun.dev/

## License

GPL-2.0+ - Same as PipraPay
