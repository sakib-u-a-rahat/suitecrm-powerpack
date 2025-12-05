# 📞 Twilio Integration v2.4.0 for SuiteCRM

> Complete voice and SMS integration with security, automation, and analytics

[![Version](https://img.shields.io/badge/version-2.4.0-blue.svg)](CHANGELOG.md)
[![SuiteCRM](https://img.shields.io/badge/SuiteCRM-7.x%20%7C%208.x-green.svg)](https://suitecrm.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-orange.svg)](LICENSE)

## 🎯 Overview

Transform your SuiteCRM into a powerful communication hub with complete Twilio integration. Make and receive calls, send and receive SMS, automatically log all activities, and gain insights with comprehensive analytics—all from within your CRM.

### ✨ Key Features

- 📞 **Click-to-Call** - Call any phone number with one click
- 💬 **SMS Messaging** - Send and receive SMS with templates
- 🔊 **Inbound Call Routing** - Automatically route to the right person
- 📼 **Call Recordings** - Auto-download and store recordings
- 📊 **Analytics Dashboard** - Comprehensive metrics API
- 🔔 **Smart Alerts** - Auto-create tasks for missed activities
- ⚡ **Auto-Follow-ups** - Never miss an unreplied SMS
- 🔒 **Enterprise Security** - Webhook signature validation
- 🤖 **Automation** - Scheduled tasks and cleanup
- 📈 **Response Time Tracking** - Monitor team performance

---

## 🚀 Quick Start

### 1. Install

The module is pre-installed in `custom-modules/TwilioIntegration/`.

Run database setup:
```bash
mysql -u user -p database < custom-modules/TwilioIntegration/install/twilio_setup.sql
```

### 2. Configure

Add to your `config.php`:

```php
$sugar_config['twilio_account_sid'] = 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
$sugar_config['twilio_auth_token'] = 'your_auth_token_here';
$sugar_config['twilio_phone_number'] = '+15551234567';
$sugar_config['site_url'] = 'https://yourdomain.com/suitecrm';
```

### 3. Setup Webhooks

In [Twilio Console](https://console.twilio.com/):

**Voice Webhook:**
```
https://yourdomain.com/suitecrm/index.php?module=TwilioIntegration&action=webhook
```

**SMS Webhook:**
```
https://yourdomain.com/suitecrm/index.php?module=TwilioIntegration&action=sms_webhook
```

### 4. Enable Scheduler

Go to **Admin > Schedulers** and create:

| Name | Job | Interval |
|------|-----|----------|
| Twilio SMS Follow-up | `function::TwilioSchedulerJob::checkSMSFollowups` | `0 * * * *` |
| Twilio Recording Cleanup | `function::TwilioSchedulerJob::cleanupRecordings` | `0 2 * * *` |

### 5. Test

- Open a Lead/Contact record
- Look for 📞 and 💬 buttons next to phone numbers
- Click 📞 to make a test call
- Click 💬 to send a test SMS

✅ **Done!** Your integration is live.

---

## 📚 Documentation

- **[Installation Guide](INSTALLATION.md)** - Detailed setup instructions
- **[Implementation Summary](IMPLEMENTATION_SUMMARY.md)** - Complete feature list
- **[Changelog](../../CHANGELOG.md)** - Version history

---

## 🎨 Features

### Outbound Calls

<img src="docs/screenshot-call.png" alt="Call UI" width="400"/>

- **Click-to-call buttons** on all phone fields
- **Beautiful call UI** with timer and controls
- **Real-time status** - see call progress live
- **Auto-logging** - calls saved to CRM automatically
- **Recording URLs** - access recordings from call records

### Inbound Calls

- **Smart routing** - connects to assigned BDM automatically
- **Personalized greetings** - "Hello John, thanks for calling..."
- **Voicemail fallback** - records message if no answer
- **Transcription** - voicemail text in CRM
- **Auto-tasks** - missed calls create follow-up tasks

### SMS Messaging

<img src="docs/screenshot-sms.png" alt="SMS UI" width="400"/>

- **Quick templates** - pre-written messages for common scenarios
- **Character counter** - see SMS segments and cost
- **Two-way messaging** - receive and respond
- **Auto-reply keywords** - STOP, START, HELP
- **Delivery tracking** - see if message was delivered

### Timeline & Logging

Every interaction is automatically logged:

- ✅ All calls → **Calls** module
- ✅ All SMS → **Notes** module
- ✅ Recordings → **Documents** module
- ✅ Call/SMS SIDs for tracking
- ✅ Linked to Leads/Contacts
- ✅ Assigned to correct user

### Analytics & Metrics

**Available APIs:**

| Metric Type | Endpoint |
|-------------|----------|
| Summary | `?module=TwilioIntegration&action=metrics&type=summary` |
| Calls | `?module=TwilioIntegration&action=metrics&type=calls` |
| SMS | `?module=TwilioIntegration&action=metrics&type=sms` |
| Performance | `?module=TwilioIntegration&action=metrics&type=performance` |
| Response Time | `?module=TwilioIntegration&action=metrics&type=response_time` |

**Example Response:**
```json
{
  "success": true,
  "period": "30days",
  "data": {
    "calls": {
      "totals": {
        "total": 156,
        "outbound": 89,
        "inbound": 67,
        "connected": 134,
        "missed": 22,
        "connect_rate": 85.9
      }
    },
    "response_time": {
      "summary": {
        "avg_call_response_minutes": 12.5,
        "avg_sms_response_minutes": 28.3,
        "response_time_buckets": {
          "0-15min": 45,
          "15-60min": 32,
          "1-4hr": 18,
          "4-24hr": 8,
          "24hr+": 3
        }
      }
    }
  }
}
```

### Automation

#### SMS Auto-Follow-ups ⚡ NEW

Never miss an unreplied SMS again!

- Checks every hour for unreplied messages
- Creates high-priority tasks after 24h (configurable)
- Sends email notification to assigned user
- Prevents duplicate follow-ups

#### Recording Management 📼 NEW

- Auto-downloads recordings when available
- Stores locally with retention policy
- Creates Document records in CRM
- Auto-cleanup after retention period (default: 365 days)

#### Maintenance Tasks

- Daily activity summaries
- Recording cleanup
- Audit log maintenance

### Security 🔒 NEW

#### Webhook Signature Validation

Every webhook validates the `X-Twilio-Signature` header to prevent spoofing:

```php
TwilioSecurity::validateOrDie('webhook');
```

- HMAC SHA1 verification
- Timing attack protection
- IP address validation
- Development mode bypass available

#### Audit Logging

Every action is logged to `twilio_audit_log`:

- User ID
- Action type
- Timestamp
- Full JSON data
- Searchable history

---

## 🔧 Configuration

### Required Settings

```php
$sugar_config['twilio_account_sid'] = 'ACxxxxx';          // Required
$sugar_config['twilio_auth_token'] = 'your_token';        // Required
$sugar_config['twilio_phone_number'] = '+15551234567';    // Required
$sugar_config['site_url'] = 'https://yourdomain.com';     // Required
```

### Optional Settings

```php
// Click-to-Call
$sugar_config['twilio_enable_click_to_call'] = true;

// Recordings
$sugar_config['twilio_enable_recordings'] = true;
$sugar_config['twilio_recording_path'] = 'upload/twilio_recordings';
$sugar_config['twilio_recording_retention_days'] = 365;

// SMS Follow-ups
$sugar_config['twilio_sms_followup_hours'] = 24;          // Hours before follow-up
$sugar_config['twilio_sms_followup_email'] = true;        // Send email notification

// Lead Auto-Creation
$sugar_config['twilio_auto_create_lead'] = true;          // Create lead from SMS

// Fallback
$sugar_config['twilio_fallback_phone'] = '+15559876543';  // Fallback number

// Security (DEV ONLY!)
$sugar_config['twilio_skip_validation'] = false;          // NEVER true in production!
```

---

## 📊 Use Cases

### Sales Team

- **Quick prospecting** - Call leads with one click
- **Follow-up reminders** - Never miss a callback
- **Activity tracking** - Every call/SMS logged automatically
- **Performance metrics** - Track connect rates and response times

### Support Team

- **Inbound routing** - Route calls to right agent
- **Voicemail transcription** - Read instead of listen
- **SMS support** - Handle support via text
- **Ticket creation** - Auto-create tasks from missed calls

### Management

- **Team performance** - See who's responding fastest
- **Call analytics** - Total calls, connect rates, duration
- **Response time SLA** - Monitor first response metrics
- **Activity summaries** - Daily/weekly reports

---

## 🏗️ Architecture

### Components

```
TwilioIntegration/
├── TwilioIntegration.php      # Main module class
├── TwilioClient.php            # Twilio API wrapper
├── TwilioSecurity.php          # Webhook validation
├── TwilioRecordingManager.php  # Recording handler
├── TwilioScheduler.php         # Scheduled tasks
├── TwilioSchedulerJob.php      # SuiteCRM scheduler integration
├── controller.php              # Action router
├── views/
│   ├── view.makecall.php       # Outbound call UI
│   ├── view.sendsms.php        # SMS UI
│   ├── view.webhook.php        # Voice webhook
│   ├── view.sms_webhook.php    # SMS webhook
│   ├── view.twiml.php          # TwiML generator
│   ├── view.metrics.php        # Analytics API
│   └── view.recording_webhook.php # Recording handler
├── cron/
│   └── twilio_tasks.php        # Cron script
└── install/
    └── twilio_setup.sql        # Database setup
```

### Data Flow

#### Outbound Call
```
User clicks 📞 → makecall view → Twilio API → TwiML response → Call connects → Status webhook → Call logged
```

#### Inbound Call
```
Twilio receives call → Voice webhook → Find lead → Route to BDM → Voicemail if no answer → Log + Create task
```

#### SMS
```
User sends SMS → sendsms view → Twilio API → SMS sent → Status callback → Note created
Reply received → SMS webhook → Find lead → Note created → Task created
```

#### Recordings
```
Call completes → Recording webhook → Download file → Create Document → Attach to Call → Cleanup after retention
```

---

## 🧪 Testing

### Manual Testing

1. **Test Click-to-Call**
   - Open Lead DetailView
   - Click 📞 on phone number
   - Verify call connects
   - Check Calls module for log

2. **Test Inbound**
   - Call your Twilio number
   - Verify routing works
   - Check for Call record
   - Check for Task if missed

3. **Test SMS**
   - Click 💬 on phone number
   - Send test message
   - Reply from phone
   - Check Notes module

4. **Test Metrics**
   - Visit `/index.php?module=TwilioIntegration&action=metrics`
   - Verify JSON response
   - Check all metric types

### Automated Testing

```bash
# Test webhook signature validation
curl -X POST "https://yourdomain.com/index.php?module=TwilioIntegration&action=webhook" \
  -d "CallSid=test" \
  -d "CallStatus=completed"
# Should return 403 (no signature)

# Test metrics API
curl "https://yourdomain.com/index.php?module=TwilioIntegration&action=metrics&type=summary"
# Should return JSON

# Test scheduler manually
php custom/modules/TwilioIntegration/cron/twilio_tasks.php
# Should run without errors
```

---

## 🐛 Troubleshooting

### Common Issues

**Problem**: Webhooks not working

**Solution**:
- Verify HTTPS is enabled
- Check webhook URLs in Twilio Console
- Check `suitecrm.log` for errors
- Test with signature validation disabled (temporarily)

**Problem**: Recordings not downloading

**Solution**:
- Check directory permissions: `chmod 755 upload/twilio_recordings`
- Verify recording webhook is configured
- Check logs for download errors

**Problem**: SMS follow-ups not creating

**Solution**:
- Verify scheduler is running
- Check `twilio_sms_followup_hours` setting
- Run manually: `php cron/twilio_tasks.php`

See [INSTALLATION.md](INSTALLATION.md#troubleshooting) for more.

---

## 📈 Roadmap

### Completed ✅
- [x] Click-to-call
- [x] Inbound routing
- [x] SMS messaging
- [x] Timeline logging
- [x] Metrics API
- [x] Webhook security
- [x] Recording management
- [x] SMS auto-follow-ups
- [x] Response time tracking

### Future (v2.5.0+)
- [ ] Dashboard UI (frontend)
- [ ] Bulk SMS
- [ ] AI agent integration
- [ ] Configuration UI
- [ ] Call queue management
- [ ] SMS campaigns
- [ ] Call analytics
- [ ] Calendar integration

---

## 🤝 Contributing

Contributions welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

---

## 📝 License

MIT License - See LICENSE file for details

---

## 💼 Support

- **Documentation**: See [INSTALLATION.md](INSTALLATION.md)
- **Issues**: Check `suitecrm.log` with grep "Twilio"
- **Twilio Logs**: [console.twilio.com/monitor/logs](https://console.twilio.com/monitor/logs)
- **Email**: support@boomershub.com

---

## 🎉 Acknowledgments

Built with ❤️ for the SuiteCRM community

- **Twilio** - Communication platform
- **SuiteCRM** - Open source CRM
- **Contributors** - Everyone who helped test and provide feedback

---

## 📌 Quick Links

- [Installation Guide](INSTALLATION.md)
- [Implementation Summary](IMPLEMENTATION_SUMMARY.md)
- [Changelog](../../CHANGELOG.md)
- [Twilio Documentation](https://www.twilio.com/docs)
- [SuiteCRM Documentation](https://docs.suitecrm.com/)

---

**Version**: 2.4.0
**Last Updated**: 2025-12-05
**Maintained By**: Boomers Hub Team

---

Made with 📞 and 💬 by [Boomers Hub](https://boomershub.com)
