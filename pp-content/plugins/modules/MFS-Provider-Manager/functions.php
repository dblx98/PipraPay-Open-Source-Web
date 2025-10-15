<?php
    if (!defined('pp_allowed_access')) {
        die('Direct access not allowed');
    }

/**
 * Get all MFS providers from plugin settings
 * 
 * @return array Array of providers
 */
function mfs_get_providers() {
    $settings = pp_get_plugin_setting('mfs-provider-manager');
    
    // Return default providers if no custom ones exist
    if (empty($settings['providers'])) {
        return mfs_get_default_providers();
    }
    
    return json_decode($settings['providers'], true) ?: mfs_get_default_providers();
}

/**
 * Get default MFS providers (fallback)
 * 
 * @return array Default providers
 */
function mfs_get_default_providers() {
    return [
        'NAGAD' => 'Nagad',
        'Nagad' => 'Nagad',
        'bKash' => 'bKash',
        '16216' => 'Rocket',
        'upay' => 'Upay',
        'tap.' => 'Tap',
        '16269' => 'OkWallet',
        'IBBL .' => 'Cellfin',
        'IPAY' => 'Ipay',
        'iPAY' => 'Ipay',
        'PathaoPay' => 'Pathao Pay'
    ];
}

/**
 * Get provider formats from plugin settings
 * 
 * @return array Array of provider formats with regex patterns
 */
function mfs_get_provider_formats() {
    $settings = pp_get_plugin_setting('mfs-provider-manager');
    
    // Return default formats if no custom ones exist
    if (empty($settings['provider_formats'])) {
        return mfs_get_default_formats();
    }
    
    return json_decode($settings['provider_formats'], true) ?: mfs_get_default_formats();
}

/**
 * Get default provider formats (fallback)
 * 
 * @return array Default provider formats
 */
function mfs_get_default_formats() {
    return [
        'bKash' => [
            [
                'type' => 'sms1',
                'format' => '/Cash In Tk (?<amount>[\d,]+\.\d{2}) from (?<mobile>\d+) successful\. Fee Tk (?<fee>[\d,]+\.\d{2})\. Balance Tk (?<balance>[\d,]+\.\d{2})\. TrxID (?<trxid>\w+) at (?<datetime>\d{2}\/\d{2}\/\d{4} \d{2}:\d{2})/'
            ],
            [
                'type' => 'sms2',
                'format' => '/You have received Tk (?<amount>[\d,]+\.\d{2}) from (?<mobile>\d+)\. Fee Tk (?<fee>[\d,]+\.\d{2})\. Balance Tk (?<balance>[\d,]+\.\d{2})\. TrxID (?<trxid>\w+) at (?<datetime>\d{2}\/\d{2}\/\d{4} \d{2}:\d{2})/'
            ],
            [
                'type' => 'sms3',
                'format' => '/You have received Tk (?<amount>[\d,]+\.\d{2}) from (?<mobile>\d+)\. Ref .*?Fee Tk (?<fee>[\d,]+\.\d{2})\. Balance Tk (?<balance>[\d,]+\.\d{2})\. TrxID (?<trxid>\w+) at (?<datetime>\d{2}\/\d{2}\/\d{4} \d{2}:\d{2})/'
            ],
            [
                'type' => 'sms4',
                'format' => '/You have received payment Tk (?<amount>[\d,]+\.\d{2}) from (?<mobile>\d+)\. Fee Tk (?<fee>[\d,]+\.\d{2})\. Balance Tk (?<balance>[\d,]+\.\d{2})\. TrxID (?<trxid>\w+) at (?<datetime>\d{2}\/\d{2}\/\d{4} \d{2}:\d{2})/'
            ],
            [
                'type' => 'sms5',
                'format' => '/You have received Tk (?<amount>[\d,]+\.\d{2}) from (?<mobile>\d+)\.Ref .*?TrxID (?<trxid>\w+) at (?<datetime>\d{2}\/\d{2}\/\d{4} \d{2}:\d{2})/'
            ]
        ],
        'Nagad' => [
            [
                'type' => 'sms1',
                'format' => '/Cash In Received\.\nAmount: Tk (?<amount>[\d,]+\.\d{2})\nUddokta: (?<mobile>\d+)\nTxnID: (?<trxid>[A-Z0-9]+)\nBalance: (?<balance>[\d,]+\.\d{2})\n(?<date>\d{2}\/\d{2}\/\d{4}) (?<time>\d{2}:\d{2})/'
            ],
            [
                'type' => 'sms2',
                'format' => '/Money Received\.\nAmount: Tk (?<amount>[\d,]+\.\d{2})\nSender: (?<mobile>\d+)\nRef: (.+)\nTxnID: (?<trxid>\w+)\nBalance: Tk (?<balance>[\d,]+\.\d{2})\n(?<date>\d{2}\/\d{2}\/\d{4}) (?<time>\d{2}:\d{2})/'
            ]
        ],
        'Upay' => [
            [
                'type' => 'sms1',
                'format' => '/Cash In Received\.\nAmount: Tk (?<amount>[\d,]+\.\d{2})\nUddokta: (?<mobile>\d+)\nTxnID: (?<trxid>[A-Z0-9]+)\nBalance: (?<balance>[\d,]+\.\d{2})\n(?<date>\d{2}\/\d{2}\/\d{4}) (?<time>\d{2}:\d{2})/'
            ],
            [
                'type' => 'sms2',
                'format' => '/Tk\. (?<amount>[\d,]+\.\d{2}) has been received from (?<mobile>\d+)\. Ref-.*?Balance Tk\. (?<balance>[\d,]+\.\d{2})\. TrxID (?<trxid>\w+) at (?<datetime>\d{2}\/\d{2}\/\d{4} \d{2}:\d{2})\./'
            ]
        ],
        'Rocket' => [
            [
                'type' => 'sms1',
                'format' => '/Tk(?<amount>[\d,]+\.\d{2}) received from A\/C:(?<mobile>[\*\d]+) Fee:Tk(?<fee>[\d,]+\.\d{2})?, Your A\/C Balance: Tk(?<balance>[\d,]+\.\d{2}) TxnId:(?<trxid>\w+) Date:(?<datetime>\d{2}-[A-Z]{3}-\d{2} \d{2}:\d{2}:\d{2} [ap]m)/i'
            ],
            [
                'type' => 'sms2',
                'format' => '/Tk(?<amount>[\d,]+\.\d{2}) received from A\/C:(?<mobile>[\*\d]+) Fee:Tk(?<fee>[\d,]+\.\d{2})?, Your A\/C Balance: Tk(?<balance>[\d,]+\.\d{2}) TxnId:(?<trxid>\w+) Date:(?<datetime>\d{2}-[A-Z]{3}-\d{2} \d{2}:\d{2}:\d{2} [ap]m)/i'
            ]
        ]
    ];
}

