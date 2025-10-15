<?php
/**
 * MFS Provider Manager - Auto Bootstrap
 * 
 * This file provides automatic webhook interception using PHP's execution flow.
 * It works by checking if this is a webhook request and taking over processing
 * BEFORE the main webhook handler in index.php runs.
 * 
 * HOW IT WORKS:
 * 1. This file is loaded by functions.php when the module is active
 * 2. It checks if current request is a webhook (?webhook=xxx)
 * 3. If yes, it processes immediately and exits
 * 4. If no, it does nothing and lets normal flow continue
 * 
 * ZERO MODIFICATIONS NEEDED to index.php or any other file!
 * 
 * @package MFS Provider Manager
 * @version 1.0.3
 * @author Saimun Bepari
 */

if (!defined('pp_allowed_access')) {
    // This file is being loaded early, before pp_allowed_access is defined
    // We'll handle this carefully
}

/**
 * Check if this is a webhook request and handle it
 * This function runs immediately when the file is included
 */
function mfs_auto_intercept_webhook() {
    // Only run if this is a webhook request
    if (!isset($_GET['webhook'])) {
        return; // Not a webhook, do nothing
    }
    
    // Check if we have the necessary functions available
    if (!function_exists('escape_string') || !function_exists('getData')) {
        return; // Core functions not loaded yet, can't proceed
    }
    
    // Check if webhook handler is available
    $webhook_handler = __DIR__ . '/webhook-handler.php';
    if (!file_exists($webhook_handler)) {
        return; // Webhook handler not found
    }
    
    // Load the webhook handler
    require_once $webhook_handler;
    
    // Get the webhook key
    $webhook_key = escape_string($_GET['webhook']);
    
    if ($webhook_key == "") {
        return; // Empty webhook, let default handler deal with it
    }
    
    // Verify this module is active before processing
    global $db_prefix;
    $response = json_decode(getData($db_prefix.'plugins', 'WHERE plugin_slug="mfs-provider-manager" AND status="active"'), true);
    
    if ($response['status'] != true) {
        return; // Module not active, don't intercept
    }
    
    // All checks passed - take over webhook processing
    mfs_standalone_webhook_handler($webhook_key);
    // Note: The standalone handler calls exit() so code won't continue
}

// Attempt auto-interception (will only work if called at the right time)
if (isset($_GET['webhook']) && function_exists('escape_string') && function_exists('getData')) {
    mfs_auto_intercept_webhook();
}
