<?php
    if (!defined('pp_allowed_access')) {
        die('Direct access not allowed');
    }

    /**
     * Example webhook processing plugin
     * 
     * This plugin demonstrates how to use the pp_webhook_received hook
     * to process incoming webhooks without modifying index.php
     */

    // Register the webhook logging hook
    add_action('pp_webhook_received', 'webhook_logger_log_request');
    add_action('pp_webhook_processed', 'webhook_logger_log_completion');
    
    /**
     * Log incoming webhook requests
     * 
     * @param string $webhook The webhook key
     * @param array $post_data The $_POST data
     * @param string $raw_input The raw POST body
     */
    function webhook_logger_log_request($webhook, $post_data, $raw_input) {
        $log_file = __DIR__ . '/webhook-requests.log';
        $timestamp = date('Y-m-d H:i:s');
        
        // Collect request information
        $log_entry = str_repeat('=', 80) . "\n";
        $log_entry .= "[$timestamp] WEBHOOK REQUEST RECEIVED\n";
        $log_entry .= str_repeat('=', 80) . "\n";
        $log_entry .= "Webhook Key: $webhook\n";
        $log_entry .= "Remote IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
        $log_entry .= "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') . "\n";
        $log_entry .= "\n";
        
        // Log headers
        $log_entry .= "--- HEADERS ---\n";
        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                $log_entry .= "$name: $value\n";
            }
        } else {
            $log_entry .= "Headers not available\n";
        }
        $log_entry .= "\n";
        
        // Log POST data
        $log_entry .= "--- POST DATA ---\n";
        if (!empty($post_data)) {
            $log_entry .= json_encode($post_data, JSON_PRETTY_PRINT) . "\n";
        } else {
            $log_entry .= "No POST data\n";
        }
        $log_entry .= "\n";
        
        // Log raw input
        $log_entry .= "--- RAW INPUT ---\n";
        if (!empty($raw_input)) {
            $log_entry .= $raw_input . "\n";
        } else {
            $log_entry .= "No raw input\n";
        }
        $log_entry .= "\n";
        
        // Write to log file
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
    
    /**
     * Log webhook processing completion
     * 
     * @param string $webhook The webhook key
     * @param string $device_status The device connection status
     */
    function webhook_logger_log_completion($webhook, $device_status) {
        $log_file = __DIR__ . '/webhook-requests.log';
        $timestamp = date('Y-m-d H:i:s');
        
        $log_entry = "[$timestamp] WEBHOOK PROCESSING COMPLETED\n";
        $log_entry .= "Webhook Key: $webhook\n";
        $log_entry .= "Device Status: $device_status\n";
        $log_entry .= str_repeat('=', 80) . "\n\n";
        
        // Write to log file
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }

?>