/**
 * Initialize plugin on first activation - save defaults to settings
 */
function mfs_initialize_defaults() {
    $settings = pp_get_plugin_setting('mfs-provider-manager');
    
    // Only initialize if not already done
    if (empty($settings['initialized'])) {
        // Save default providers and formats
        $settings['providers'] = json_encode(mfs_get_default_providers());
        $settings['provider_formats'] = json_encode(mfs_get_default_formats());
        $settings['initialized'] = 'true';
        
        pp_set_plugin_setting('mfs-provider-manager', $settings);
    }
}

// Initialize defaults when plugin is first loaded
mfs_initialize_defaults();

/**
 * Hook into the webhook processing to inject custom providers and formats
 */
add_action('pp_before_webhook_process', 'mfs_inject_providers');

function mfs_inject_providers() {
    global $mfs_providers, $provider_formats;
    
    // Get custom providers and formats
    $custom_providers = mfs_get_providers();
    $custom_formats = mfs_get_provider_formats();
    
    // Merge with existing (if any)
    if (isset($mfs_providers) && is_array($mfs_providers)) {
        $mfs_providers = array_merge($mfs_providers, $custom_providers);
    } else {
        $mfs_providers = $custom_providers;
    }
    
    if (isset($provider_formats) && is_array($provider_formats)) {
        $provider_formats = array_merge($provider_formats, $custom_formats);
    } else {
        $provider_formats = $custom_formats;
    }
}

/**
 * Save MFS provider
 */
function mfs_save_provider($short_name, $full_name) {
    $settings = pp_get_plugin_setting('mfs-provider-manager');
    $providers = json_decode($settings['providers'] ?? '{}', true) ?: [];
    
    $providers[$short_name] = $full_name;
    
    $settings['providers'] = json_encode($providers);
    pp_set_plugin_setting('mfs-provider-manager', $settings);
    
    return true;
}

/**
 * Delete MFS provider
 */
function mfs_delete_provider($short_name) {
    $settings = pp_get_plugin_setting('mfs-provider-manager');
    $providers = json_decode($settings['providers'] ?? '{}', true) ?: [];
    
    if (isset($providers[$short_name])) {
        unset($providers[$short_name]);
        $settings['providers'] = json_encode($providers);
        pp_set_plugin_setting('mfs-provider-manager', $settings);
        return true;
    }
    
    return false;
}

