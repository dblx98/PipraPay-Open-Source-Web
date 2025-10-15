=== MFS Provider Manager ===
Contributors: PipraPay Community
Tags: mfs, provider, sms, payment, mobile financial service
Requires at least: 1.0.0
Tested up to: 1.0.0
Requires PHP: 7.4
Stable tag: 1.0.3
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Powerful module to manage MFS providers and their SMS format patterns without touching core code.

== Description ==

The MFS Provider Manager plugin allows administrators to add, edit, and manage Mobile Financial Service (MFS) providers and their SMS parsing regex patterns through an intuitive admin interface.

**Key Features:**

* Add custom MFS providers with short names and full names
* Define multiple SMS format patterns per provider using regex
* Test regex patterns against sample SMS messages
* Delete providers and formats
* Automatically integrates with PipraPay's webhook SMS processing
* No core file modifications required

**Use Cases:**

* Add new MFS providers not included by default
* Update SMS formats when providers change their message structure
* Support regional or custom payment providers
* Test regex patterns before deploying to production

== Installation ==

1. Upload the `mfs-provider-manager` folder to `pp-content/plugins/modules/`
2. Navigate to Admin > More > Modules
3. Find "MFS Provider Manager" and click Activate
4. Access the plugin from the Modules menu

== Usage ==

**Adding a Provider:**

1. Go to MFS Provider Manager in admin menu
2. Enter Short Name (e.g., "bKash", "NAGAD") - used for SMS matching
3. Enter Full Name (e.g., "bKash") - display name
4. Click "Add"

**Adding SMS Format Pattern:**

1. Select the provider from dropdown
2. Enter Format Type (e.g., "sms1", "sms2")
3. Enter Regex Pattern with named groups:
   - (?<amount>) - Transaction amount
   - (?<mobile>) - Mobile number
   - (?<trxid>) - Transaction ID
   - (?<balance>) - Account balance
   - (?<datetime>) - Transaction date/time
   - (?<date>) and (?<time>) - Separate date and time
4. Click "Add Format"

**Testing Regex:**

1. Click "Test Regex" button
2. Enter your regex pattern
3. Paste a sample SMS message
4. Click "Test Pattern" to see if it matches and what groups are captured

== Regex Pattern Guidelines ==

* Use PHP PCRE regex syntax
* Always use named capture groups: (?<name>pattern)
* Required groups: amount, mobile, trxid
* Optional groups: balance, fee, datetime, date, time
* Escape special characters: \. \( \) \/ etc.
* Use \d for digits, \w for word characters
* Use [\d,]+ for numbers with commas
* Use .+? or .*? for non-greedy matching

**Example Pattern:**

```
/Cash In Tk (?<amount>[\d,]+\.\d{2}) from (?<mobile>\d+) successful\. Fee Tk (?<fee>[\d,]+\.\d{2})\. Balance Tk (?<balance>[\d,]+\.\d{2})\. TrxID (?<trxid>\w+) at (?<datetime>\d{2}\/\d{2}\/\d{4} \d{2}:\d{2})/
```

== Frequently Asked Questions ==

= How do I add a new provider? =

Use the "Add New Provider" form in the admin interface. Enter both short name (for matching) and full name (for display).

= What if my regex pattern doesn't work? =

Use the "Test Regex" feature to validate your pattern against sample SMS messages before saving.

= Can I delete default providers? =

Yes, but be careful. The plugin stores your custom providers separately, so you can always reset by deleting and re-activating the plugin.

= How does this integrate with core? =

The plugin hooks into the webhook processing using the `pp_before_webhook_process` action to inject custom providers and formats.

= Where is the data stored? =

All provider and format data is stored in the plugin settings (plugin_array column) in the database.

== Changelog ==

= 1.0.3 =
* Added Edit button for format patterns - modify existing formats without deleting
* Edit format modal with pre-filled data for easy updates
* Update format type and regex pattern for any saved format
* Improved user experience - no need to delete and re-add formats

= 1.0.2 =
* Auto-save default providers and formats to database on first activation
* Added Reset to Defaults button - restore original settings anytime
* Added Export button - download settings as JSON backup
* Added Import button - restore settings from JSON file
* Settings now persist in pp_plugins table for better data management
* Better backup and recovery options for configuration

= 1.0.1 =
* Fixed AJAX responses returning HTML instead of pure JSON
* Added separate ajax-handler.php for clean JSON responses
* Improved error logging in JavaScript
* Better separation of UI and AJAX logic

= 1.0.0 =
* Initial release
* Add/delete MFS providers
* Add/delete SMS format patterns
* Test regex patterns
* Integration with webhook processing

== Upgrade Notice ==

= 1.0.0 =
Initial release of MFS Provider Manager plugin.

== Developer Notes ==

**Functions Available:**

* `mfs_get_providers()` - Get all providers
* `mfs_get_provider_formats()` - Get all format patterns
* `mfs_save_provider($short, $full)` - Save a provider
* `mfs_delete_provider($short)` - Delete a provider
* `mfs_save_format($provider, $type, $regex)` - Save a format
* `mfs_delete_format($provider, $index)` - Delete a format
* `mfs_test_regex($pattern, $text)` - Test a regex pattern

**Hooks:**

* `pp_before_webhook_process` - Used to inject custom providers

**Database:**

Data is stored in JSON format in the plugins table under the `mfs-provider-manager` slug.

== Credits ==

Developed for the PipraPay community to enable flexible MFS provider management.
