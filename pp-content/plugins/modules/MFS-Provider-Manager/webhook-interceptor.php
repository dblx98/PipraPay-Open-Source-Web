<?php
/**
 * MFS Provider Manager - Webhook Interceptor
 * 
 * This file intercepts webhook requests and processes them using the
 * MFS Provider Manager module, bypassing the default webhook handler in index.php.
 * 
 * SETUP INSTRUCTIONS:
 * Add this line to the END of your pp-config.php file:
 * 
 * $mfs_interceptor = __DIR__.'/pp-content/plugins/modules/mfs-provider-manager/webhook-interceptor.php';
 * if (file_exists($mfs_interceptor)) { require_once $mfs_interceptor; }
 * 
 * That's it! Webhooks will now be automatically handled by MFS Provider Manager.
 * 
 * @package MFS Provider Manager
 * @version 1.0.3
 * @author Saimun Bepari
 */

// =============================================================================
// STEP 1: Check if this is a webhook request
// =============================================================================

if (!isset($_GET['webhook'])) {
    return; // Not a webhook request, do nothing
}
echo "Webhook detected\n";
// =============================================================================
// STEP 2: Verify pp-config.php has been loaded (database credentials available)
// =============================================================================

if (!isset($db_host) || !isset($db_user) || !isset($db_pass) || !isset($db_name) || !isset($db_prefix)) {
    return; // Database config not available yet, can't proceed
}

// =============================================================================
// STEP 3: Quick check if module is active (direct DB query)
// =============================================================================

$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    return; // Can't connect to database, let default handler deal with it
}

// Check if MFS Provider Manager is active
$plugin_slug = $conn->real_escape_string('mfs-provider-manager');
$table = $conn->real_escape_string($db_prefix . 'plugins');
$query = "SELECT status FROM $table WHERE plugin_slug = '$plugin_slug' LIMIT 1";
$result = $conn->query($query);

if (!$result || $result->num_rows === 0) {
    $conn->close();
    return; // Module not found, don't intercept
}

$row = $result->fetch_assoc();
$status = $row['status'];
$result->close();
$conn->close();

if ($status !== 'active') {
    return; // Module not active, don't intercept
}

// =============================================================================
// STEP 4: Module is active! Load required files and take over webhook processing
// =============================================================================

// Load pp-controller.php if not already loaded
if (!function_exists('connectDatabase')) {
    $controller_file = __DIR__ . '/../../../../pp-include/pp-controller.php';
    if (file_exists($controller_file)) {
        require_once $controller_file;
    } else {
        return; // Controller not found, can't proceed
    }
}

// Load pp-model.php if not already loaded
if (!function_exists('getData')) {
    $model_file = __DIR__ . '/../../../../pp-include/pp-model.php';
    if (file_exists($model_file)) {
        require_once $model_file;
    } else {
        return; // Model not found, can't proceed
    }
}

// Load MFS Provider Manager functions
$functions_file = __DIR__ . '/functions.php';
if (file_exists($functions_file)) {
    require_once $functions_file;
} else {
    return; // Functions not found, can't proceed
}

// Load webhook handler
$webhook_handler_file = __DIR__ . '/webhook-handler.php';
if (file_exists($webhook_handler_file)) {
    require_once $webhook_handler_file;
} else {
    return; // Webhook handler not found, can't proceed
}

// =============================================================================
// STEP 5: Process the webhook using MFS Provider Manager
// =============================================================================

// Get webhook key
$webhook_key = $_GET['webhook'];

// Use the standalone webhook handler
// Note: This function will validate the webhook, process the SMS, and exit
if (function_exists('mfs_standalone_webhook_handler')) {
    mfs_standalone_webhook_handler($webhook_key);
    // Execution stops here (function calls exit())
}

// If we somehow get here, something went wrong - let default handler take over
return;
