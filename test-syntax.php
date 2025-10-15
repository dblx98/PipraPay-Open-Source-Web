<?php
function mfs_auto_configure() {
    $config_path = __DIR__ . '/../../../../pp-config.php';
    
    // Check if file exists, create if it doesn't
    if (!file_exists($config_path)) {
        // Create basic pp-config.php file
        $initial_content = "<?php\n";
        $initial_content .= "// PipraPay Configuration File\n";
        $initial_content .= "// Created automatically by MFS Provider Manager\n";
        $initial_content .= "// Add your database and other configurations here\n\n";
        
        if (file_put_contents($config_path, $initial_content) === false) {
            return [
                'success' => false,
                'message' => 'Failed to create pp-config.php file. Please create it manually.'
            ];
        }
    }
    
    // Read current content
    $content = file_get_contents($config_path);
    
    // Check if already configured
    if (strpos($content, 'webhook-interceptor.php') !== false || 
        strpos($content, 'MFS Provider Manager') !== false) {
        return [
            'success' => true,
            'message' => 'pp-config.php already configured',
            'already_configured' => true
        ];
    }
    
    // Check if writable
    if (!is_writable($config_path)) {
        return [
            'success' => false,
            'message' => 'pp-config.php is not writable',
            'can_modify' => false,
            'permissions' => substr(sprintf('%o', fileperms($config_path)), -4)
        ];
    }
    
    // Prepare the code to append
    $webhook_code = "\n// =====================================================================\n";
    $webhook_code .= "// MFS Provider Manager - Automatic Webhook Handler\n";
    $webhook_code .= "// Added automatically by MFS Provider Manager plugin\n";
    $webhook_code .= "// =====================================================================\n";
    $webhook_code .= "\$mfs_interceptor = __DIR__.'/pp-content/plugins/modules/MFS-Provider-Manager/webhook-interceptor.php';\n";
    $webhook_code .= "if (file_exists(\$mfs_interceptor)) {\n";
    $webhook_code .= "    require_once \$mfs_interceptor;\n";
    $webhook_code .= "}\n";
    
    // Create backup
    $backup_path = $config_path . '.mfs-backup-' . date('Y-m-d-His');
    if (!copy($config_path, $backup_path)) {
        return [
            'success' => false,
            'message' => 'Failed to create backup file'
        ];
    }
    
    // Check if the file ends with ?>
    $content = rtrim($content);
    if (substr($content, -2) === '?>') {
        // Remove the closing PHP tag
        $content = substr($content, 0, -2);
        $content = rtrim($content);
    }
    
    // Append the webhook code
    $new_content = $content . $webhook_code;
    
    // Write the new content
    if (file_put_contents($config_path, $new_content) === false) {
        return [
            'success' => false,
            'message' => 'Failed to write to pp-config.php'
        ];
    }
    
    return [
        'success' => true,
        'message' => 'pp-config.php configured successfully',
        'backup_created' => $backup_path
    ];
}

echo "Syntax OK\n";