/**
 * Save MFS provider format
 */
function mfs_save_format($provider_name, $format_type, $regex_pattern) {
    $settings = pp_get_plugin_setting('mfs-provider-manager');
    $formats = json_decode($settings['provider_formats'] ?? '{}', true) ?: [];
    
    if (!isset($formats[$provider_name])) {
        $formats[$provider_name] = [];
    }
    
    $formats[$provider_name][] = [
        'type' => $format_type,
        'format' => $regex_pattern
    ];
    
    $settings['provider_formats'] = json_encode($formats);
    pp_set_plugin_setting('mfs-provider-manager', $settings);
    
    return true;
}

/**
 * Update MFS provider format
 */
function mfs_update_format($provider_name, $format_index, $format_type, $regex_pattern) {
    $settings = pp_get_plugin_setting('mfs-provider-manager');
    $formats = json_decode($settings['provider_formats'] ?? '{}', true) ?: [];
    
    if (isset($formats[$provider_name][$format_index])) {
        $formats[$provider_name][$format_index] = [
            'type' => $format_type,
            'format' => $regex_pattern
        ];
        
        $settings['provider_formats'] = json_encode($formats);
        pp_set_plugin_setting('mfs-provider-manager', $settings);
        return true;
    }
    
    return false;
}

/**
 * Delete MFS provider format
 */
function mfs_delete_format($provider_name, $format_index) {
    $settings = pp_get_plugin_setting('mfs-provider-manager');
    $formats = json_decode($settings['provider_formats'] ?? '{}', true) ?: [];
    
    if (isset($formats[$provider_name][$format_index])) {
        unset($formats[$provider_name][$format_index]);
        $formats[$provider_name] = array_values($formats[$provider_name]);
        
        if (empty($formats[$provider_name])) {
            unset($formats[$provider_name]);
        }
        
        $settings['provider_formats'] = json_encode($formats);
        pp_set_plugin_setting('mfs-provider-manager', $settings);
        return true;
    }
    
    return false;
}

/**
 * Test regex pattern against sample SMS
 */
function mfs_test_regex($pattern, $sample_text) {
    try {
        $matches = [];
        if (preg_match($pattern, $sample_text, $matches)) {
            return [
                'success' => true,
                'matches' => $matches
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Pattern did not match the sample text'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Invalid regex pattern: ' . $e->getMessage()
        ];
    }
}

/**
 * Reset providers and formats to defaults
 */
function mfs_reset_to_defaults() {
    $settings = pp_get_plugin_setting('mfs-provider-manager');
    
    // Reset to default providers and formats
    $settings['providers'] = json_encode(mfs_get_default_providers());
    $settings['provider_formats'] = json_encode(mfs_get_default_formats());
    
    pp_set_plugin_setting('mfs-provider-manager', $settings);
    
    return true;
}

/**
 * Export current settings as JSON
 */
function mfs_export_settings() {
    $settings = pp_get_plugin_setting('mfs-provider-manager');
    
    return [
        'providers' => json_decode($settings['providers'] ?? '{}', true),
        'provider_formats' => json_decode($settings['provider_formats'] ?? '{}', true)
    ];
}

/**
 * Import settings from JSON
 */
function mfs_import_settings($import_data) {
    if (empty($import_data)) {
        return false;
    }
    
    $settings = pp_get_plugin_setting('mfs-provider-manager');
    
    if (isset($import_data['providers'])) {
        $settings['providers'] = json_encode($import_data['providers']);
    }
    
    if (isset($import_data['provider_formats'])) {
        $settings['provider_formats'] = json_encode($import_data['provider_formats']);
    }
    
    pp_set_plugin_setting('mfs-provider-manager', $settings);
    
    return true;
}

function mfs_check_for_github_updates() {
    $current_version = '1.0.3'; 
    $github_repo = 'dblx98/MFS-Provider-Manager';

    $api_url = "https://api.github.com/repos/{$github_repo}/releases/latest";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => 'PipraPay Plugin Update Checker'
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $release_data = json_decode($response, true);

        if (isset($release_data['tag_name'])) {
            $latest_version = ltrim($release_data['tag_name'], 'v');

            if (version_compare($latest_version, $current_version, '>')) {
                $download_url = '';
                if (!empty($release_data['assets'])) {
                    foreach ($release_data['assets'] as $asset) {
                        if (strpos($asset['name'], '.zip') !== false) {
                            $download_url = $asset['browser_download_url'];
                            break;
                        }
                    }
                }
                
                return [
                    'new_version' => $latest_version,
                    'download_url' => $download_url,
                    'changelog' => $release_data['body']
                ];
            }
        }
    }

    return null;
}