<?php
/**
 * MFS Provider Manager - Webhook Handler
 * 
 * This file provides webhook handling capabilities using the MFS Provider Manager module.
 * It intercepts webhook requests and processes them using custom providers and formats
 * configured in the module, without modifying any core files.
 * 
 * @package MFS Provider Manager
 * @version 1.0.3
 * @author Saimun Bepari
 */

if (!defined('pp_allowed_access')) {
    die('Direct access not allowed');
}

/**
 * Main webhook handler function
 * Processes incoming SMS webhook data using MFS Provider Manager settings
 * 
 * @param array $webhook_data Contains webhook request data
 * @return array Response with status and message
 */
function mfs_process_webhook($webhook_data = []) {
    global $db_prefix;
    
    // Extract data from webhook_data or fallback to POST/decoded JSON
    $from = $webhook_data['from'] ?? '';
    $text = $webhook_data['text'] ?? '';
    $sentStamp = $webhook_data['sentStamp'] ?? '';
    $receivedStamp = $webhook_data['receivedStamp'] ?? '';
    $sim = $webhook_data['sim'] ?? '';
    
    // Normalize SIM value
    if ($sim == 1) {
        $sim = "sim1";
    } else if ($sim == 2) {
        $sim = "sim2";
    }
    
    // Get custom providers from MFS Provider Manager
    $mfs_providers = mfs_get_providers();
    
    $matchFound = false;
    $matchedFullName = '';
    
    // Match provider - first check exact key match
    if (array_key_exists($from, $mfs_providers)) {
        $matchFound = true;
        $matchedFullName = $mfs_providers[$from];
    } else {
        // Then check partial match in 'from' or 'text'
        foreach ($mfs_providers as $short => $full) {
            if (stripos($from, $short) !== false || stripos($text, $short) !== false) {
                $matchFound = true;
                $matchedFullName = $full;
                break;
            }
        }
    }
    
    if (!$matchFound) {
        return [
            'status' => false,
            'message' => 'No matching provider found',
            'provider' => null
        ];
    }
    
    // Provider matched, now parse the SMS
    $sms_status = "review"; // Default status if parsing fails
    
    // Get custom provider formats from MFS Provider Manager
    $provider_formats = mfs_get_provider_formats();
    
    // Normalize newlines in formats for matching
    foreach ($provider_formats as &$formats) {
        foreach ($formats as &$entry) {
            if (isset($entry['format'])) {
                $entry['format'] = str_replace('\n', '\s*', $entry['format']);
            }
        }
    }
    
    // Initialize matched data with defaults
    $matched_data = [
        'amount' => '0',
        'mobile' => '--',
        'trxid' => '--',
        'balance' => '0',
        'datetime' => $receivedStamp
    ];
    
    $parsed = false;
    
    // Try to match SMS text against provider formats
    if (isset($provider_formats[$matchedFullName])) {
        foreach ($provider_formats[$matchedFullName] as $formatData) {
            if (preg_match($formatData['format'], $text, $matches)) {
                $parsed = true;
                
                // Extract matched data
                $matched_data['amount'] = str_replace(',', '', $matches['amount'] ?? '0');
                $matched_data['mobile'] = $matches['mobile'] ?? '--';
                $matched_data['trxid'] = $matches['trxid'] ?? '--';
                $matched_data['balance'] = str_replace(',', '', $matches['balance'] ?? '0');
                
                // Handle datetime
                if (isset($matches['datetime'])) {
                    $matched_data['datetime'] = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $matches['datetime'])));
                } elseif (isset($matches['date']) && isset($matches['time'])) {
                    $matched_data['datetime'] = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $matches['date']) . ' ' . $matches['time']));
                }
                
                // Successfully parsed
                $sms_status = "approved";
                break;
            }
        }
    }
    
    // Prepare data for insertion
    $columns = [
        'entry_type', 
        'sim', 
        'payment_method', 
        'mobile_number', 
        'transaction_id', 
        'amount', 
        'balance', 
        'message', 
        'status', 
        'created_at'
    ];
    
    $values = [
        'automatic',
        $sim,
        $matchedFullName,
        $matched_data['mobile'],
        $matched_data['trxid'],
        $matched_data['amount'],
        $matched_data['balance'],
        $text,
        $sms_status,
        $matched_data['datetime']
    ];
    
    // Insert into database
    if (function_exists('insertData')) {
        insertData($db_prefix . 'sms_data', $columns, $values);
    }
    
    return [
        'status' => true,
        'message' => 'SMS processed successfully',
        'provider' => $matchedFullName,
        'parsed' => $parsed,
        'sms_status' => $sms_status,
        'data' => $matched_data
    ];
}

