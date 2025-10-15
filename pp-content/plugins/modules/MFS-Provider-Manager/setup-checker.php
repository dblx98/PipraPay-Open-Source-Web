<?php
/**
 * MFS Provider Manager - Setup Checker
 * 
 * This file checks if the automatic webhook handler is properly configured.
 * Call this function from the admin UI to display setup status.
 * 
 * @package MFS Provider Manager
 * @version 1.0.3
 * @author Saimun Bepari
 */

if (!defined('pp_allowed_access')) {
    die('Direct access not allowed');
}

/**
 * Check if automatic webhook handling is properly configured
 * 
 * @return array Status information
 */
function mfs_check_webhook_setup() {
    $status = [
        'overall' => false,
        'checks' => [],
        'method' => 'none',
        'recommendations' => []
    ];
    
    // Check 1: Module is active
    global $db_prefix;
    $response = json_decode(getData($db_prefix.'plugins', 'WHERE plugin_slug="mfs-provider-manager"'), true);
    
    if ($response['status'] == true && $response['response'][0]['status'] == 'active') {
        $status['checks']['module_active'] = [
            'status' => 'success',
            'message' => 'Module is active',
            'icon' => '✅'
        ];
    } else {
        $status['checks']['module_active'] = [
            'status' => 'error',
            'message' => 'Module is not active',
            'icon' => '❌'
        ];
        return $status;
    }
    
    // Check 2: Webhook interceptor file exists
    $interceptor_file = __DIR__ . '/webhook-interceptor.php';
    if (file_exists($interceptor_file)) {
        $status['checks']['interceptor_file'] = [
            'status' => 'success',
            'message' => 'Webhook interceptor file found',
            'icon' => '✅'
        ];
    } else {
        $status['checks']['interceptor_file'] = [
            'status' => 'error',
            'message' => 'Webhook interceptor file missing',
            'icon' => '❌'
        ];
    }
    
    // Check 3: Webhook endpoint file exists
    $endpoint_file = __DIR__ . '/webhook-endpoint.php';
    if (file_exists($endpoint_file)) {
        $status['checks']['endpoint_file'] = [
            'status' => 'success',
            'message' => 'Webhook endpoint file found',
            'icon' => '✅'
        ];
    } else {
        $status['checks']['endpoint_file'] = [
            'status' => 'warning',
            'message' => 'Webhook endpoint file missing (needed for .htaccess method)',
            'icon' => '⚠️'
        ];
    }
    
    // Check 4: Webhook handler file exists
    $handler_file = __DIR__ . '/webhook-handler.php';
    if (file_exists($handler_file)) {
        $status['checks']['handler_file'] = [
            'status' => 'success',
            'message' => 'Webhook handler file found',
            'icon' => '✅'
        ];
    } else {
        $status['checks']['handler_file'] = [
            'status' => 'error',
            'message' => 'Webhook handler file missing',
            'icon' => '❌'
        ];
    }
    
    // Check 5: Functions file exists and mfs_process_webhook is available
    if (function_exists('mfs_process_webhook')) {
        $status['checks']['functions'] = [
            'status' => 'success',
            'message' => 'MFS functions are loaded',
            'icon' => '✅'
        ];
    } else {
        $status['checks']['functions'] = [
            'status' => 'error',
            'message' => 'MFS functions not loaded',
            'icon' => '❌'
        ];
    }
    
    // Check 6: Try to detect if pp-config.php includes the interceptor
    $config_file = __DIR__ . '/../../../../../pp-config.php';
    $pp_config_configured = false;
    
    if (file_exists($config_file)) {
        $config_content = file_get_contents($config_file);
        if (strpos($config_content, 'webhook-interceptor.php') !== false) {
            $pp_config_configured = true;
            $status['checks']['pp_config'] = [
                'status' => 'success',
                'message' => 'PP-Config method is configured',
                'icon' => '✅'
            ];
            $status['method'] = 'pp-config';
        } else {
            $status['checks']['pp_config'] = [
                'status' => 'warning',
                'message' => 'PP-Config method not detected',
                'icon' => '⚠️'
            ];
        }
    }
    
    // Check 7: Try to detect if .htaccess has the rewrite rule
    $htaccess_file = __DIR__ . '/../../../../../.htaccess';
    $htaccess_configured = false;
    
    if (file_exists($htaccess_file)) {
        $htaccess_content = file_get_contents($htaccess_file);
        if (strpos($htaccess_content, 'webhook-endpoint.php') !== false) {
            $htaccess_configured = true;
            $status['checks']['htaccess'] = [
                'status' => 'success',
                'message' => '.htaccess method is configured',
                'icon' => '✅'
            ];
            $status['method'] = 'htaccess';
        } else {
            $status['checks']['htaccess'] = [
                'status' => 'warning',
                'message' => '.htaccess method not detected',
                'icon' => '⚠️'
            ];
        }
    }
    
    // Check 8: Has any providers configured
    $providers = mfs_get_providers();
    if (!empty($providers)) {
        $provider_count = count($providers);
        $status['checks']['providers'] = [
            'status' => 'success',
            'message' => "$provider_count MFS providers configured",
            'icon' => '✅'
        ];
    } else {
        $status['checks']['providers'] = [
            'status' => 'warning',
            'message' => 'No providers configured (using defaults)',
            'icon' => '⚠️'
        ];
    }
    
    // Check 9: Has any formats configured
    $formats = mfs_get_provider_formats();
    $format_count = 0;
    foreach ($formats as $provider_formats) {
        $format_count += count($provider_formats);
    }
    
    if ($format_count > 0) {
        $status['checks']['formats'] = [
            'status' => 'success',
            'message' => "$format_count SMS format patterns configured",
            'icon' => '✅'
        ];
    } else {
        $status['checks']['formats'] = [
            'status' => 'warning',
            'message' => 'No format patterns configured (using defaults)',
            'icon' => '⚠️'
        ];
    }
    
    // Determine overall status
    if ($pp_config_configured || $htaccess_configured) {
        $status['overall'] = true;
        $status['message'] = 'Automatic webhook handling is properly configured!';
    } else {
        $status['overall'] = false;
        $status['message'] = 'Automatic webhook handling is NOT configured.';
        
        // Add recommendations
        $status['recommendations'][] = [
            'title' => 'Recommended: PP-Config Method',
            'description' => 'Add webhook interceptor to pp-config.php (works on all servers)',
            'code' => "\$mfs_interceptor = __DIR__.'/pp-content/plugins/modules/MFS-Provider-Manager/webhook-interceptor.php';\nif (file_exists(\$mfs_interceptor)) {\n    require_once \$mfs_interceptor;\n}",
            'file' => 'pp-config.php',
            'position' => 'At the end of the file'
        ];
        
        $status['recommendations'][] = [
            'title' => 'Alternative: .htaccess Method',
            'description' => 'Add rewrite rule to .htaccess (Apache only)',
            'code' => "# MFS Provider Manager Webhook Handler\nRewriteCond %{QUERY_STRING} webhook=([^&]+)\nRewriteCond %{REQUEST_URI} ^/\$ [OR]\nRewriteCond %{REQUEST_URI} ^/index\\.php\$\nRewriteRule ^ pp-content/plugins/modules/MFS-Provider-Manager/webhook-endpoint.php [L]",
            'file' => '.htaccess',
            'position' => 'Before other RewriteRules'
        ];
    }
    
    return $status;
}

