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
    // Handle auto-configuration actions
    if (isset($_POST['mfs_auto_configure'])) {
        $result = mfs_auto_configure_htaccess();
        
        if ($result['success']) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
            echo '<strong>Success!</strong> ' . htmlspecialchars($result['message']);
            if (isset($result['backup_created'])) {
                echo '<br><small>Backup created: ' . htmlspecialchars(basename($result['backup_created'])) . '</small>';
            }
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            echo '</div>';
        } else {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
            echo '<strong>Error!</strong> ' . htmlspecialchars($result['message']);
            if (isset($result['permissions'])) {
                echo '<br><small>Current permissions: ' . htmlspecialchars($result['permissions']) . '</small>';
            }
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            echo '</div>';
        }
    }
    
    if (isset($_POST['mfs_remove_config'])) {
        $result = mfs_remove_htaccess_config();
        
        if ($result['success']) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
            echo '<strong>Success!</strong> ' . htmlspecialchars($result['message']);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            echo '</div>';
        } else {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
            echo '<strong>Error!</strong> ' . htmlspecialchars($result['message']);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            echo '</div>';
        }
    }
    
    // Get setup status
    $setup_status = mfs_get_setup_status();
    
    // Display setup status card
    echo '<div class="container-fluid mt-4">';
    
    // Setup Status Card
    echo '<div class="card mb-4 shadow-sm">';
    echo '<div class="card-header bg-' . ($setup_status['is_configured'] ? 'success' : 'warning') . ' text-white">';
    echo '<h5 class="mb-0">';
    echo '<i class="bi bi-' . ($setup_status['is_configured'] ? 'check-circle' : 'exclamation-triangle') . '"></i> ';
    echo 'Automatic Webhook Setup Status';
    echo '</h5>';
    echo '</div>';
    echo '<div class="card-body">';
    
    if ($setup_status['is_configured']) {
        echo '<div class="alert alert-success">';
        echo '<strong>✅ Setup Complete!</strong><br>';
        echo 'Active Method: <strong>' . ucfirst($setup_status['method']) . ' Integration</strong><br>';
        echo 'Webhooks are being handled automatically.';
        echo '</div>';
    } else {
        echo '<div class="alert alert-warning">';
        echo '<strong>⚠️ Setup Required</strong><br>';
        echo 'Automatic webhook handling is not configured yet.';
        echo '</div>';
    }
    
    // Display errors
    if (!empty($setup_status['errors'])) {
        echo '<div class="alert alert-danger mt-3">';
        echo '<strong>❌ Errors:</strong><ul class="mb-0">';
        foreach ($setup_status['errors'] as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul></div>';
    }
    
    // Display warnings
    if (!empty($setup_status['warnings'])) {
        echo '<div class="alert alert-warning mt-3">';
        echo '<strong>⚠️ Warnings:</strong><ul class="mb-0">';
        foreach ($setup_status['warnings'] as $warning) {
            echo '<li>' . htmlspecialchars($warning) . '</li>';
        }
        echo '</ul></div>';
    }
    
    // Display info
    if (!empty($setup_status['info'])) {
        echo '<div class="alert alert-info mt-3">';
        echo '<strong>ℹ️ Information:</strong><ul class="mb-0">';
        foreach ($setup_status['info'] as $info) {
            echo '<li>' . htmlspecialchars($info) . '</li>';
        }
        echo '</ul></div>';
    }
    
    // Auto-configuration options
    if (!$setup_status['is_configured']) {
        echo '<div class="card mt-4">';
        echo '<div class="card-header">';
        echo '<h6 class="mb-0">🚀 Quick Setup Options</h6>';
        echo '</div>';
        echo '<div class="card-body">';
        
        // Option 1: Auto-configure .htaccess
        if (isset($setup_status['htaccess_writable']) && $setup_status['htaccess_writable']) {
            echo '<div class="mb-4">';
            echo '<h6>Option 1: Auto-Configure .htaccess (Recommended)</h6>';
            echo '<p class="text-muted">Automatically add webhook handler to your .htaccess file. A backup will be created.</p>';
            echo '<form method="post" class="d-inline">';
            echo '<input type="hidden" name="mfs_auto_configure" value="1">';
            echo '<button type="submit" class="btn btn-success" onclick="return confirm(\'This will modify your .htaccess file. A backup will be created. Continue?\');">';
            echo '<i class="bi bi-lightning-fill"></i> Auto-Configure .htaccess';
            echo '</button>';
            echo '</form>';
            echo '</div>';
        } else {
            echo '<div class="mb-4">';
            echo '<h6>Option 1: Manual .htaccess Configuration</h6>';
            echo '<div class="alert alert-warning">';
            echo '<strong>⚠️ .htaccess is not writable</strong><br>';
            echo 'Please make .htaccess writable or add the code manually:';
            echo '</div>';
            echo '<pre class="bg-light p-3 rounded"><code>';
            echo htmlspecialchars("# MFS Provider Manager - Automatic Webhook Handler\n");
            echo htmlspecialchars("RewriteCond %{QUERY_STRING} webhook=([^&]+)\n");
            echo htmlspecialchars("RewriteCond %{REQUEST_URI} ^/$ [OR]\n");
            echo htmlspecialchars("RewriteCond %{REQUEST_URI} ^/index\\.php$\n");
            echo htmlspecialchars("RewriteRule ^ pp-content/plugins/modules/MFS-Provider-Manager/webhook-endpoint.php [L]\n");
            echo '</code></pre>';
            echo '<button class="btn btn-sm btn-secondary" onclick="copyHtaccessCode()">📋 Copy Code</button>';
            echo '</div>';
        }
        
        // Option 2: PP-Config method
        echo '<div class="mb-4">';
        echo '<h6>Option 2: PP-Config Method (Alternative)</h6>';
        echo '<p class="text-muted">Add the following code to the END of your pp-config.php file:</p>';
        echo '<pre class="bg-light p-3 rounded"><code>';
        $config_code = '$mfs_interceptor = __DIR__.\'/pp-content/plugins/modules/MFS-Provider-Manager/webhook-interceptor.php\';' . "\n";
        $config_code .= 'if (file_exists($mfs_interceptor)) {' . "\n";
        $config_code .= '    require_once $mfs_interceptor;' . "\n";
        $config_code .= '}';
        echo htmlspecialchars($config_code);
        echo '</code></pre>';
        echo '<button class="btn btn-sm btn-secondary" onclick="copyConfigCode()">📋 Copy Code</button>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    } else {
        // Show remove option if configured via .htaccess
        if ($setup_status['method'] === 'htaccess' && isset($setup_status['htaccess_writable']) && $setup_status['htaccess_writable']) {
            echo '<div class="card mt-4">';
            echo '<div class="card-header bg-danger text-white">';
            echo '<h6 class="mb-0">🗑️ Remove Configuration</h6>';
            echo '</div>';
            echo '<div class="card-body">';
            echo '<p class="text-muted">Remove MFS Provider Manager configuration from .htaccess. A backup will be created.</p>';
            echo '<form method="post" class="d-inline">';
            echo '<input type="hidden" name="mfs_remove_config" value="1">';
            echo '<button type="submit" class="btn btn-danger" onclick="return confirm(\'This will remove MFS configuration from .htaccess. A backup will be created. Continue?\');">';
            echo '<i class="bi bi-trash"></i> Remove .htaccess Configuration';
            echo '</button>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
        }
    }
    
    echo '</div>';
    echo '</div>';
    
    // Add JavaScript for copy buttons
    echo '<script>';
    echo 'function copyHtaccessCode() {';
    echo '  const code = `# MFS Provider Manager - Automatic Webhook Handler\nRewriteCond %{QUERY_STRING} webhook=([^&]+)\nRewriteCond %{REQUEST_URI} ^/$ [OR]\nRewriteCond %{REQUEST_URI} ^/index\\\\.php$\nRewriteRule ^ pp-content/plugins/modules/MFS-Provider-Manager/webhook-endpoint.php [L]`;';
    echo '  navigator.clipboard.writeText(code).then(() => { alert(\'Code copied to clipboard!\'); });';
    echo '}';
    echo 'function copyConfigCode() {';
    echo '  const code = `$mfs_interceptor = __DIR__.\'/pp-content/plugins/modules/MFS-Provider-Manager/webhook-interceptor.php\';\nif (file_exists($mfs_interceptor)) {\n    require_once $mfs_interceptor;\n}`;';
    echo '  navigator.clipboard.writeText(code).then(() => { alert(\'Code copied to clipboard!\'); });';
    echo '}';
    echo '</script>';
    
    echo '</div>';
    
    // Load the main admin UI
    $viewFile = __DIR__ . '/views/admin-ui.php';

    if (file_exists($viewFile)) {
        include $viewFile;
    } else {
        echo "<div class='alert alert-danger'>Admin UI not found.</div>";
    }
}
