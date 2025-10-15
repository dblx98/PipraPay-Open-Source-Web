# MFS Provider Manager - Setup Instructions

## 🎯 Goal: Automatic Webhook Handling Without Core Modifications

This guide provides **2 methods** to enable automatic webhook handling. Choose the one that works best for your setup.

---

## ✅ Method 1: PP-Config Integration (RECOMMENDED)

**Best for:** All users, works on any server

### Step 1: Locate your `pp-config.php` file

The file is in your PipraPay root directory:
```
/path/to/piprapay/pp-config.php
```

### Step 2: Add webhook interceptor

Open `pp-config.php` and add these lines **at the very end** of the file:

```php
// ... existing config ...
$db_prefix = "pp_";

// =====================================================================
// MFS Provider Manager - Automatic Webhook Handler
// Add this at the END of pp-config.php
// =====================================================================
$mfs_interceptor = __DIR__.'/pp-content/plugins/modules/MFS-Provider-Manager/webhook-interceptor.php';
if (file_exists($mfs_interceptor)) {
    require_once $mfs_interceptor;
}
```

### Step 3: Test

Visit your webhook URL:
```
https://yourdomain.com/?webhook=your_webhook_key
```

Send a test SMS, and it should be processed by MFS Provider Manager!

### ✅ Verification

Check if it's working:
1. Go to **Admin Panel** → **SMS Data**
2. Send a test SMS to your webhook
3. Check if it appears with status "approved" or "review"

---

## ✅ Method 2: .htaccess Rewrite (ALTERNATIVE)

**Best for:** Apache servers, advanced users

### Step 1: Locate your `.htaccess` file

The file is in your PipraPay root directory:
```
/path/to/piprapay/.htaccess
```

### Step 2: Add rewrite rule

Open `.htaccess` and add these lines **BEFORE the existing RewriteRules**:

```apache
# =====================================================================
# MFS Provider Manager - Automatic Webhook Handler
# Add this BEFORE other RewriteRules
# =====================================================================
RewriteCond %{QUERY_STRING} webhook=([^&]+)
RewriteCond %{REQUEST_URI} ^/$ [OR]
RewriteCond %{REQUEST_URI} ^/index\.php$
RewriteRule ^ pp-content/plugins/modules/MFS-Provider-Manager/webhook-endpoint.php [L]
```

### Example `.htaccess` after modification:

```apache
RewriteEngine On

# =====================================================================
# MFS Provider Manager - Automatic Webhook Handler
# =====================================================================
RewriteCond %{QUERY_STRING} webhook=([^&]+)
RewriteCond %{REQUEST_URI} ^/$ [OR]
RewriteCond %{REQUEST_URI} ^/index\.php$
RewriteRule ^ pp-content/plugins/modules/MFS-Provider-Manager/webhook-endpoint.php [L]

# Original rules below
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME}\.php -f
RewriteRule ^(.*)$ $1.php [L]

# ... rest of your existing rules ...
```

### Step 3: Test

Visit your webhook URL and send a test SMS.

### ✅ Verification

Same as Method 1 - check SMS Data in admin panel.

---

## 🔍 Installation Checker

Visit this URL to check if setup is correct:
```
https://yourdomain.com/admin/plugins?page=modules--mfs-provider-manager
```

The admin panel will show:
- ✅ Module active
- ✅ Webhook interceptor status
- ✅ Configuration status

---

## 📊 Comparison

| Feature | PP-Config Method | .htaccess Method |
|---------|------------------|------------------|
| **Setup Complexity** | ⭐ Very Easy | ⭐⭐ Easy |
| **Server Compatibility** | ✅ All servers | ⚠️ Apache only |
| **Performance** | ⭐⭐⭐⭐⭐ Excellent | ⭐⭐⭐⭐⭐ Excellent |
| **File Modifications** | 1 (config only) | 1 (config only) |
| **Automatic** | ✅ Yes | ✅ Yes |
| **Easy to Remove** | ✅ Yes | ✅ Yes |

---

## 🚨 Troubleshooting

### Problem: Webhooks still using old handler

**Solution:**
1. Clear PHP opcache if enabled
2. Restart PHP-FPM or web server
3. Check file paths are correct

### Problem: "Module not found" error

