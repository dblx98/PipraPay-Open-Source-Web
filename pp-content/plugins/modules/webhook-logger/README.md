# Webhook Logger (Example Plugin)

This is an example plugin that demonstrates how to process incoming webhooks without modifying `index.php`.

## Purpose

This plugin serves as a reference implementation showing:

1. How to register webhook processing hooks
2. How to capture webhook data (headers, POST data, raw payload)
3. How to log webhook events for debugging
4. How to use both `pp_webhook_received` and `pp_webhook_processed` hooks

## Features

- Logs all incoming webhook requests to a file
- Captures request headers, POST data, and raw input
- Demonstrates proper hook registration
- Shows how to access webhook parameters

## Log File Location

All webhook requests are logged to:
```
pp-content/plugins/modules/webhook-logger/webhook-requests.log
```

## Usage

1. Install the plugin by copying it to `pp-content/plugins/modules/webhook-logger/`
2. Activate the plugin from Admin Panel → More → Modules
3. Send webhook requests to `https://yoursite.com/?webhook=your-webhook-key`
4. Check the log file to see captured webhook data

## Example Log Entry

```
================================================================================
[2025-10-15 08:23:46] WEBHOOK REQUEST RECEIVED
================================================================================
Webhook Key: abc123xyz
Remote IP: 192.168.1.100
User Agent: MyService/1.0

--- HEADERS ---
Content-Type: application/json
X-Signature: sha256=...

--- POST DATA ---
{
  "event": "payment.completed",
  "amount": 100.00
}

--- RAW INPUT ---
{"event":"payment.completed","amount":100.00}

[2025-10-15 08:23:46] WEBHOOK PROCESSING COMPLETED
Webhook Key: abc123xyz
Device Status: Connected
================================================================================
```

## For Production Use

This is a **debugging/example plugin** and should not be used in production as-is. For production:

1. Add log rotation to prevent the log file from growing too large
2. Add log filtering to exclude sensitive data
3. Consider using a proper logging library
4. Add access controls for viewing logs

## Learn More

See the [Webhook Processing Plugin Guide](../../../docs/Webhook-Processing-Plugin-Guide.md) for complete documentation on creating webhook processing plugins.

## License

GPL-2.0+