/**
 * Hook into webhook processing
 * This function is called when a webhook is received
 */
function mfs_handle_webhook_request() {
    global $db_prefix;
    
    // Check if this is a webhook request
    if (!isset($_GET['webhook'])) {
        return;
    }
    
    $webhook = function_exists('escape_string') ? escape_string($_GET['webhook']) : $_GET['webhook'];
    
    if ($webhook == "") {
        return;
    }
    
    // Verify webhook exists in database
    if (!function_exists('getData')) {
        return;
    }
    
    $response = json_decode(getData($db_prefix.'settings','WHERE webhook="'.$webhook.'"'), true);
    
    if ($response['status'] != true) {
        return;
    }
    
    // Handle device pairing/status
    $d_status = "Pairing";
    if (isset($_POST['d_model']) && isset($_POST['d_brand']) && isset($_POST['d_version']) && isset($_POST['d_api_level'])) {
        $d_model = function_exists('escape_string') ? escape_string($_POST['d_model']) : $_POST['d_model'];
        $d_brand = function_exists('escape_string') ? escape_string($_POST['d_brand']) : $_POST['d_brand'];
        $d_version = function_exists('escape_string') ? escape_string($_POST['d_version']) : $_POST['d_version'];
        $d_api_level = function_exists('escape_string') ? escape_string($_POST['d_api_level']) : $_POST['d_api_level'];
        
        $response_device = json_decode(getData($db_prefix.'devices','WHERE d_model="'.$d_model.'" AND  d_brand="'.$d_brand.'" AND  d_version="'.$d_version.'" AND  d_api_level="'.$d_api_level.'" '), true);
        
        if ($response_device['status'] == true) {
            if (isset($_POST['connection_status'])) {
                $d_status = function_exists('escape_string') ? escape_string($_POST['connection_status']) : $_POST['connection_status'];
            } else {
                $d_status = "Connected";
            }
            
            $columns = ['d_status', 'created_at'];
            $values = [$d_status, function_exists('getCurrentDatetime') ? getCurrentDatetime('Y-m-d H:i:s') : date('Y-m-d H:i:s')];
            $condition = "id = '".$response_device['response'][0]['id']."'"; 
            
            if (function_exists('updateData')) {
                updateData($db_prefix.'devices', $columns, $values, $condition);
            }
        } else {
            $columns = ['d_id', 'd_model', 'd_brand', 'd_version', 'd_api_level', 'd_status', 'created_at'];
            $values = [rand(), $d_model, $d_brand, $d_version, $d_api_level, 'Connected', function_exists('getCurrentDatetime') ? getCurrentDatetime('Y-m-d H:i:s') : date('Y-m-d H:i:s')];
            
            if (function_exists('insertData')) {
                insertData($db_prefix.'devices', $columns, $values);
            }
        }
    }
    
    // Get payload
    $payload = file_get_contents('php://input');
    $decoded = json_decode($payload, true);
    
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Process SMS data only if correct user agent
    if ($userAgent === 'mh-piprapay-api-key') {
        $webhook_data = [
            'from' => $decoded['from'] ?? ($_POST['from'] ?? ''),
            'text' => $decoded['text'] ?? ($_POST['text'] ?? ''),
            'sentStamp' => $decoded['sentStamp'] ?? ($_POST['sentStamp'] ?? ''),
            'receivedStamp' => $decoded['receivedStamp'] ?? ($_POST['receivedStamp'] ?? ''),
            'sim' => $decoded['sim'] ?? ($_POST['sim'] ?? '')
        ];
        
        // Process the webhook using MFS Provider Manager
        $result = mfs_process_webhook($webhook_data);
        
        // Allow other plugins to hook into webhook processing
        if (function_exists('pp_trigger_hook')) {
            pp_trigger_hook('mfs_after_webhook_process', $result);
        }
    }
    
    // Send response and exit
    echo json_encode(['status' => "true", 'message' => "Device ".$d_status]);
    exit();
}

