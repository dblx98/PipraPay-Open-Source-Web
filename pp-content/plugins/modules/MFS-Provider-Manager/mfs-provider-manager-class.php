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

// Load the admin UI rendering function
function mfs_provider_manager_admin_page() {
    $viewFile = __DIR__ . '/views/admin-ui.php';

    if (file_exists($viewFile)) {
        include $viewFile;
    } else {
        echo "<div class='alert alert-warning'>Admin UI not found.</div>";
    }
}
