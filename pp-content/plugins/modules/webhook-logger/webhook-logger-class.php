<?php
    if (!defined('pp_allowed_access')) {
        die('Direct access not allowed');
    }

$plugin_meta = [
    'Plugin Name'       => 'Webhook Logger (Example)',
    'Description'       => 'Example plugin demonstrating how to process incoming webhooks. Logs all webhook requests for debugging and monitoring purposes.',
    'Version'           => '1.0.0',
    'Author'            => 'PipraPay',
    'Author URI'        => 'https://piprapay.com/',
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
function webhook_logger_admin_page() {
    echo "<div class='card'>";
    echo "<div class='card-body'>";
    echo "<h4>Webhook Logger</h4>";
    echo "<p>This example plugin demonstrates how to process incoming webhooks without modifying index.php.</p>";
    echo "<h5>Features:</h5>";
    echo "<ul>";
    echo "<li>Logs all incoming webhook requests</li>";
    echo "<li>Captures headers, POST data, and raw payload</li>";
    echo "<li>Demonstrates the <code>pp_webhook_received</code> hook</li>";
    echo "</ul>";
    echo "<h5>Log Location:</h5>";
    echo "<p><code>pp-content/plugins/modules/webhook-logger/webhook-requests.log</code></p>";
    echo "<div class='alert alert-info'>";
    echo "<strong>Note:</strong> This is an example plugin for demonstration purposes. ";
    echo "See the <a href='https://github.com/dblx98/PipraPay-Open-Source-Web/blob/main/docs/Webhook-Processing-Plugin-Guide.md' target='_blank'>Webhook Processing Plugin Guide</a> for complete documentation.";
    echo "</div>";
    echo "</div>";
    echo "</div>";
}
