<?php
    if (!defined('pp_allowed_access')) {
        die('Direct access not allowed');
    }

$plugin_meta = [
    'Plugin Name'       => 'MFS Provider Manager',
    'Description'       => 'Powerful module to manage MFS (Mobile Financial Service) providers and their SMS format patterns. Add, edit, and manage custom providers and regex patterns for automatic SMS parsing without touching core code.',
    'Version'           => '1.0.3',
    'Author'            => 'Saimun Bepari',
    'Author URI'        => 'https://saimun.dev/',
    'License'           => 'GPL-2.0+',
    'License URI'       => 'http://www.gnu.org/licenses/gpl-2.0.txt',
    'Requires at least' => '1.0.0',
    'Plugin URI'        => '',
    'Text Domain'       => '',
    'Domain Path'       => '',
    'Requires PHP'      => '7.4'
];

$funcFile = __DIR__ . '/functions.php';
if (file_exists($funcFile)) {
    require_once $funcFile;
}

// Load setup checker
$setupCheckerFile = __DIR__ . '/setup-checker.php';
if (file_exists($setupCheckerFile)) {
    require_once $setupCheckerFile;
}

/**
 * Attempt to automatically configure pp-config.php for webhook handling
 * 
 * @return array Result with status and message
 */
function mfs_auto_configure() {
    $config_path = __DIR__ . '/../../../../pp-config.php';
    
    // Check if file exists, create if it doesn't
    if (!file_exists($config_path)) {
        // Create basic pp-config.php file
        $initial_content = "<?php\n";
        $initial_content .= "// PipraPay Configuration File\n";
        $initial_content .= "// Created automatically by mfs-provider-manager\n";
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
        strpos($content, 'mfs-provider-manager') !== false) {
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
    
    // Prepare the code to insert
    $webhook_code = "\n// =====================================================================\n";
    $webhook_code .= "// mfs-provider-manager - Automatic Webhook Handler\n";
    $webhook_code .= "// Added automatically by mfs-provider-manager plugin\n";
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
    
    // Check if file ends with closing PHP tag
    $content_trimmed = rtrim($content);
    if (substr($content_trimmed, -2) === '?>') {
        // Insert before the closing tag
        $new_content = substr($content_trimmed, 0, -2) . $webhook_code . "\n?>";
    } else {
        // Just append at the end
        $new_content = $content . $webhook_code;
    }
    
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

/**
 * Remove MFS configuration from pp-config.php
 * 
 * @return array Result with status and message
 */
function mfs_remove_config() {
    $config_path = __DIR__ . '/../../../../pp-config.php';
    
    if (!file_exists($config_path)) {
        return [
            'success' => false,
            'message' => 'pp-config.php file not found'
        ];
    }
    
    if (!is_writable($config_path)) {
        return [
            'success' => false,
            'message' => 'pp-config.php is not writable'
        ];
    }
    
    $content = file_get_contents($config_path);
    
    // Check if MFS configuration exists
    if (strpos($content, 'mfs-provider-manager') === false) {
        return [
            'success' => true,
            'message' => 'MFS configuration not found in pp-config.php',
            'already_removed' => true
        ];
    }
    
    // Create backup
    $backup_path = $config_path . '.mfs-backup-' . date('Y-m-d-His');
    copy($config_path, $backup_path);
    
    // Remove MFS configuration block (support both old uppercase and new lowercase)
    $pattern = '/\n*\/\/ ={50,}\n\/\/ (mfs-provider-manager|MFS Provider Manager).*?require_once.*?;\n}\n/s';
    $new_content = preg_replace($pattern, "\n", $content);
    
    if (file_put_contents($config_path, $new_content) === false) {
        return [
            'success' => false,
            'message' => 'Failed to write to pp-config.php'
        ];
    }
    
    return [
        'success' => true,
        'message' => 'MFS configuration removed from pp-config.php',
        'backup_created' => $backup_path
    ];
}

/**
 * Get setup status with detailed information
 * 
 * @return array Setup status information
 */
function mfs_get_setup_status() {
    $status = [
        'is_configured' => false,
        'method' => 'none',
        'errors' => [],
        'warnings' => [],
        'info' => []
    ];
    
    // Check pp-config.php
    $config_path = __DIR__ . '/../../../../pp-config.php';
    if (file_exists($config_path)) {
        $content = file_get_contents($config_path);
        
        if (strpos($content, 'webhook-interceptor.php') !== false) {
            $status['is_configured'] = true;
            $status['method'] = 'pp-config';
            $status['info'][] = 'PP-Config method is active';
        } else {
            $status['warnings'][] = 'PP-Config method not configured';
        }
        
        if (!is_writable($config_path)) {
            $status['warnings'][] = 'pp-config.php is not writable';
            $status['config_writable'] = false;
            $status['permissions'] = substr(sprintf('%o', fileperms($config_path)), -4);
        } else {
            $status['config_writable'] = true;
        }
    } else {
        $status['errors'][] = 'pp-config.php file not found';
    }
    
    // Check required files
    $required_files = [
        'webhook-interceptor.php',
        'webhook-endpoint.php',
        'webhook-handler.php',
        'functions.php'
    ];
    
    foreach ($required_files as $file) {
        if (!file_exists(__DIR__ . '/' . $file)) {
            $status['errors'][] = "Required file missing: $file";
        }
    }
    
    return $status;
}

// Load the admin UI rendering function
function mfs_provider_manager_admin_page() {
    // Load the main admin UI
    $viewFile = __DIR__ . '/views/admin-ui.php';

    if (file_exists($viewFile)) {
        include $viewFile;
    } else {
        echo "<div class='alert alert-danger'>Admin UI not found.</div>";
    }
}
