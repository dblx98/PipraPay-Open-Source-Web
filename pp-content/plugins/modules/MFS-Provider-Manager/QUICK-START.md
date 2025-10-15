# MFS Provider Manager - Quick Start Guide

## 🚀 Zero-Touch Webhook Integration

The MFS Provider Manager now handles ALL webhook processing automatically without requiring ANY modifications to your core files!

## ✅ What You Get

- ✨ **Automatic webhook handling** - No code changes needed
- 🔧 **Manage providers via UI** - Add/edit MFS providers without touching code
- 🎯 **Custom regex patterns** - Configure SMS parsing through admin panel
- 🔄 **Zero maintenance** - Update patterns anytime without redeployment
- 🛡️ **Fully isolated** - All code stays in the module folder

## 📦 Quick Setup (3 Steps)

### Step 1: Activate the Module

1. Go to **Admin Panel** → **Plugins**
2. Find **MFS Provider Manager**
3. Click **Activate**

✅ **Done!** The webhook handler is now active.

### Step 2: Configure Providers (Optional)

The module comes with default providers pre-configured:
- bKash
- Nagad
- Rocket
- Upay
- Cellfin
- Tap
- OkWallet
- Ipay
- Pathao Pay

To add more:
1. Go to **Plugins** → **MFS Provider Manager**
2. Click **Add Provider**
3. Enter Short Name (e.g., "16216") and Full Name (e.g., "Rocket")
4. Click **Save**

### Step 3: Test Your Webhook

Send a test SMS to your webhook URL:
```
https://yourdomain.com/?webhook=your_webhook_key
```

The SMS will be automatically:
- ✅ Detected for provider type
- ✅ Parsed using configured patterns
- ✅ Stored in the database
- ✅ Marked as "approved" or "review"

## 🎯 How It Works

### Before (Manual Process)

```php
// You had to manually add providers and formats in index.php:
$mfs_providers = [
    'NAGAD' => 'Nagad',
    'bKash' => 'bKash',
    // Adding more? Edit code, test, deploy... 😓
];

$provider_formats = [
    'bKash' => [
        ['format' => '/complicated regex here/']
        // Need new format? Edit code again... 😓
    ]
];
```

### After (Automatic Process)

```php
// Nothing! The module handles everything automatically! 🎉
// Just use the admin UI to manage providers and patterns.
```

## 📊 Architecture

```
┌──────────────────────────────────────────────────┐
│                   Mobile Device                  │
│               Sends SMS to Server                │
└─────────────────────┬────────────────────────────┘
                      │
                      ↓
┌──────────────────────────────────────────────────┐
│              PipraPay index.php                  │
│             Receives ?webhook=key                │
└─────────────────────┬────────────────────────────┘
                      │
                      ↓
┌──────────────────────────────────────────────────┐
│           Plugin System (Hook: pp_init)          │
│         Triggers all registered modules          │
└─────────────────────┬────────────────────────────┘
                      │
                      ↓
┌──────────────────────────────────────────────────┐
│        MFS Provider Manager (Priority 1)         │
│            webhook-handler.php                   │
│                                                  │
│  ✓ Intercepts webhook request                   │
│  ✓ Validates webhook key                        │
│  ✓ Handles device pairing                       │
│  ✓ Identifies MFS provider                      │
│  ✓ Parses SMS using custom regex                │
│  ✓ Stores data in database                      │
│  ✓ Returns JSON response                        │
│  ✓ Exits (other code won't run)                 │
└──────────────────────────────────────────────────┘
```

## 🔧 Advanced Configuration

### Adding Custom SMS Formats

1. Go to **MFS Provider Manager**
2. Select a provider (e.g., "bKash")
3. Click **Add Format**
4. Enter regex pattern with named groups:
   ```regex
   /You have received Tk (?<amount>[\d,]+\.\d{2}) from (?<mobile>\d+)\. TrxID (?<trxid>\w+)/
   ```
5. Test with sample SMS
6. Save

### Required Named Groups

Your regex must capture these groups:
- `(?<amount>...)` - Transaction amount
- `(?<mobile>...)` - Sender mobile number
- `(?<trxid>...)` - Transaction ID
- `(?<balance>...)` - Current balance (optional)
- `(?<datetime>...)` or `(?<date>...)` + `(?<time>...)` - Timestamp

### Testing Patterns

Use the built-in pattern tester:
1. Enter your regex pattern
2. Paste a sample SMS
3. Click **Test**
4. See extracted values

## 🔍 Monitoring

### Check SMS Processing