/**
 * Display setup status in HTML format
 */
function mfs_display_setup_status() {
    $status = mfs_check_webhook_setup();
    
    ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <?php if ($status['overall']): ?>
                    <span class="badge bg-success">✅ Setup Complete</span>
                <?php else: ?>
                    <span class="badge bg-warning">⚠️ Setup Required</span>
                <?php endif; ?>
                Automatic Webhook Handler Status
            </h5>
        </div>
        <div class="card-body">
            <p class="mb-3"><strong><?php echo $status['message']; ?></strong></p>
            
            <?php if ($status['method'] != 'none'): ?>
                <div class="alert alert-success">
                    <strong>Active Method:</strong> 
                    <?php echo $status['method'] == 'pp-config' ? 'PP-Config Integration' : '.htaccess Integration'; ?>
                </div>
            <?php endif; ?>
            
            <h6 class="mt-4 mb-3">System Checks:</h6>
            <ul class="list-group">
                <?php foreach ($status['checks'] as $check_name => $check): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center
                        <?php echo $check['status'] == 'success' ? 'list-group-item-success' : 
                                  ($check['status'] == 'error' ? 'list-group-item-danger' : 'list-group-item-warning'); ?>">
                        <?php echo $check['message']; ?>
                        <span class="badge bg-secondary"><?php echo $check['icon']; ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <?php if (!empty($status['recommendations'])): ?>
                <h6 class="mt-4 mb-3">Setup Instructions:</h6>
                <?php foreach ($status['recommendations'] as $index => $rec): ?>
                    <div class="card mb-3">
                        <div class="card-header">
                            <strong><?php echo $rec['title']; ?></strong>
                        </div>
                        <div class="card-body">
                            <p><?php echo $rec['description']; ?></p>
                            <p class="mb-2"><strong>File:</strong> <code><?php echo $rec['file']; ?></code></p>
                            <p class="mb-2"><strong>Location:</strong> <?php echo $rec['position']; ?></p>
                            <p class="mb-2"><strong>Code to add:</strong></p>
                            <pre style="background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto;"><code><?php echo htmlspecialchars($rec['code']); ?></code></pre>
                            <button class="btn btn-sm btn-secondary" onclick="copyToClipboard<?php echo $index; ?>()">
                                📋 Copy Code
                            </button>
                            <script>
                            function copyToClipboard<?php echo $index; ?>() {
                                const code = <?php echo json_encode($rec['code']); ?>;
                                navigator.clipboard.writeText(code).then(() => {
                                    alert('Code copied to clipboard!');
                                });
                            }
                            </script>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="alert alert-info mt-3">
                    <strong>📖 Need detailed instructions?</strong><br>
                    See <code>SETUP-INSTRUCTIONS.md</code> in the module folder for complete guide.
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
