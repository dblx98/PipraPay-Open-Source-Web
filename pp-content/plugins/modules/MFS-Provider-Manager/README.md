# MFS Provider Manager Plugin

## Overview
A powerful module plugin for PipraPay that allows administrators to manage Mobile Financial Service (MFS) providers and their SMS format patterns through an intuitive admin interface without modifying core files.

## Features

✅ **Provider Management**
- Add custom MFS providers with short names (for SMS matching) and full names (for display)
- Delete existing providers
- View all configured providers

✅ **SMS Format Pattern Management**
- Add multiple regex patterns per provider
- Support for complex SMS parsing with named capture groups
- Delete format patterns
- Visual display of all patterns

✅ **Regex Testing Tool**
- Built-in regex tester with live validation
- Test patterns against sample SMS messages
- See captured groups before deployment

✅ **Seamless Integration**
- Hooks into PipraPay's webhook processing
- No core file modifications needed (only one hook added)
- Automatic injection of custom providers and formats

## Installation

### Method 1: Direct Upload
1. Download the repository: https://github.com/dblx98/MFS-Provider-Manager.git
2. Extract and upload the `mfs-provider-manager` folder to:
   ```
   pp-content/plugins/modules/mfs-provider-manager/
   ```

### Method 2: Admin Panel Upload
1. Download the ZIP file from: https://github.com/dblx98/MFS-Provider-Manager.git
2. Navigate to: **Admin > Customize > Plugin > Add New**
3. Choose the ZIP file you downloaded and upload

### Activation
1. Navigate to: **Admin > Customize > Plugin > Installed Plugin**
2. Find "MFS Provider Manager" and click **Activate**
3. Access from the Modules menu


## Core Modification

Only ONE hook was added to `index.php` to enable plugin integration:

```php
// Allow plugins to modify providers before processing
if (function_exists('pp_trigger_hook')) {
    pp_trigger_hook('pp_before_webhook_process');
}
```

This hook is placed right after the `$mfs_providers` array definition, allowing plugins to modify providers and formats before SMS processing.

📸 **[View index.php Hook Screenshot](https://github.com/dblx98/MFS-Provider-Manager/blob/main/assets/screenshots.png)**

## Usage Guide

### Adding a New Provider
1. Go to **MFS Provider Manager** in admin menu
2. Enter **Short Name/ID** (e.g., `bKash`, `NAGAD`) and **Full Name**
3. Click **Add**

### Adding SMS Format Patterns
1. Select provider from dropdown
2. Enter **Format Type/ID** and **Regex Pattern** with named capture groups
3. Available capture groups: `(?<amount>)`, `(?<mobile>)`, `(?<trxid>)`, `(?<balance>)`, `(?<fee>)`, `(?<datetime>)`
4. Click **Add Format**

### Testing Regex Patterns
1. Click **Test Regex** button, enter pattern and sample SMS
2. View match results and captured groups

## Regex Pattern Examples

### bKash Cash In
```regex
/Cash In Tk (?<amount>[\d,]+\.\d{2}) from (?<mobile>\d+) successful\. Fee Tk (?<fee>[\d,]+\.\d{2})\. Balance Tk (?<balance>[\d,]+\.\d{2})\. TrxID (?<trxid>\w+) at (?<datetime>\d{2}\/\d{2}\/\d{4} \d{2}:\d{2})/
```

### Nagad Money Received
```regex
/Money Received\.\nAmount: Tk (?<amount>[\d,]+\.\d{2})\nSender: (?<mobile>\d+)\nRef: (.+)\nTxnID: (?<trxid>\w+)\nBalance: Tk (?<balance>[\d,]+\.\d{2})\n(?<date>\d{2}\/\d{2}\/\d{4}) (?<time>\d{2}:\d{2})/
```

### Rocket Transfer
```regex
/Tk(?<amount>[\d,]+\.\d{2}) received from A\/C:(?<mobile>[\*\d]+) Fee:Tk(?<fee>[\d,]+\.\d{2})?, Your A\/C Balance: Tk(?<balance>[\d,]+\.\d{2}) TxnId:(?<trxid>\w+) Date:(?<datetime>\d{2}-[A-Z]{3}-\d{2} \d{2}:\d{2}:\d{2} [ap]m)/i
```

## Troubleshooting

- **Providers not appearing**: Ensure plugin is activated and data is saved
- **Regex not matching**: Use the Test Regex tool to validate patterns
- **Formats not working**: Verify provider names match exactly

## Support

For any issues or problems, please create an issue in the repository:  
**https://github.com/dblx98/MFS-Provider-Manager/issues**

## Credits

Developed by **[saimun.dev](https://saimun.dev)**

## License

GPL-2.0+ - Same as PipraPay core

---

**Created for PipraPay Community** 🚀
