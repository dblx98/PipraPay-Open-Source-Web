# PipraPay - Modular Architecture Guide

## Overview
PipraPay now uses a modular architecture where business logic is separated into specialized handler files instead of being embedded in the main `index.php` entry point.

## Architecture

### Entry Point Flow
```
index.php (unchanged)
  ↓
  includes pp-config.php
  ↓
  includes pp-controller.php (helper functions)
  ↓
  includes pp-model.php (database operations)
  ↓
  includes handlers (webhook, routing, etc.)
  ↓
  handlers process requests and exit early
```

### Handler Files

All handler files are located in `/pp-include/` directory:

#### `pp-webhook-handler.php`
Handles SMS gateway webhooks from the PipraPay mobile application.

**Endpoint**: `/?webhook={token}`

**Example**:
```bash
curl -X POST "https://yourdomain.com/?webhook=your_webhook_token" \
  -H "User-Agent: mh-piprapay-api-key" \
  -d "from=bKash&text=Cash In Tk 1000.00 from 01234567890 successful..."
```

**Response**:
```json
{
  "status": "true",
  "message": "Device Connected"
}
```

#### `pp-request-router.php`
Handles cron jobs and default page routing.

**Cron Job Endpoint**: `/?cron=1`

**Example**:
```bash
curl "https://yourdomain.com/?cron=1"
```

**Response**:
```json
{
  "status": "false",
  "message": "Direct access not allowed"
}
```

## Adding New Handlers

To add a new handler without modifying `index.php`:

1. Create your handler file in `/pp-include/` directory:

```php
<?php
    if (!defined('pp_allowed_access')) {
        die('Direct access not allowed');
    }

    // Check for your condition
    if(isset($_GET['your_parameter'])){
        // Your logic here
        
        // Always exit to prevent further execution
        exit();
    }
?>
```

2. Include your handler in `/pp-include/pp-model.php` at the end:

```php
if (file_exists(__DIR__.'/your-handler.php')) {
    include(__DIR__.'/your-handler.php');
}
```

3. Your handler will now be automatically loaded and executed!

## Supported SMS Providers

The webhook handler automatically parses SMS messages from:

- **bKash** (5 format variations)
- **Nagad** (2 format variations)  
- **Rocket/DBBL** (2 format variations)
- **Upay** (2 format variations)
- **Tap**
- **OkWallet**
- **Cellfin**
- **Ipay**
- **Pathao Pay**

### Adding New SMS Provider

To add support for a new payment provider:

1. Edit `/pp-include/pp-webhook-handler.php`
2. Add provider to the `$mfs_providers` array:

```php
$mfs_providers = [
    // ... existing providers ...
    'NewProvider' => 'New Provider Name'
];
```

3. Add SMS format patterns to `$provider_formats` array:

```php
'New Provider Name' => [
    [
        'type' => 'sms1',
        'format' => '/Your regex pattern here with (?<amount>...) (?<mobile>...) (?<trxid>...) etc./'
    ]
]
```

## Plugin System Integration

Handlers can trigger plugin hooks for extensibility:

```php
// In your handler
if (function_exists('pp_trigger_hook')) {
    pp_trigger_hook('your_custom_hook', $param1, $param2);
}
```

Plugins can then register callbacks:

```php
// In plugin functions.php
add_action('your_custom_hook', 'my_callback_function');

function my_callback_function($param1, $param2) {
    // Your plugin logic
}
```

## Security Best Practices

1. **Always validate** `pp_allowed_access` constant at the start of handlers
2. **Sanitize inputs** using `escape_string()` function
3. **Use database helpers** from pp-controller.php instead of raw SQL
4. **Validate webhook tokens** against database before processing
5. **Check user agent** for webhook requests (should be `mh-piprapay-api-key`)

## Debugging

Enable PHP error logging in `/pp-config.php`:

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/error.log');
```

Then check `/error.log` for handler execution details.

## Benefits of Modular Architecture

✅ **No index.php modifications needed** - Add features without touching the entry point  
✅ **Better organization** - Each handler has a single, clear responsibility  
✅ **Easier debugging** - Issues are isolated to specific handler files  
✅ **Team collaboration** - Multiple developers can work on different handlers  
✅ **Version control friendly** - Changes are isolated and easier to review  
✅ **Testing friendly** - Handlers can be tested independently  

## Migration Notes

The original webhook and routing logic in `index.php` (lines 23-262) is now dead code. It will never execute because handlers process requests and exit early. This dead code can be safely removed in a future update.

For now, it's kept for:
- Code archaeology (understanding old logic)
- Emergency rollback capability
- Gradual team adoption

## Support

For issues or questions about the modular architecture:
1. Check `/docs/REFACTORING_NOTES.md` for technical details
2. Review handler files in `/pp-include/` directory
3. Consult `/docs/PipraPay-Module-Plugin-Developer-Guide.md` for plugin development

---

**Architecture Version**: 2.0  
**Last Updated**: 2025-10-15  
**Compatibility**: PipraPay v1.0.0+
