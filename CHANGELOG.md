# Changelog

All notable changes to SuiteCRM PowerPack will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.4.0] - 2025-12-05

### Added - Security, Automation & Enhanced Features
- **Security Enhancements**
  - Webhook signature validation (X-Twilio-Signature) to prevent spoofing
  - TwilioSecurity class with validateOrDie() method
  - IP address validation against Twilio ranges
  - Development mode bypass option (twilio_skip_validation)
  - Protection against timing attacks with hash_equals()

- **Recording Management**
  - Automatic recording download after call completion
  - TwilioRecordingManager class for file handling
  - Recording stored locally with configurable retention
  - Document creation in CRM with metadata
  - Automatic attachment to Call records
  - Recording webhook handler (recording_webhook view)
  - MP3 format storage with proper naming convention
  - .htaccess protection for recording directory

- **SMS Auto-Follow-up System**
  - TwilioScheduler class for automated tasks
  - Detects unreplied SMS after configurable threshold (default: 24h)
  - Creates high-priority follow-up tasks automatically
  - Email notifications to assigned users
  - SuiteCRM Scheduler integration (TwilioSchedulerJob)
  - Cron job script (twilio_tasks.php)
  - Prevents duplicate follow-up tasks

- **Response Time Metrics**
  - First response time calculation for inbound calls
  - First response time calculation for inbound SMS
  - Response time distribution buckets (0-15min, 15-60min, 1-4hr, 4-24hr, 24hr+)
  - Average response time per user
  - Response time trends over time
  - Available via metrics API: `type=response_time`

- **Maintenance & Cleanup**
  - Automated recording cleanup after retention period
  - Daily/weekly activity summary generation
  - Configurable retention policies
  - Scheduled maintenance tasks

### Changed
- view.webhook.php now validates Twilio signatures
- view.sms_webhook.php now validates Twilio signatures
- view.makecall.php includes recording callback URL
- view.metrics.php expanded with response_time endpoint
- controller.php registered new actions (recording_webhook, dashboard, bulksms)

### Fixed
- Webhook security vulnerability (no signature validation)
- Call recordings expiring on Twilio CDN
- SMS follow-ups not being tracked
- Missing first response time metrics

## [2.3.0] - 2025-12-03

### Added - Complete Twilio Integration Rewrite
- **Outbound Calls**
  - Complete makecall view with dark theme UI
  - Real-time call status updates with polling
  - Call timer showing duration
  - End call functionality
  - Lead/Contact auto-detection from phone number
  - Call logging to SuiteCRM Calls module
  - TwiML generation for call routing

- **SMS Messaging**
  - Complete sendsms view with dark theme UI
  - Character counter (1600 max)
  - Quick template support
  - Message delivery status tracking
  - Lead/Contact auto-detection
  - SMS logging to SuiteCRM Notes

- **Inbound Call Handling**
  - Voice webhook for incoming calls
  - Caller ID lookup - matches to Lead/Contact
  - Personalized greeting with caller's name
  - BDM routing - connects to assigned user's phone
  - Voicemail fallback when BDM unavailable
  - Voicemail transcription support
  - Missed call logging with auto-task creation

- **Inbound SMS Handling**
  - SMS webhook for incoming messages
  - Auto-reply for STOP/START/HELP keywords
  - SMS/MMS support with media attachments
  - Follow-up task creation
  - Optional auto-create lead for unknown numbers

- **Dashboard Metrics API**
  - `/index.php?module=TwilioIntegration&action=metrics`
  - Call metrics: total, inbound, outbound, connected, missed
  - SMS metrics: total sent/received
  - Performance metrics by user
  - Connect rate calculations
  - Daily breakdown data for charts

- **Configuration**
  - Enhanced config view with dark theme
  - Webhook URL generator with copy buttons
  - Connection test button
  - Fallback phone number setting
  - Auto-create lead toggle
  - Environment variable support

- **Audit Logging**
  - New `twilio_audit_log` table
  - Logs all Twilio events with JSON data
  - User tracking for actions
  - Timestamp tracking

### Fixed
- Controller action mapping (no more "no action by that name" error)
- Phone number formatting to E.164
- Fetch credentials for same-origin requests
- TwiML dial action completion handling

## [1.1.0] - 2025-11-20

### Fixed
- **CRITICAL**: Removed infinite database connection check loop that caused containers to hang
- **CRITICAL**: Added Composer and composer dependencies installation
- **CRITICAL**: Fixed module installation timing - only installs after SuiteCRM is configured
- **CRITICAL**: Added `sql_require_primary_key=0` patch for managed MySQL databases (DigitalOcean, AWS RDS, etc.)

### Added
- MySQL client (default-mysql-client) for optional database connectivity testing
- CA certificates for SSL support
- Composer from official Composer image
- Automated MysqliManager.php patching for managed database compatibility
- Comprehensive environment variables documentation (ENVIRONMENT_VARIABLES.md)
- Implementation summary document (FIXES_v1.1.0.md)

### Changed
- Container now starts immediately without waiting for database
- Module installation checks for config.php existence first
- All database connections handled by PHP PDO/MySQLi with proper SSL support

### Improved
- Production-ready for managed MySQL databases:
  - DigitalOcean Managed MySQL
  - AWS RDS
  - Google Cloud SQL
  - Azure Database for MySQL
  - Any MySQL 8.0+ with sql_require_primary_key=ON
- Better error handling during startup
- Clearer documentation on port configuration (port 80, not 8080)

## [1.0.1] - 2025-11-17

### Added
- SMS (Click-to-text) functionality in Twilio Integration
  - Click-to-text button on all phone fields
  - Interactive SMS compose dialog
  - Character counter (1600 characters max)
  - Automatic SMS logging to Notes
  - SMS status callbacks
  - Message history retrieval
  - Ctrl+Enter shortcut to send

### Enhanced
- Updated JavaScript UI with separate Call and SMS buttons
- Improved notification system with info/success/error states
- Better visual feedback for SMS operations
- Enhanced TwilioClient with SMS API methods

## [1.0.0] - 2025-11-16

### Added
- Initial release of SuiteCRM PowerPack
- Twilio Integration module
  - Click-to-call functionality
  - Automatic call logging
  - Call recordings with storage
  - UI-based configuration panel
- Lead Journey Timeline module
  - Unified timeline view of all touchpoints
  - Support for calls, emails, meetings, site visits, LinkedIn clicks
  - Filterable timeline by touchpoint type
  - Engagement statistics dashboard
  - JavaScript tracking for site visits
- Funnel Dashboard module
  - Visual sales funnel with all stages
  - Category/lead source segmentation
  - Conversion rate calculations
  - Funnel velocity metrics
  - Top performing categories analysis
  - Date range filtering
- Docker support with MySQL 8 compatibility
- External database support (local, remote, cloud)
- Comprehensive documentation
- Database connection testing tool
- Build and deployment scripts

### Features
- Out-of-the-box ready modules
- UI-configurable settings
- Production-ready Docker image
- Support for AWS RDS, Google Cloud SQL, Azure Database
- Automated module installation
- Built on SuiteCRM 7.14.2
- PHP 8.1 support
- Apache web server

### Documentation
- README with quick start guide
- External database configuration guide
- Architecture overview
- Deployment checklist
- Quick start guide
- Troubleshooting documentation

[1.1.0]: https://github.com/mahir/suitecrm-powerpack/releases/tag/v1.1.0
[1.0.1]: https://github.com/mahir/suitecrm-powerpack/releases/tag/v1.0.1
[1.0.0]: https://github.com/mahir/suitecrm-powerpack/releases/tag/v1.0.0
