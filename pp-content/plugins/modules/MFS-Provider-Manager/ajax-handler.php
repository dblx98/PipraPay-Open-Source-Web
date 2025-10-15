<?php
/**
 * MFS Provider Manager - AJAX Handler
 * 
 * This file handles all AJAX requests for the MFS Provider Manager plugin.
 * It should be called directly via AJAX to avoid HTML output issues.
 */

// Load PipraPay core configuration
$pp_config = __DIR__ . '/../../../../pp-config.php';
if (file_exists($pp_config)) {
    require_once $pp_config;
} else {
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Configuration file not found']);
    exit;
}

// Load controller and model (this sets up $global_user_login)
if (file_exists(__DIR__ . '/../../../../pp-include/pp-controller.php')) {
    require_once __DIR__ . '/../../../../pp-include/pp-controller.php';
} else {
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Controller not found']);
    exit;
}

if (file_exists(__DIR__ . '/../../../../pp-include/pp-model.php')) {
    require_once __DIR__ . '/../../../../pp-include/pp-model.php';
} else {
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Model not found']);
    exit;
}

// Check if user is logged in using PipraPay's authentication
if (!isset($global_user_login) || $global_user_login == false) {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

// Define access constant
define('pp_allowed_access', true);

// Load plugin functions
require_once __DIR__ . '/functions.php';

// Clear any output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Set JSON header
header('Content-Type: application/json');

// Get action
$action = $_POST['ajax_action'] ?? $_GET['ajax_action'] ?? '';

if (empty($action)) {
    echo json_encode(['status' => false, 'message' => 'No action specified']);
    exit;
}

// Handle actions
switch ($action) {
    case 'add_provider':
        $short_name = escape_string($_POST['short_name'] ?? '');
        $full_name = escape_string($_POST['full_name'] ?? '');
        
        if (empty($short_name) || empty($full_name)) {
            echo json_encode(['status' => false, 'message' => 'Both short name and full name are required']);
            exit;
        }
        
        mfs_save_provider($short_name, $full_name);
        echo json_encode(['status' => true, 'message' => 'Provider added successfully']);
        break;
        
    case 'delete_provider':
        $short_name = escape_string($_POST['short_name'] ?? '');
        
        if (mfs_delete_provider($short_name)) {
            echo json_encode(['status' => true, 'message' => 'Provider deleted successfully']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Provider not found']);
        }
        break;
        
    case 'add_format':
        $provider_name = escape_string($_POST['provider_name'] ?? '');
        $format_type = escape_string($_POST['format_type'] ?? '');
        $regex_pattern = $_POST['regex_pattern'] ?? ''; // Don't escape regex
        
        if (empty($provider_name) || empty($format_type) || empty($regex_pattern)) {
            echo json_encode(['status' => false, 'message' => 'All fields are required']);
            exit;
        }
        
        mfs_save_format($provider_name, $format_type, $regex_pattern);
        echo json_encode(['status' => true, 'message' => 'Format added successfully']);
        break;
        
    case 'update_format':
        $provider_name = escape_string($_POST['provider_name'] ?? '');
        $format_index = intval($_POST['format_index'] ?? -1);
        $format_type = escape_string($_POST['format_type'] ?? '');
        $regex_pattern = $_POST['regex_pattern'] ?? ''; // Don't escape regex
        
        if (empty($provider_name) || $format_index < 0 || empty($format_type) || empty($regex_pattern)) {
            echo json_encode(['status' => false, 'message' => 'All fields are required']);
            exit;
        }
        
        if (mfs_update_format($provider_name, $format_index, $format_type, $regex_pattern)) {
            echo json_encode(['status' => true, 'message' => 'Format updated successfully']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Format not found']);
        }
        break;
        
    case 'delete_format':
        $provider_name = escape_string($_POST['provider_name'] ?? '');
        $format_index = intval($_POST['format_index'] ?? -1);
        
        if (mfs_delete_format($provider_name, $format_index)) {
            echo json_encode(['status' => true, 'message' => 'Format deleted successfully']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Format not found']);
        }
        break;
        
    case 'test_regex':
        $pattern = $_POST['pattern'] ?? '';
        $sample_text = $_POST['sample_text'] ?? '';
        
        $result = mfs_test_regex($pattern, $sample_text);
        echo json_encode($result);
        break;
        
    case 'reset_to_defaults':
        if (mfs_reset_to_defaults()) {
            echo json_encode(['status' => true, 'message' => 'Settings reset to defaults successfully']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Failed to reset settings']);
        }
        break;
        
    case 'export_settings':
        $export_data = mfs_export_settings();
        echo json_encode([
            'status' => true,
            'data' => $export_data,
            'message' => 'Settings exported successfully'
        ]);
        break;
        
    case 'import_settings':
        $import_json = $_POST['import_data'] ?? '';
        $import_data = json_decode($import_json, true);
        
        if (mfs_import_settings($import_data)) {
            echo json_encode(['status' => true, 'message' => 'Settings imported successfully']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Failed to import settings']);
        }
        break;
        
    case 'check_for_updates':
        $update_info = mfs_check_for_github_updates();
        
        if ($update_info) {
            echo json_encode([
                'status' => true,
                'update_available' => true,
                'data' => $update_info,
                'message' => 'Update available'
            ]);
        } else {
            echo json_encode([
                'status' => true,
                'update_available' => false,
                'message' => 'You are using the latest version'
            ]);
        }
        break;
        
    default:
        echo json_encode(['status' => false, 'message' => 'Invalid action: ' . $action]);
        break;
}

exit;
