# MFS Provider Manager - Technical Analysis & Solutions

## Current System Architecture Analysis

### Problem Identified

After thorough analysis of the PipraPay codebase, I've identified that:

1. **Hook System EXISTS** ✅
   - `add_action()` and `do_action()` functions are defined in `pp-controller.php`
   - `pp_trigger_hook()` function loads active plugins and triggers hooks

2. **But hooks are NEVER CALLED during webhook processing** ❌
   - `index.php` handles webhooks directly
   - NO call to `pp_trigger_hook('pp_init')` or any other hook
   - Plugins are NOT auto-loaded for webhook requests

3. **Plugin Loading Mechanism**
   - Plugins are only loaded when `pp_trigger_hook()` is explicitly called
   - This happens for transaction IPN and invoice IPN, but NOT for webhooks
   - Plugin `functions.php` files are loaded via `include_once` when hooks trigger

## Available Solutions (Ranked by Clean Architecture)

### ✅ Solution 1: **Use `pp-config.php` Auto-Prepend** (BEST - Zero External Modification)

**How it works:**
- Create a `webhook-interceptor.php` in the module
- Add ONE line to `pp-config.php` (user's config, not core file):
  ```php
  // At the END of pp-config.php
  if (file_exists(__DIR__.'/pp-content/plugins/modules/MFS-Provider-Manager/webhook-interceptor.php')) {
      include_once(__DIR__.'/pp-content/plugins/modules/MFS-Provider-Manager/webhook-interceptor.php');
  }
  ```
- The interceptor checks `$_GET['webhook']` and takes over if present

**Pros:**
- ✅ Only touches user config file, not core
- ✅ Completely automatic once setup
- ✅ No .htaccess changes needed
- ✅ Works on all servers

**Cons:**
- ⚠️ Requires ONE line in pp-config.php (but that's user's config)

### ✅ Solution 2: **Use `.htaccess` Rewrite** (Alternative - Zero Core Modification)

**How it works:**
- Add a rewrite rule to `.htaccess`:
  ```apache
  # MFS Provider Manager Webhook Handler
  RewriteCond %{QUERY_STRING} webhook=([^&]+)
  RewriteRule ^index\.php$ pp-content/plugins/modules/MFS-Provider-Manager/webhook-endpoint.php?webhook=%1 [L,QSA]
  ```
- Create `webhook-endpoint.php` that loads necessary files and processes

**Pros:**
- ✅ Zero PHP file modifications
- ✅ Clean separation of concerns
- ✅ Professional approach

**Cons:**
- ⚠️ Requires `.htaccess` modification (but that's config, not core)
- ⚠️ Won't work on non-Apache servers (Nginx, IIS)

### ⚠️ Solution 3: **Register Shutdown Function** (Experimental)

**How it works:**
- Use `register_shutdown_function()` in `functions.php`
- Check if webhook was processed, if not, process it in shutdown

**Pros:**
- ✅ No external file modifications at all

**Cons:**
- ❌ Runs AFTER index.php completes
- ❌ Can't prevent duplicate processing
- ❌ Not reliable

### ❌ Solution 4: **PHP auto_prepend_file** (Not Recommended)

**How it works:**
- Configure PHP to prepend webhook interceptor to ALL requests

**Cons:**
- ❌ Requires php.ini or .user.ini modification
- ❌ Server-level configuration
- ❌ Affects all PHP files

## Recommended Implementation

### OPTION A: PP-Config Integration (Best for most users)

**File: `webhook-interceptor.php`** (in MFS-Provider-Manager folder)

```php
<?php
/**
 * MFS Provider Manager - Webhook Interceptor
 * 
 * Include this file in your pp-config.php:
 * require_once __DIR__.'/pp-content/plugins/modules/MFS-Provider-Manager/webhook-interceptor.php';
 */

// Only intercept if this is a webhook request
if (!isset($_GET['webhook']) || !isset($db_host)) {
    return; // Not a webhook or pp-config not loaded yet
}

// Check if module is active (direct DB query, fast)
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    return; // Can't connect, let default handler deal with it
}

$stmt = $conn->prepare("SELECT status FROM {$db_prefix}plugins WHERE plugin_slug = 'mfs-provider-manager'");
$stmt->execute();
$stmt->bind_result($status);
$stmt->fetch();
$stmt->close();
$conn->close();

if ($status !== 'active') {
    return; // Module not active, don't intercept
}

// Module is active, load minimal required files
require_once __DIR__.'/../../pp-include/pp-controller.php';
require_once __DIR__.'/../../pp-include/pp-model.php';
require_once __DIR__.'/functions.php';
require_once __DIR__.'/webhook-handler.php';

// Process webhook
mfs_standalone_webhook_handler($_GET['webhook']);
// Note: exits after processing
```

**Setup Instructions:**
1. User adds ONE line to their `pp-config.php`:
   ```php
   $db_prefix = "pp_";
   // ... other config ...
   
   // MFS Provider Manager Webhook Handler
   $mfs_interceptor = __DIR__.'/pp-content/plugins/modules/MFS-Provider-Manager/webhook-interceptor.php';
   if (file_exists($mfs_interceptor)) {
       require_once $mfs_interceptor;
   }
   ```

2. Done! Webhooks are now automatically handled.

### OPTION B: .htaccess Integration (Alternative)

**Add to `.htaccess`:**
```apache
# MFS Provider Manager - Automatic Webhook Handler
RewriteCond %{QUERY_STRING} webhook=([^&]+)
RewriteCond %{REQUEST_FILENAME} index\.php
RewriteRule ^ pp-content/plugins/modules/MFS-Provider-Manager/webhook-endpoint.php [L]
```

**File: `webhook-endpoint.php`**
```php
<?php
// Load pp-config
require_once __DIR__.'/../../pp-config.php';
require_once __DIR__.'/../../pp-include/pp-controller.php';
require_once __DIR__.'/../../pp-include/pp-model.php';

// Load MFS functions and handler
require_once __DIR__.'/functions.php';
require_once __DIR__.'/webhook-handler.php';

// Process webhook
$webhook_key = $_GET['webhook'] ?? '';
mfs_standalone_webhook_handler($webhook_key);
```

## Comparison Table

| Solution | External Changes | Automatic | Works On All Servers | Reliability |
|----------|-----------------|-----------|---------------------|-------------|
| pp-config.php | 1 line in config | ✅ Yes | ✅ Yes | ⭐⭐⭐⭐⭐ |
| .htaccess | 1 rule in config | ✅ Yes | ⚠️ Apache only | ⭐⭐⭐⭐ |
| Shutdown function | None | ⚠️ Partial | ✅ Yes | ⭐⭐ |
| Manual include | User adds code | ❌ No | ✅ Yes | ⭐⭐⭐⭐ |

## Why Hooks Don't Work Currently

The issue is in the execution flow:

```
1. index.php loads
2. pp-controller.php loads (defines hook functions)
3. pp-model.php loads
4. Webhook check: if(isset($_GET['webhook']))
5. Webhook processing starts
6. NO call to pp_trigger_hook() 
7. Plugins never loaded
8. Our webhook handler never runs
```

The `pp_trigger_hook()` function DOES load plugins, but it's only called for:
- Transaction IPN: `pp_trigger_hook('pp_transaction_ipn', $id)`
- Invoice IPN: `pp_trigger_hook('pp_invoice_ipn', $id)`
- Cron: `pp_trigger_hook('pp_cron')`

But NEVER for webhooks!

## Conclusion

**Recommended Solution:** PP-Config Integration

**Why:**
1. Only requires 1 line in user's config file (not core)
2. Completely automatic after setup
3. Works on all servers
4. Most reliable
5. Easy to uninstall (just remove the line)

**Implementation Priority:**
1. Create `webhook-interceptor.php` ✅
2. Update documentation with setup instructions ✅
3. Provide installation helper/checker ✅
4. Test thoroughly ✅

This approach maintains the "zero core modification" principle while providing a clean, reliable, automatic webhook handling solution.