Navigate to **SMS Data** to see:
- ✅ **Approved**: Successfully parsed SMS
- ⚠️ **Review**: Provider matched but parsing failed
- Status, amount, transaction ID, etc.

### Debug Mode

Check logs if SMS isn't being processed:

```php
// Check PHP error log
tail -f /var/log/php/error.log

// Check database
SELECT * FROM pp_sms_data ORDER BY created_at DESC LIMIT 10;
```

## 🛠️ Troubleshooting

### Webhook Not Processing

**Problem**: SMS not appearing in database

**Solutions**:
1. ✅ Verify module is **activated**
2. ✅ Check webhook key is correct
3. ✅ Verify User-Agent is `mh-piprapay-api-key`
4. ✅ Check PHP error logs

### SMS Status is "Review"

**Problem**: SMS stored but not parsed (status = "review")

**Solutions**:
1. ✅ Check actual SMS format matches your regex
2. ✅ Test pattern using pattern tester
3. ✅ Add new pattern for this SMS format
4. ✅ Escape special regex characters

### Provider Not Detected

**Problem**: SMS not being stored at all

**Solutions**:
1. ✅ Add provider short name (check SMS sender)
2. ✅ Add alternative names (e.g., "NAGAD" and "Nagad")
3. ✅ Check if provider name appears in SMS text

## 📝 Examples

### Example 1: bKash Cash-In SMS

**SMS Text:**
```
Cash In Tk 1,500.00 from 01712345678 successful. Fee Tk 15.00. Balance Tk 8,234.50. TrxID ABC123XYZ at 15/10/2025 14:30
```

**Regex Pattern:**
```regex
/Cash In Tk (?<amount>[\d,]+\.\d{2}) from (?<mobile>\d+) successful\. Fee Tk (?<fee>[\d,]+\.\d{2})\. Balance Tk (?<balance>[\d,]+\.\d{2})\. TrxID (?<trxid>\w+) at (?<datetime>\d{2}\/\d{2}\/\d{4} \d{2}:\d{2})/
```

**Extracted Data:**
- Amount: `1500.00`
- Mobile: `01712345678`
- TrxID: `ABC123XYZ`
- Balance: `8234.50`
- DateTime: `2025-10-15 14:30:00`

### Example 2: Nagad Money Received SMS

**SMS Text:**
```
Money Received.
Amount: Tk 2,750.00
Sender: 01898765432
Ref: Payment for order
TxnID: NGD789456123
Balance: Tk 12,500.00
15/10/2025 16:45
```

**Regex Pattern:**
```regex
/Money Received\.\nAmount: Tk (?<amount>[\d,]+\.\d{2})\nSender: (?<mobile>\d+)\nRef: (.+)\nTxnID: (?<trxid>\w+)\nBalance: Tk (?<balance>[\d,]+\.\d{2})\n(?<date>\d{2}\/\d{2}\/\d{4}) (?<time>\d{2}:\d{2})/
```

**Note**: `\n` is automatically converted to `\s*` by the handler.

## 🎓 Best Practices

### 1. Test Before Deploy
- Always test regex patterns with the built-in tester
- Use actual SMS samples from your device

### 2. Handle Variations
- Add multiple patterns for the same provider
- MFS providers often change SMS formats

### 3. Keep Patterns Simple
- Focus on essential data (amount, mobile, trxid)
- Make optional fields truly optional with `?`

### 4. Use Named Groups
- Always use named groups: `(?<name>...)`
- Makes code maintainable

### 5. Monitor Regularly
- Check "review" status SMS weekly
- Add patterns for common formats

## 🔐 Security

The webhook handler includes:
- ✅ Webhook key validation
- ✅ User agent verification
- ✅ SQL injection prevention
- ✅ Input sanitization
- ✅ No direct file access
- ✅ Database-driven configuration

## 📚 Documentation

- **Full Guide**: See `WEBHOOK-INTEGRATION-GUIDE.md`
- **Integration Examples**: See `integration-example.php`
- **Code Reference**: See `webhook-handler.php` and `functions.php`

## 🆘 Support

Need help?
- 📖 Read the full integration guide
- 🐛 Report issues on GitHub
- 💬 Contact: [Saimun Bepari](https://saimun.dev/)

## 🎉 Summary

With MFS Provider Manager:
1. ✅ **Zero** core file modifications
2. ✅ **Complete** webhook handling
3. ✅ **Easy** provider management
4. ✅ **Flexible** pattern configuration
5. ✅ **Automatic** SMS parsing

**Just activate and go!** 🚀
