<?php
    if (!defined('pp_allowed_access')) {
        die('Direct access not allowed');
    }

    // Get data for display
    $plugin_slug = 'mfs-provider-manager';
    $settings = pp_get_plugin_setting($plugin_slug);
    
    // Get current providers and formats
    $providers = mfs_get_providers();
    $provider_formats = mfs_get_provider_formats();
?>

<style>
    .provider-card {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        background: #f9f9f9;
    }
    .format-card {
        border: 1px solid #d0d0d0;
        border-radius: 5px;
        padding: 10px;
        margin-bottom: 8px;
        background: #ffffff;
    }
    .regex-display {
        font-family: 'Courier New', monospace;
        font-size: 12px;
        background: #f5f5f5;
        padding: 8px;
        border-radius: 4px;
        word-break: break-all;
    }
    .badge-provider {
        background: #0d6efd;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
    }
</style>

<!-- Page Header -->
 
<div class="page-header">
    <div class="row align-items-end">
        <div class="col-sm mb-2 mb-sm-0">
            <h1 class="page-header-title">MFS Provider Manager</h1>
            <p class="text-muted">Manage Mobile Financial Service providers and their SMS format patterns</p>
        </div>
        <div class="col-sm-auto">
            <div class="btn-group">
                <button type="button" class="btn btn-secondary" id="checkForUpdatesBtn">
                    <i class="bi-cloud-download"></i> Check for Updates
                </button>
                <button type="button" class="btn btn-secondary" id="exportSettingsBtn">
                    <i class="bi-download"></i> Export
                </button>
                <button type="button" class="btn btn-secondary" id="importSettingsBtn">
                    <i class="bi-upload"></i> Import
                </button>
                <button type="button" class="btn btn-warning" id="resetToDefaultsBtn">
                    <i class="bi-arrow-clockwise"></i> Reset to Defaults
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Plugin Updates Section (Collapsible) -->
<div class="card mb-3 collapse" id="updateCheckCard">
    <div class="card-body">
        <div id="updateCheckResponse"></div>
    </div>
</div>

<div class="row">
    <!-- Left Column: Provider Management -->
    <div class="col-lg-6">
        <!-- MFS Providers Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title mb-0">MFS Providers</h4>
            </div>
            <div class="card-body">
                <!-- Add Provider Form -->
                <form id="addProviderForm" class="mb-4">
                    <h5>Add New Provider</h5>
                    <div class="row mb-3">
                        <div class="col-md-5">
                            <label class="form-label">Short Name/ID</label>
                            <input type="text" class="form-control" name="short_name" placeholder="e.g., bKash, NAGAD" required>
                            <small class="text-muted">Used for SMS matching</small>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" placeholder="e.g., bKash" required>
                            <small class="text-muted">Display name</small>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Add</button>
                        </div>
                    </div>
                </form>

                <hr>

                <!-- Provider List -->
                <h5>Current Providers</h5>
                <div id="providersList" style="max-height: 400px; overflow-y: auto;">
                    <?php foreach ($providers as $short => $full): ?>
                    <div class="provider-card" data-short="<?= htmlspecialchars($short) ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= htmlspecialchars($full) ?></strong>
                                <br>
                                <small class="text-muted">Match ID: <code><?= htmlspecialchars($short) ?></code></small>
                            </div>
                            <button class="btn btn-sm btn-danger delete-provider" data-short="<?= htmlspecialchars($short) ?>">
                                Delete
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Format Management -->
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title mb-0">SMS Format Patterns</h4>
            </div>
            <div class="card-body">
                <!-- Add Format Form -->
                <form id="addFormatForm" class="mb-4">
                    <h5>Add New Format Pattern</h5>
                    <div class="mb-3">
                        <label class="form-label">Provider</label>
                        <select class="form-control" name="provider_name" required>
                            <option value="">Select Provider</option>
                            <?php foreach ($providers as $short => $full): ?>
                            <option value="<?= htmlspecialchars($full) ?>"><?= htmlspecialchars($full) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Format Type/ID</label>
                        <input type="text" class="form-control" name="format_type" placeholder="e.g., sms1, sms2" required>
                        <small class="text-muted">Identifier for this format</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Regex Pattern</label>
                        <textarea class="form-control" name="regex_pattern" rows="3" placeholder="e.g., /Cash In Tk (?<amount>[\d,]+\.\d{2}) from (?<mobile>\d+).../" required></textarea>
                        <small class="text-muted">Use named groups: (?&lt;amount&gt;), (?&lt;mobile&gt;), (?&lt;trxid&gt;), (?&lt;balance&gt;), (?&lt;datetime&gt;)</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Format</button>
                    <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#testRegexModal">Test Regex</button>
                </form>

                <hr>

                <!-- Format List -->
                <h5>Current Format Patterns</h5>
                <div id="formatsList" style="max-height: 500px; overflow-y: auto;">
                    <?php foreach ($provider_formats as $provider => $formats): ?>
                        <div class="mb-3">
                            <h6 class="badge-provider"><?= htmlspecialchars($provider) ?></h6>
                            <?php foreach ($formats as $index => $format): ?>
                            <div class="format-card" data-provider="<?= htmlspecialchars($provider) ?>" data-index="<?= $index ?>">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <strong>Type: <?= htmlspecialchars($format['type']) ?></strong>
                                    <div>
                                        <button class="btn btn-sm btn-primary edit-format me-1" 
                                                data-provider="<?= htmlspecialchars($provider) ?>" 
                                                data-index="<?= $index ?>"
                                                data-type="<?= htmlspecialchars($format['type']) ?>"
                                                data-format="<?= htmlspecialchars($format['format']) ?>">
                                            Edit
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-format" 
                                                data-provider="<?= htmlspecialchars($provider) ?>" 
                                                data-index="<?= $index ?>">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                                <div class="regex-display">
                                    <?= htmlspecialchars($format['format']) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Test Regex Modal -->