/**
 * Register webhook handler with plugin system
 * This allows the module to intercept webhook requests
 */
if (function_exists('add_action')) {
    // Hook into early init to handle webhooks before other processing
    add_action('pp_init', 'mfs_handle_webhook_request', 1);
}

/**
 * Standalone webhook processor function
 * Can be called directly from index.php or other files if needed
 * 
 * @param string $webhook The webhook key
 * @return void
 */
function mfs_standalone_webhook_handler($webhook_key) {
    global $db_prefix;
    
    $webhook = function_exists('escape_string') ? escape_string($webhook_key) : $webhook_key;
    
    if ($webhook == "") {
        echo json_encode(['status' => "false", 'message' => "Invalid webhook"]);
        exit();
    }
    
    // Verify webhook
    if (!function_exists('getData')) {
        echo json_encode(['status' => "false", 'message' => "System error"]);
        exit();
    }
    
    $response = json_decode(getData($db_prefix.'settings','WHERE webhook="'.$webhook.'"'), true);
    
    if ($response['status'] != true) {
        echo json_encode(['status' => "false", 'message' => "Invalid Webhook"]);
        exit();
    }
    
    // Process device info
    $d_status = "Pairing";
    if (isset($_POST['d_model']) && isset($_POST['d_brand']) && isset($_POST['d_version']) && isset($_POST['d_api_level'])) {
        $d_model = function_exists('escape_string') ? escape_string($_POST['d_model']) : $_POST['d_model'];
        $d_brand = function_exists('escape_string') ? escape_string($_POST['d_brand']) : $_POST['d_brand'];
        $d_version = function_exists('escape_string') ? escape_string($_POST['d_version']) : $_POST['d_version'];
        $d_api_level = function_exists('escape_string') ? escape_string($_POST['d_api_level']) : $_POST['d_api_level'];
        
        $response_device = json_decode(getData($db_prefix.'devices','WHERE d_model="'.$d_model.'" AND  d_brand="'.$d_brand.'" AND  d_version="'.$d_version.'" AND  d_api_level="'.$d_api_level.'" '), true);
        
        if ($response_device['status'] == true) {
            if (isset($_POST['connection_status'])) {
                $d_status = function_exists('escape_string') ? escape_string($_POST['connection_status']) : $_POST['connection_status'];
            } else {
                $d_status = "Connected";
            }
            
            $columns = ['d_status', 'created_at'];
            $values = [$d_status, function_exists('getCurrentDatetime') ? getCurrentDatetime('Y-m-d H:i:s') : date('Y-m-d H:i:s')];
            $condition = "id = '".$response_device['response'][0]['id']."'"; 
            
            if (function_exists('updateData')) {
                updateData($db_prefix.'devices', $columns, $values, $condition);
            }
        } else {
            $columns = ['d_id', 'd_model', 'd_brand', 'd_version', 'd_api_level', 'd_status', 'created_at'];
            $values = [rand(), $d_model, $d_brand, $d_version, $d_api_level, 'Connected', function_exists('getCurrentDatetime') ? getCurrentDatetime('Y-m-d H:i:s') : date('Y-m-d H:i:s')];
            
            if (function_exists('insertData')) {
                insertData($db_prefix.'devices', $columns, $values);
            }
        }
    }
    
    // Get payload
    $payload = file_get_contents('php://input');
    $decoded = json_decode($payload, true);
    
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Process SMS data
    if ($userAgent === 'mh-piprapay-api-key') {
        $webhook_data = [
            'from' => $decoded['from'] ?? ($_POST['from'] ?? ''),
            'text' => $decoded['text'] ?? ($_POST['text'] ?? ''),
            'sentStamp' => $decoded['sentStamp'] ?? ($_POST['sentStamp'] ?? ''),
            'receivedStamp' => $decoded['receivedStamp'] ?? ($_POST['receivedStamp'] ?? ''),
            'sim' => $decoded['sim'] ?? ($_POST['sim'] ?? '')
        ];
        
        // Process webhook
        $result = mfs_process_webhook($webhook_data);
    }
    
    echo json_encode(['status' => "true", 'message' => "Device ".$d_status]);
    exit();
}