**Solution:**
1. Verify module is activated in Admin Panel → Plugins
2. Check database table `pp_plugins` has entry with `plugin_slug='mfs-provider-manager'` and `status='active'`

### Problem: SMS not being parsed (status = "review")

**Solution:**
1. This is normal if SMS format doesn't match any pattern
2. Go to MFS Provider Manager admin page
3. Add new SMS format regex pattern
4. Test with the pattern tester

### Problem: .htaccess method not working

**Solution:**
1. Check if mod_rewrite is enabled: `php -i | grep mod_rewrite`
2. Verify .htaccess is being read: add a syntax error and see if site breaks
3. Try PP-Config method instead (works everywhere)

---

## 🔄 Uninstallation

### To Remove PP-Config Method:

1. Open `pp-config.php`
2. Remove the MFS Provider Manager section
3. Save file

### To Remove .htaccess Method:

1. Open `.htaccess`
2. Remove the MFS Provider Manager section
3. Save file

The default webhook handler in `index.php` will take over again.

---

## 💡 Advanced: Verify Setup Programmatically

Create a file `test-webhook-setup.php` in your root directory:

```php
<?php
// Test if MFS webhook interceptor is working
require_once 'pp-config.php';

echo "=== MFS Provider Manager Setup Test ===\n\n";

// Check if interceptor file exists
$interceptor = __DIR__.'/pp-content/plugins/modules/MFS-Provider-Manager/webhook-interceptor.php';
if (file_exists($interceptor)) {
    echo "✅ Webhook interceptor file found\n";
} else {
    echo "❌ Webhook interceptor file NOT found\n";
}

// Check if module is active
require_once 'pp-include/pp-controller.php';
require_once 'pp-include/pp-model.php';

$response = json_decode(getData($db_prefix.'plugins', 'WHERE plugin_slug="mfs-provider-manager"'), true);
if ($response['status'] == true) {
    $status = $response['response'][0]['status'];
    if ($status == 'active') {
        echo "✅ Module is ACTIVE\n";
    } else {
        echo "⚠️ Module found but status: $status\n";
    }
} else {
    echo "❌ Module NOT found in database\n";
}

// Check if functions are available
if (function_exists('mfs_process_webhook')) {
    echo "✅ MFS functions loaded\n";
} else {
    echo "❌ MFS functions NOT loaded\n";
}

echo "\n=== Test Complete ===\n";
```

Run it:
```bash
php test-webhook-setup.php
```

---

## 📚 What Happens Behind the Scenes

### PP-Config Method Flow:

```
1. Webhook request arrives: /?webhook=abc123
2. index.php starts loading
3. pp-config.php is included
4. MFS webhook interceptor runs (added at end of pp-config)
5. Interceptor checks: Is this webhook? Is module active?
6. If yes: Process immediately and exit
7. If no: Return and let index.php continue normally
```

### .htaccess Method Flow:

```
1. Webhook request arrives: /?webhook=abc123
2. Apache mod_rewrite intercepts
3. Rewrites to: pp-content/.../webhook-endpoint.php
4. Endpoint loads config, core files, and processes
5. Exits after processing
6. index.php never runs
```

---

## 🎉 Success Indicators

You'll know it's working when:

1. ✅ SMS appears in **SMS Data** automatically
2. ✅ Status is "approved" (if format matched) or "review" (if not matched)
3. ✅ Provider is correctly identified
4. ✅ Transaction details are extracted
5. ✅ No duplicate entries

---

## 🛟 Need Help?

- 📖 Read: `WEBHOOK-INTEGRATION-GUIDE.md`
- 🔬 Check: `TECHNICAL-ANALYSIS.md` for deep dive
- 💬 Issues: https://github.com/dblx98/MFS-Provider-Manager/issues
- 👨‍💻 Author: Saimun Bepari - https://saimun.dev/

---

## ✨ Summary

**Recommended Setup:**
1. Choose **PP-Config Method** (works everywhere)
2. Add 3 lines to `pp-config.php`
3. Test webhook
4. Done! 🎉

**Zero modifications** to:
- ❌ index.php
- ❌ pp-controller.php
- ❌ pp-model.php
- ❌ Any core files

**Only touched:**
- ✅ pp-config.php (your configuration file)

This is the cleanest possible implementation! 🚀
