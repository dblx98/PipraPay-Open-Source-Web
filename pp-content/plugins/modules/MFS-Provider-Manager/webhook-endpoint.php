<?php
/**
 * MFS Provider Manager - Webhook Endpoint
 * 
 * This file serves as an alternative entry point for webhook requests
 * when using .htaccess URL rewriting.
 * 
 * SETUP INSTRUCTIONS FOR .HTACCESS METHOD:
 * Add these lines to your .htaccess file (before other RewriteRules):
 * 
 * # MFS Provider Manager - Automatic Webhook Handler
 * RewriteCond %{QUERY_STRING} webhook=([^&]+)
 * RewriteCond %{REQUEST_URI} ^/$ [OR]
 * RewriteCond %{REQUEST_URI} ^/index\.php$
 * RewriteRule ^ pp-content/plugins/modules/mfs-provider-manager/webhook-endpoint.php [L]
 * 
 * @package MFS Provider Manager
 * @version 1.0.3
 * @author Saimun Bepari
 */

// =============================================================================
// STEP 1: Load configuration
// =============================================================================

$config_file = __DIR__ . '/../../../../pp-config.php';
if (!file_exists($config_file)) {
    http_response_code(500);
    die(json_encode([
        'status' => 'false', 
        'message' => 'Configuration file not found',
        'debug' => [
            '__DIR__' => __DIR__,
            'config_path' => $config_file,
            'config_realpath' => realpath($config_file),
            'config_exists' => file_exists($config_file),
            'parent_dir' => dirname(__DIR__),
            'server_document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'N/A',
            'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'N/A'
        ]
    ], JSON_PRETTY_PRINT));
}

require_once $config_file;

// =============================================================================
// STEP 2: Load core files
// =============================================================================

$controller_file = __DIR__ . '/../../../../pp-include/pp-controller.php';
if (!file_exists($controller_file)) {
    http_response_code(500);
    die(json_encode([
        'status' => 'false', 
        'message' => 'Controller not found',
        'debug' => [
            '__DIR__' => __DIR__,
            'controller_path' => $controller_file,
            'controller_realpath' => realpath($controller_file),
            'controller_exists' => file_exists($controller_file)
        ]
    ], JSON_PRETTY_PRINT));
}
require_once $controller_file;

$model_file = __DIR__ . '/../../../../pp-include/pp-model.php';
if (!file_exists($model_file)) {
    http_response_code(500);
    die(json_encode([
        'status' => 'false', 
        'message' => 'Model not found',
        'debug' => [
            '__DIR__' => __DIR__,
            'model_path' => $model_file,
            'model_realpath' => realpath($model_file),
            'model_exists' => file_exists($model_file)
        ]
    ], JSON_PRETTY_PRINT));
}
require_once $model_file;

// =============================================================================
// STEP 3: Verify module is active
// =============================================================================

$response = json_decode(getData($db_prefix.'plugins', 'WHERE plugin_slug="mfs-provider-manager" AND status="active"'), true);

if ($response['status'] != true) {
    http_response_code(403);
    die(json_encode(['status' => 'false', 'message' => 'MFS Provider Manager is not active']));
}

// =============================================================================
// STEP 4: Load module files
// =============================================================================

$functions_file = __DIR__ . '/functions.php';
if (!file_exists($functions_file)) {
    http_response_code(500);
    die(json_encode(['status' => 'false', 'message' => 'Module functions not found']));
}
require_once $functions_file;

$webhook_handler_file = __DIR__ . '/webhook-handler.php';
if (!file_exists($webhook_handler_file)) {
    http_response_code(500);
    die(json_encode(['status' => 'false', 'message' => 'Webhook handler not found']));
}
require_once $webhook_handler_file;

// =============================================================================
// STEP 5: Process webhook
// =============================================================================

$webhook_key = $_GET['webhook'] ?? '';

if ($webhook_key == '') {
    http_response_code(400);
    die(json_encode(['status' => 'false', 'message' => 'Webhook key missing']));
}

// Process the webhook
// Note: This function validates, processes, and exits
mfs_standalone_webhook_handler($webhook_key);