<div class="modal fade" id="testRegexModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test Regex Pattern</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="testRegexForm">
                    <div class="mb-3">
                        <label class="form-label">Regex Pattern</label>
                        <textarea class="form-control" id="test_pattern" rows="3" placeholder="Enter regex pattern..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sample SMS Text</label>
                        <textarea class="form-control" id="test_sample" rows="4" placeholder="Paste sample SMS here..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Test Pattern</button>
                </form>
                <div id="testResult" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Import Settings Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <strong>Note:</strong> Importing will replace all current providers and formats with the imported data.
                </div>
                <form id="importSettingsForm">
                    <div class="mb-3">
                        <label class="form-label">Paste JSON Data</label>
                        <textarea class="form-control" id="import_data" rows="10" placeholder='{"providers":{...},"provider_formats":{...}}' required></textarea>
                        <small class="text-muted">Paste the JSON export data here</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Import Settings</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Format Modal -->
<div class="modal fade" id="editFormatModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Format Pattern</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editFormatForm">
                    <input type="hidden" id="edit_provider_name" name="provider_name">
                    <input type="hidden" id="edit_format_index" name="format_index">
                    
                    <div class="mb-3">
                        <label class="form-label">Provider</label>
                        <input type="text" class="form-control" id="edit_provider_display" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Format Type/ID</label>
                        <input type="text" class="form-control" id="edit_format_type" name="format_type" placeholder="e.g., sms1, sms2" required>
                        <small class="text-muted">Identifier for this format</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Regex Pattern</label>
                        <textarea class="form-control" id="edit_regex_pattern" name="regex_pattern" rows="5" placeholder="e.g., /Cash In Tk (?<amount>[\d,]+\.\d{2}) from (?<mobile>\d+).../" required></textarea>
                        <small class="text-muted">Use named groups: (?&lt;amount&gt;), (?&lt;mobile&gt;), (?&lt;trxid&gt;), (?&lt;balance&gt;), (?&lt;datetime&gt;)</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Format</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Define AJAX endpoint - use separate handler to avoid HTML output
    const ajaxUrl = '<?php echo pp_get_site_url(); ?>/pp-content/plugins/modules/mfs-provider-manager/ajax-handler.php';
    
    // Add Provider
    $('#addProviderForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: $(this).serialize() + '&ajax_action=add_provider',
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                alert('An error occurred. Please try again.');
            }
        });
    });

    // Delete Provider
    $(document).on('click', '.delete-provider', function() {
        if (!confirm('Are you sure you want to delete this provider?')) return;
        
        const shortName = $(this).data('short');
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                ajax_action: 'delete_provider',
                short_name: shortName
            },
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                alert('An error occurred. Please try again.');
            }
        });
    });

    // Add Format
    $('#addFormatForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: $(this).serialize() + '&ajax_action=add_format',
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                alert('An error occurred. Please try again.');
            }
        });
    });

    // Edit Format
    $(document).on('click', '.edit-format', function() {
        const provider = $(this).data('provider');
        const index = $(this).data('index');
        const type = $(this).data('type');
        const format = $(this).data('format');
        
        // Populate modal fields
        $('#edit_provider_name').val(provider);
        $('#edit_provider_display').val(provider);
        $('#edit_format_index').val(index);
        $('#edit_format_type').val(type);
        $('#edit_regex_pattern').val(format);
        
        // Show modal
        $('#editFormatModal').modal('show');
    });
    
    // Update Format
    $('#editFormatForm').on('submit', function(e) {
        e.preventDefault();
        
        const provider = $('#edit_provider_name').val();
        const index = $('#edit_format_index').val();
        const type = $('#edit_format_type').val();
        const pattern = $('#edit_regex_pattern').val();
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                ajax_action: 'update_format',
                provider_name: provider,
                format_index: index,
                format_type: type,
                regex_pattern: pattern
            },
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    alert(response.message);
                    $('#editFormatModal').modal('hide');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                alert('An error occurred. Please try again.');
            }
        });
    });
    
    // Delete Format
    $(document).on('click', '.delete-format', function() {
        if (!confirm('Are you sure you want to delete this format?')) return;
        
        const provider = $(this).data('provider');
        const index = $(this).data('index');
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                ajax_action: 'delete_format',
                provider_name: provider,
                format_index: index
            },
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                alert('An error occurred. Please try again.');
            }
        });
    });

    // Test Regex
    $('#testRegexForm').on('submit', function(e) {
        e.preventDefault();
        
        const pattern = $('#test_pattern').val();
        const sampleText = $('#test_sample').val();
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                ajax_action: 'test_regex',
                pattern: pattern,
                sample_text: sampleText
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let html = '<div class="alert alert-success"><strong>Match Found!</strong><br><br>';
                    html += '<strong>Captured Groups:</strong><pre>' + JSON.stringify(response.matches, null, 2) + '</pre>';
                    html += '</div>';
                    $('#testResult').html(html);
                } else {
                    $('#testResult').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                alert('An error occurred. Please try again.');
            }
        });
    });
    
    // Reset to Defaults
    $('#resetToDefaultsBtn').on('click', function() {
        if (!confirm('Are you sure you want to reset all providers and formats to defaults? This will delete all custom entries.')) return;
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                ajax_action: 'reset_to_defaults'
            },
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                alert('An error occurred. Please try again.');
            }
        });
    });
    
    // Export Settings
    $('#exportSettingsBtn').on('click', function() {
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                ajax_action: 'export_settings'
            },
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    const jsonStr = JSON.stringify(response.data, null, 2);
                    const blob = new Blob([jsonStr], { type: 'application/json' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'mfs-provider-settings-' + new Date().toISOString().split('T')[0] + '.json';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                    alert('Settings exported successfully!');
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                alert('An error occurred. Please try again.');
            }
        });
    });
    
    // Show Import Modal
    $('#importSettingsBtn').on('click', function() {
        $('#importModal').modal('show');
    });
    
    // Import Settings
    $('#importSettingsForm').on('submit', function(e) {
        e.preventDefault();
        
        const importData = $('#import_data').val();
        
        // Validate JSON
        try {
            JSON.parse(importData);
        } catch (e) {
            alert('Invalid JSON format. Please check your data.');
            return;
        }
        
        if (!confirm('Are you sure you want to import these settings? This will replace all current providers and formats.')) return;
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                ajax_action: 'import_settings',
                import_data: importData
            },
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    alert(response.message);
                    $('#importModal').modal('hide');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                alert('An error occurred. Please try again.');
            }
        });
    });
    
    // Check for Updates
    $('#checkForUpdatesBtn').on('click', function() {
        const button = $(this);
        const responseContainer = $('#updateCheckResponse');
        const updateCard = $('#updateCheckCard');
        const originalButtonText = button.html();
        
        button.html('<span class="spinner-border spinner-border-sm"></span> Checking...').prop('disabled', true);
        responseContainer.html('');
        
        // Show the card
        updateCard.collapse('show');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: { ajax_action: 'check_for_updates' },
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    if (response.update_available) {
                        const update = response.data;
                        const changelog = update.changelog.replace(/\n/g, '<br>');
                        const updateHtml = `
                            <div class="alert alert-info">
                                <button type="button" class="btn-close float-end" data-bs-dismiss="alert"></button>
                                <h4 class="alert-heading">🚀 New Version Available!</h4>
                                <p>A new version (<strong>${update.new_version}</strong>) is available.</p>
                                <hr>
                                <h5>Release Notes:</h5>
                                <div>${changelog}</div>
                                <a href="${update.download_url}" class="btn btn-success mt-3" target="_blank">
                                    <i class="bi-download"></i> Download Update
                                </a>
                            </div>`;
                        responseContainer.html(updateHtml);
                    } else {
                        const noUpdateHtml = `
                            <div class="alert alert-success">
                                <button type="button" class="btn-close float-end" data-bs-dismiss="alert"></button>
                                <strong>✓ Up to date!</strong> ${response.message}
                            </div>`;
                        responseContainer.html(noUpdateHtml);
                    }
                } else {
                     responseContainer.html(`
                        <div class="alert alert-danger">
                            <button type="button" class="btn-close float-end" data-bs-dismiss="alert"></button>
                            <strong>Error:</strong> ${response.message}
                        </div>`);
                }
            },
            error: function() {
                responseContainer.html(`
                    <div class="alert alert-danger">
                        <button type="button" class="btn-close float-end" data-bs-dismiss="alert"></button>
                        <strong>Error:</strong> An unexpected error occurred.
                    </div>`);
            },
            complete: function() {
                button.html(originalButtonText).prop('disabled', false);
            }
        });
    });
});
</script>
