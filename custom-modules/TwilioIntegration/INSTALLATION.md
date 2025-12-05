# Twilio Integration - Installation & Configuration Guide

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Webhook Setup](#webhook-setup)
5. [Scheduler Setup](#scheduler-setup)
6. [Testing](#testing)
7. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### Required
- SuiteCRM 7.x or 8.x
- PHP 7.4+ with curl extension
- MySQL/MariaDB database
- Twilio Account (free trial or paid)
- HTTPS-enabled domain (required for webhooks)

### Recommended
- Cron access for scheduled tasks
- Email configured in SuiteCRM for notifications
- At least 500MB free disk space for recordings

---

## Installation

### Step 1: Get Twilio Credentials

1. Sign up at [twilio.com](https://www.twilio.com/try-twilio)
2. Get your credentials from the Twilio Console:
   - **Account SID**: Found on dashboard (starts with `AC`)
   - **Auth Token**: Found on dashboard (click to reveal)
   - **Phone Number**: Purchase a number with Voice + SMS capabilities

### Step 2: Install the Module

The module is already installed in your `custom-modules/TwilioIntegration` directory.

#### Run Database Setup

Execute the SQL setup script:

```bash
mysql -u YOUR_DB_USER -p YOUR_DB_NAME < custom-modules/TwilioIntegration/install/twilio_setup.sql
```

Or via phpMyAdmin:
- Open phpMyAdmin
- Select your SuiteCRM database
- Click "SQL" tab
- Copy/paste contents of `install/twilio_setup.sql`
- Click "Go"

This creates:
- `twilio_audit_log` table
- `twilio_call_metrics` view
- `twilio_sms_metrics` view
- Phone number indexes for faster lookups

### Step 3: Configure SuiteCRM

Add Twilio credentials to your `config.php` or use environment variables.

#### Option A: config.php (Simple)

Add to your `config.php`:

```php
$sugar_config['twilio_account_sid'] = 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
$sugar_config['twilio_auth_token'] = 'your_auth_token_here';
$sugar_config['twilio_phone_number'] = '+15551234567';
$sugar_config['site_url'] = 'https://yourdomain.com/suitecrm';

// Optional settings
$sugar_config['twilio_enable_click_to_call'] = true;
$sugar_config['twilio_enable_auto_logging'] = true;
$sugar_config['twilio_enable_recordings'] = true;
$sugar_config['twilio_recording_path'] = 'upload/twilio_recordings';
$sugar_config['twilio_recording_retention_days'] = 365;
$sugar_config['twilio_sms_followup_hours'] = 24;
$sugar_config['twilio_sms_followup_email'] = true;
$sugar_config['twilio_auto_create_lead'] = true;
$sugar_config['twilio_fallback_phone'] = '+15559876543';

// Development mode (DISABLE IN PRODUCTION!)
$sugar_config['twilio_skip_validation'] = false; // Set to true only for testing
```

#### Option B: Environment Variables (Recommended for production)

Set environment variables in your `.htaccess` or server config:

```apache
SetEnv TWILIO_ACCOUNT_SID "ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
SetEnv TWILIO_AUTH_TOKEN "your_auth_token_here"
SetEnv TWILIO_PHONE_NUMBER "+15551234567"
```

Or in `.env` file (if using env loader):
```bash
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=your_auth_token_here
TWILIO_PHONE_NUMBER=+15551234567
```

### Step 4: Set Directory Permissions

```bash
mkdir -p upload/twilio_recordings
chmod 755 upload/twilio_recordings
chown www-data:www-data upload/twilio_recordings
```

---

## Webhook Setup

Webhooks allow Twilio to send real-time updates to your CRM.

### Get Your Webhook URLs

Your webhook URLs will be:

```
Voice Webhook:
https://yourdomain.com/suitecrm/index.php?module=TwilioIntegration&action=webhook

SMS Webhook:
https://yourdomain.com/suitecrm/index.php?module=TwilioIntegration&action=sms_webhook

Recording Webhook:
https://yourdomain.com/suitecrm/index.php?module=TwilioIntegration&action=recording_webhook
```

Replace `yourdomain.com/suitecrm` with your actual SuiteCRM URL.

### Configure in Twilio Console

#### For Voice Calls:

1. Go to [Twilio Console > Phone Numbers](https://console.twilio.com/phone-numbers)
2. Click on your phone number
3. Scroll to "Voice & Fax"
4. Set **A Call Comes In** to:
   - Webhook
   - `https://yourdomain.com/suitecrm/index.php?module=TwilioIntegration&action=webhook`
   - HTTP POST
5. Set **Primary Handler Fails** (optional) to same URL with `&webhook_action=fallback`
6. Click "Save"

#### For SMS:

1. Same phone number settings page
2. Scroll to "Messaging"
3. Set **A Message Comes In** to:
   - Webhook
   - `https://yourdomain.com/suitecrm/index.php?module=TwilioIntegration&action=sms_webhook`
   - HTTP POST
4. Click "Save"

### Test Webhooks

Test that webhooks are accessible:

```bash
curl -X POST "https://yourdomain.com/suitecrm/index.php?module=TwilioIntegration&action=webhook&webhook_action=status" \
  -d "CallSid=test" \
  -d "CallStatus=completed"
```

You should see a 403 error (signature validation). That's expected! Twilio will provide valid signatures.

---

## Scheduler Setup

The scheduler handles automated tasks like SMS follow-ups and recording cleanup.

### Option A: SuiteCRM Built-in Scheduler (Recommended)

1. Log in as Admin
2. Go to **Admin > Schedulers**
3. Click "Create Scheduler"
4. Add three schedulers:

#### Scheduler 1: SMS Follow-up Check
- Name: `Twilio SMS Follow-up Check`
- Job: `function::TwilioSchedulerJob::checkSMSFollowups`
- Interval: `0 * * * *` (every hour)
- Status: Active

#### Scheduler 2: Recording Cleanup
- Name: `Twilio Recording Cleanup`
- Job: `function::TwilioSchedulerJob::cleanupRecordings`
- Interval: `0 2 * * *` (daily at 2 AM)
- Status: Active

#### Scheduler 3: Daily Summary
- Name: `Twilio Daily Summary`
- Job: `function::TwilioSchedulerJob::generateDailySummary`
- Interval: `0 0 * * *` (daily at midnight)
- Status: Active

### Option B: System Cron Job

Add to your crontab:

```bash
crontab -e
```

Add this line:

```cron
0 * * * * cd /path/to/suitecrm && php custom/modules/TwilioIntegration/cron/twilio_tasks.php > /dev/null 2>&1
```

Replace `/path/to/suitecrm` with your actual path.

---

## Testing

### Test Click-to-Call

1. Go to a Lead or Contact record in DetailView
2. You should see call (📞) and SMS (💬) buttons next to phone numbers
3. Click the call button
4. A popup should open with the call interface
5. Click "Start Call"
6. Your Twilio number should ring, then connect to the lead

### Test Inbound Calls

1. Call your Twilio number from a phone
2. You should hear a greeting
3. If the number matches a lead/contact, you'll be routed to the assigned BDM
4. Check CRM - a Call record should be created
5. If unanswered, check for a Task created

### Test SMS

1. Click SMS button next to a phone number
2. Type a message
3. Click "Send Message"
4. Check the Notes module for the sent SMS
5. Reply from your phone
6. Check Notes - the reply should appear as a new Note

### Test Metrics API

Visit:
```
https://yourdomain.com/suitecrm/index.php?module=TwilioIntegration&action=metrics&type=summary
```

You should see JSON with call/SMS metrics.

---

## Troubleshooting

### Webhooks Not Working

**Problem**: Calls/SMS not logging in CRM

**Solutions**:
1. Check webhook URLs are correct in Twilio Console
2. Verify your domain is HTTPS (webhooks require SSL)
3. Check suitecrm.log for errors:
   ```bash
   tail -f suitecrm.log | grep Twilio
   ```
4. Temporarily disable signature validation for testing:
   ```php
   $sugar_config['twilio_skip_validation'] = true;
   ```
   **WARNING**: Re-enable after testing!

### 403 Forbidden on Webhooks

**Problem**: Webhooks return 403 error

**Cause**: This is NORMAL if signature validation is working. Twilio's requests will pass validation.

**Test**: Look for this in logs:
```
Twilio webhook: Signature validated successfully
```

### Recordings Not Downloading

**Problem**: Recording URLs in calls but files not saved

**Solutions**:
1. Check directory exists and is writable:
   ```bash
   ls -la upload/twilio_recordings
   ```
2. Check PHP has write permissions
3. Verify recording webhook is configured in call setup
4. Check logs for download errors

### SMS Follow-ups Not Creating

**Problem**: No follow-up tasks for old SMS

**Solutions**:
1. Verify scheduler is running:
   - Check Admin > Schedulers
   - Or run manually:
     ```bash
     php custom/modules/TwilioIntegration/cron/twilio_tasks.php
     ```
2. Check threshold setting:
   ```php
   $sugar_config['twilio_sms_followup_hours'] = 24;
   ```
3. Verify SMS were actually unreplied (no outbound SMS to same lead after inbound)

### Click-to-Call Buttons Not Appearing

**Problem**: No call/SMS buttons on phone fields

**Solutions**:
1. Clear browser cache
2. Check JavaScript console for errors
3. Verify click-to-call is enabled:
   ```php
   $sugar_config['twilio_enable_click_to_call'] = true;
   ```
4. Check that TwilioHooks is registered in logic_hooks.php
5. Run Quick Repair & Rebuild

### Metrics API Returns Empty Data

**Problem**: Metrics show zero for everything

**Cause**: No calls/SMS have been made yet, or date filter is too narrow

**Solutions**:
1. Make a test call first
2. Try different period: `&period=7days` or `&period=30days`
3. Check database has data:
   ```sql
   SELECT COUNT(*) FROM calls WHERE date_start >= DATE_SUB(NOW(), INTERVAL 7 DAY);
   ```

---

## Configuration Reference

### All Available Config Options

```php
// Required
$sugar_config['twilio_account_sid'] = 'ACxxxxx';
$sugar_config['twilio_auth_token'] = 'your_token';
$sugar_config['twilio_phone_number'] = '+15551234567';
$sugar_config['site_url'] = 'https://yourdomain.com/suitecrm';

// Click-to-Call
$sugar_config['twilio_enable_click_to_call'] = true;

// Auto-logging
$sugar_config['twilio_enable_auto_logging'] = true;

// Recordings
$sugar_config['twilio_enable_recordings'] = true;
$sugar_config['twilio_recording_path'] = 'upload/twilio_recordings';
$sugar_config['twilio_recording_retention_days'] = 365; // Days to keep recordings

// SMS Follow-up
$sugar_config['twilio_sms_followup_hours'] = 24; // Hours before follow-up task
$sugar_config['twilio_sms_followup_email'] = true; // Email notification

// Lead Creation
$sugar_config['twilio_auto_create_lead'] = true; // Auto-create lead from unknown SMS

// Fallback
$sugar_config['twilio_fallback_phone'] = '+15559876543'; // Fallback when no BDM

// Security
$sugar_config['twilio_skip_validation'] = false; // ONLY true for testing!
```

---

## Next Steps

- [ ] Configure Twilio credentials
- [ ] Run database setup script
- [ ] Configure webhooks in Twilio Console
- [ ] Set up schedulers for automation
- [ ] Test click-to-call functionality
- [ ] Test inbound calls and SMS
- [ ] Review metrics API
- [ ] Train users on new features

## Support

For issues and feature requests:
- Check logs: `suitecrm.log` with grep "Twilio"
- Check Twilio Console > Monitor > Logs
- Review webhook delivery status in Twilio Console

## Security Notes

⚠️ **NEVER** commit credentials to version control
⚠️ **ALWAYS** use HTTPS for webhooks
⚠️ **ALWAYS** keep webhook signature validation enabled in production
⚠️ **REGULARLY** rotate your Twilio Auth Token
