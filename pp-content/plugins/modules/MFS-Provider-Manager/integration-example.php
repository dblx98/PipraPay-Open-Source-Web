<?php
/**
 * MFS Provider Manager - Integration Example
 * 
 * This file shows how to integrate the MFS Provider Manager webhook handler
 * with your PipraPay installation without modifying core files.
 * 
 * IMPORTANT: This is just an EXAMPLE. You should NOT need to modify index.php
 * if the plugin system is working correctly. The webhook handler automatically
 * hooks into the system.
 * 
 * Only use this if you absolutely need manual integration.
 */

// ============================================================================
// OPTION 1: Automatic Integration (Recommended - No Changes Needed)
// ============================================================================
// 
// If your PipraPay installation supports the plugin hook system, 
// the MFS Provider Manager will automatically handle webhooks.
// 
// NO MODIFICATIONS REQUIRED!
//
// The module automatically registers with:
// add_action('pp_init', 'mfs_handle_webhook_request', 1);
// ============================================================================


// ============================================================================
// OPTION 2: Manual Integration (If hooks don't work)
// ============================================================================
//
// If you need to manually integrate, add this code to your index.php
// INSIDE the webhook handling section (after the webhook validation):
//
// STEP 1: Find this section in index.php:
/*

if(isset($_GET['webhook'])){
    $webhook = escape_string($_GET['webhook']);
    
    if($webhook == ""){
        echo 'System is under maintenance. Please try again later.';
        exit();
    }else{
        $response = json_decode(getData($db_prefix.'settings','WHERE webhook="'.$webhook.'"'),true);
        if($response['status'] == true){
            
            // STEP 2: ADD THIS CODE RIGHT HERE ↓↓↓
            
            // Load MFS Provider Manager webhook handler
            $mfs_webhook_handler = __DIR__.'/pp-content/plugins/modules/MFS-Provider-Manager/webhook-handler.php';
            if (file_exists($mfs_webhook_handler)) {
                require_once $mfs_webhook_handler;
                
                // Use the standalone handler (it will process and exit)
                mfs_standalone_webhook_handler($webhook);
            }
            
            // END OF ADDED CODE ↑↑↑
            
            // ... rest of your existing webhook code will only run if MFS handler is not available
            
        }
    }
}

*/
// ============================================================================


// ============================================================================
// OPTION 3: Use as a Function Call (Advanced)
// ============================================================================
//
// If you want to use MFS Provider Manager as a library function:
//

function example_webhook_integration() {
    global $db_prefix;
    
    // Make sure functions are loaded
    $functions_file = __DIR__.'/pp-content/plugins/modules/MFS-Provider-Manager/functions.php';
    if (file_exists($functions_file)) {
        require_once $functions_file;
    }
    
    // Prepare webhook data
    $payload = file_get_contents('php://input');
    $decoded = json_decode($payload, true);
    
    $webhook_data = [
        'from' => $decoded['from'] ?? ($_POST['from'] ?? ''),
        'text' => $decoded['text'] ?? ($_POST['text'] ?? ''),
        'sentStamp' => $decoded['sentStamp'] ?? ($_POST['sentStamp'] ?? ''),
        'receivedStamp' => $decoded['receivedStamp'] ?? ($_POST['receivedStamp'] ?? ''),
        'sim' => $decoded['sim'] ?? ($_POST['sim'] ?? '')
    ];
    
    // Process the webhook
    if (function_exists('mfs_process_webhook')) {
        $result = mfs_process_webhook($webhook_data);
        
        // Check result
        if ($result['status']) {
            echo "Provider: " . $result['provider'] . "\n";
            echo "Parsed: " . ($result['parsed'] ? 'Yes' : 'No') . "\n";
            echo "Status: " . $result['sms_status'] . "\n";
            
            if ($result['parsed']) {
                echo "Amount: " . $result['data']['amount'] . "\n";
                echo "TrxID: " . $result['data']['trxid'] . "\n";
            }
        } else {
            echo "Failed: " . $result['message'] . "\n";
        }
    }
}

// ============================================================================


// ============================================================================
// TESTING: Test the webhook handler
// ============================================================================

/**
 * Simple test function to verify webhook handler is working
 * 
 * Usage: Call this function with sample SMS data
 */
function test_mfs_webhook_handler() {
    // Load functions
    $functions_file = __DIR__.'/functions.php';
    if (file_exists($functions_file)) {
        require_once $functions_file;
    }
    
    // Sample bKash SMS
    $test_data = [
        'from' => 'bKash',
        'text' => 'You have received Tk 1,250.50 from 01712345678. Fee Tk 12.51. Balance Tk 5,438.99. TrxID ABC123XYZ456 at 15/10/2025 14:30',
        'sentStamp' => '1697378400000',
        'receivedStamp' => '1697378400000',
        'sim' => '1'
    ];
    
    echo "Testing MFS Webhook Handler...\n";
    echo "================================\n\n";
    
    if (function_exists('mfs_process_webhook')) {
        $result = mfs_process_webhook($test_data);
        
        echo "Result:\n";
        print_r($result);
    } else {
        echo "ERROR: mfs_process_webhook() function not found!\n";
        echo "Make sure functions.php is loaded.\n";
    }
}

// Uncomment to test:
// test_mfs_webhook_handler();

// ============================================================================


// ============================================================================
// EXAMPLE: Custom processing after webhook
// ============================================================================

/**
 * Hook into MFS webhook processing to add custom actions
 */
function my_custom_webhook_actions($result) {
    if ($result['status'] && $result['parsed'] && $result['sms_status'] === 'approved') {
        // SMS was successfully parsed
        $amount = $result['data']['amount'];
        $trxid = $result['data']['trxid'];
        $provider = $result['provider'];
        
        // Your custom logic here:
        // - Send notification
        // - Update order status
        // - Trigger payment confirmation
        // - Log to external system
        // - etc.
        
        // Example: Log to file
        $log_message = sprintf(
            "[%s] Received %s via %s - TrxID: %s\n",
            date('Y-m-d H:i:s'),
            $amount,
            $provider,
            $trxid
        );
        
        file_put_contents(__DIR__.'/webhook-log.txt', $log_message, FILE_APPEND);
    }
}

// Register the hook (if hook system is available)
if (function_exists('add_action')) {
    add_action('mfs_after_webhook_process', 'my_custom_webhook_actions');
}

// ============================================================================

?>
