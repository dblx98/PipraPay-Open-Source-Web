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
 * Attempt to automatically configure .htaccess for webhook handling
 * 
 * @return array Result with status and message
 */
function mfs_auto_configure_htaccess() {
    $htaccess_path = __DIR__ . '/../../../../.htaccess';
    
    // Check if file exists
    if (!file_exists($htaccess_path)) {
        return [
            'success' => false,
            'message' => '.htaccess file not found',
            'can_create' => is_writable(dirname($htaccess_path))
        ];
    }
    
    // Read current content
    $content = file_get_contents($htaccess_path);
    
    // Check if already configured
    if (strpos($content, 'webhook-endpoint.php') !== false || 
        strpos($content, 'MFS Provider Manager') !== false) {
        return [
            'success' => true,
            'message' => '.htaccess already configured',
            'already_configured' => true
        ];
    }
    
    // Check if writable
    if (!is_writable($htaccess_path)) {
        return [
            'success' => false,
            'message' => '.htaccess is not writable',
            'can_modify' => false,
            'permissions' => substr(sprintf('%o', fileperms($htaccess_path)), -4)
        ];
    }
    
    // Prepare the code to append
    $webhook_code = "\n\n# =====================================================================\n";
    $webhook_code .= "# MFS Provider Manager - Automatic Webhook Handler\n";
    $webhook_code .= "# Added automatically by MFS Provider Manager plugin\n";
    $webhook_code .= "# =====================================================================\n";
    $webhook_code .= "RewriteCond %{QUERY_STRING} webhook=([^&]+)\n";
    $webhook_code .= "RewriteCond %{REQUEST_URI} ^/$ [OR]\n";
    $webhook_code .= "RewriteCond %{REQUEST_URI} ^/index\\.php$\n";
    $webhook_code .= "RewriteRule ^ pp-content/plugins/modules/MFS-Provider-Manager/webhook-endpoint.php [L]\n";
    
    // Find the position to insert (after RewriteEngine On)
    $lines = explode("\n", $content);
    $insert_position = 0;
    
    foreach ($lines as $index => $line) {
        if (stripos($line, 'RewriteEngine On') !== false) {
            $insert_position = $index + 1;
            break;
        }
    }
    
    // Insert the code
    if ($insert_position > 0) {
        array_splice($lines, $insert_position, 0, explode("\n", $webhook_code));
        $new_content = implode("\n", $lines);
    } else {
        // If RewriteEngine On not found, append at the end
        $new_content = $content . $webhook_code;
    }
    
    // Create backup
    $backup_path = $htaccess_path . '.mfs-backup-' . date('Y-m-d-His');
    if (!copy($htaccess_path, $backup_path)) {
        return [
            'success' => false,
            'message' => 'Failed to create backup file'
        ];
    }
    
    // Write the new content
    if (file_put_contents($htaccess_path, $new_content) === false) {
        return [
            'success' => false,
            'message' => 'Failed to write to .htaccess'
        ];
    }
    
    return [
        'success' => true,
        'message' => '.htaccess configured successfully',
        'backup_created' => $backup_path
    ];
}

/**
 * Remove MFS configuration from .htaccess
 * 
 * @return array Result with status and message
 */
function mfs_remove_htaccess_config() {
    $htaccess_path = __DIR__ . '/../../../../.htaccess';
    
    if (!file_exists($htaccess_path)) {
        return [
            'success' => false,
            'message' => '.htaccess file not found'
        ];
    }
    
    if (!is_writable($htaccess_path)) {
        return [
            'success' => false,
            'message' => '.htaccess is not writable'
        ];
    }
    
    $content = file_get_contents($htaccess_path);
    
    // Check if MFS configuration exists
    if (strpos($content, 'MFS Provider Manager') === false) {
        return [
            'success' => true,
            'message' => 'MFS configuration not found in .htaccess',
            'already_removed' => true
        ];
    }
    
    // Create backup
    $backup_path = $htaccess_path . '.mfs-backup-' . date('Y-m-d-His');
    copy($htaccess_path, $backup_path);
    
    // Remove MFS configuration block
    $pattern = '/\n*# ={50,}\n# MFS Provider Manager.*?\nRewriteRule.*?webhook-endpoint\.php.*?\n/s';
    $new_content = preg_replace($pattern, "\n", $content);
    
    if (file_put_contents($htaccess_path, $new_content) === false) {
        return [
            'success' => false,
            'message' => 'Failed to write to .htaccess'
        ];
    }
    
    return [
        'success' => true,
        'message' => 'MFS configuration removed from .htaccess',
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
    
    // Check .htaccess
    $htaccess_path = __DIR__ . '/../../../../.htaccess';
    if (file_exists($htaccess_path)) {
        $content = file_get_contents($htaccess_path);
        
        if (strpos($content, 'webhook-endpoint.php') !== false) {
            $status['is_configured'] = true;
            $status['method'] = 'htaccess';
            $status['info'][] = '.htaccess method is active';
        } else {
            $status['warnings'][] = '.htaccess method not configured';
        }
        
        if (!is_writable($htaccess_path)) {
            $status['errors'][] = '.htaccess is not writable (chmod needed)';
            $status['htaccess_writable'] = false;
        } else {
            $status['htaccess_writable'] = true;
        }
    } else {
        $status['errors'][] = '.htaccess file not found';
    }
    
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
