# Twilio Integration - Safe Production Upgrade Guide

**Version**: 2.4.0
**Date**: 2025-12-05

This guide explains how to safely upgrade your Twilio Integration without losing data or configuration.

---

## Table of Contents
1. [Overview](#overview)
2. [What Gets Preserved](#what-gets-preserved)
3. [Pre-Upgrade Checklist](#pre-upgrade-checklist)
4. [Upgrade Methods](#upgrade-methods)
5. [Post-Upgrade Verification](#post-upgrade-verification)
6. [Rollback Procedure](#rollback-procedure)

---

## Overview

The Twilio Integration upgrade process is designed to:
- ✅ **Preserve all configuration** in `config.php`
- ✅ **Preserve all data** in database tables (calls, notes, audit logs)
- ✅ **Preserve recordings** stored on disk
- ✅ **Update only code files** (PHP files, views, etc.)
- ✅ **Add new database tables/columns** without affecting existing data
- ✅ **Maintain backward compatibility** with existing webhooks

---

## What Gets Preserved

### ✅ Configuration (Always Preserved)
Your configuration in `config.php` is **NEVER** modified during upgrade:
```php
$sugar_config['twilio_account_sid'] = 'ACxxxxx';
$sugar_config['twilio_auth_token'] = 'your_token';
$sugar_config['twilio_phone_number'] = '+15551234567';
$sugar_config['twilio_enable_recordings'] = true;
$sugar_config['twilio_recording_path'] = 'upload/twilio_recordings';
$sugar_config['twilio_sms_followup_hours'] = 24;
// ... all your settings are preserved
```

### ✅ Data (Always Preserved)
All existing data remains intact:
- **Calls Module**: All historical calls (with recordings, SIDs, durations)
- **Notes Module**: All SMS messages (inbound and outbound)
- **Tasks Module**: All auto-created follow-up tasks
- **Documents Module**: All recording file references
- **Audit Logs**: Complete audit history (`twilio_audit_log` table)

### ✅ Files (Always Preserved)
- **Recordings**: All files in `upload/twilio_recordings/`
- **Logs**: SuiteCRM logs remain intact

### ⚠️ What Gets Updated
Only code files are replaced:
- PHP classes (`TwilioIntegration.php`, `TwilioClient.php`, etc.)
- View files (`views/*.php`)
- JavaScript files (`click-to-call.js`)
- New files added (e.g., `TwilioSecurity.php`, `TwilioScheduler.php`)

---

## Pre-Upgrade Checklist

### 1. Backup Your System

#### Database Backup
```bash
# Full database backup
mysqldump -u USERNAME -p DATABASE_NAME > suitecrm_backup_$(date +%Y%m%d_%H%M%S).sql

# Or just Twilio-related tables (minimal)
mysqldump -u USERNAME -p DATABASE_NAME \
  calls notes tasks documents twilio_audit_log \
  > twilio_backup_$(date +%Y%m%d_%H%M%S).sql
```

#### Config Backup
```bash
# Backup config.php
cp config.php config.php.backup_$(date +%Y%m%d_%H%M%S)
```

#### Recordings Backup
```bash
# Backup all recordings
tar -czf recordings_backup_$(date +%Y%m%d_%H%M%S).tar.gz upload/twilio_recordings/
```

#### Module Files Backup
```bash
# Backup current module
tar -czf twilio_module_backup_$(date +%Y%m%d_%H%M%S).tar.gz \
  modules/TwilioIntegration/ \
  custom/modules/TwilioIntegration/
```

### 2. Document Current Configuration

Save your current settings to a file:
```bash
grep -E "^.sugar_config\['twilio_" config.php > twilio_config_backup.txt
```

### 3. Note Current Version

Check your current version:
```bash
grep "version" modules/TwilioIntegration/manifest.php
```

### 4. Check Disk Space

Ensure sufficient space for upgrade:
```bash
df -h
# Need at least 100MB free for upgrade process
```

### 5. Put Site in Maintenance Mode (Optional)

To prevent data inconsistency during upgrade:
```php
// In config.php, temporarily add:
$sugar_config['site_maintenance_mode'] = true;
```

---

## Upgrade Methods

### Method 1: Docker Container Update (Recommended for Docker Users)

This method updates the container while preserving all data.

#### Step 1: Pull New Image
```bash
docker pull mahir009/suitecrm-powerpack:v2.4.0
```

#### Step 2: Stop Current Container (Preserve Volumes)
```bash
# Get container name
docker ps | grep suitecrm

# Stop container (DO NOT use 'docker rm' - that removes volumes!)
docker stop <container_name>
```

#### Step 3: Start New Container with Same Volumes
```bash
docker run -d \
  --name suitecrm-v2.4.0 \
  -p 8080:8080 \
  -p 8443:8443 \
  -v suitecrm_data:/bitnami/suitecrm \
  -v suitecrm_recordings:/bitnami/suitecrm/upload/twilio_recordings \
  -e SUITECRM_DATABASE_HOST=mariadb \
  -e SUITECRM_DATABASE_PORT=3306 \
  -e SUITECRM_DATABASE_USER=bn_suitecrm \
  -e SUITECRM_DATABASE_NAME=bitnami_suitecrm \
  mahir009/suitecrm-powerpack:v2.4.0
```

**Important**: Using the same volume names (`suitecrm_data`, `suitecrm_recordings`) ensures all data is preserved.

#### Step 4: Run Database Migration
```bash
# Connect to container
docker exec -it suitecrm-v2.4.0 bash

# Run migration script
cd /bitnami/suitecrm
mysql -u bn_suitecrm -p bitnami_suitecrm < custom/modules/TwilioIntegration/install/upgrade_to_v2.4.0.sql
```

#### Step 5: Verify Configuration
```bash
# Check config.php still has your settings
docker exec suitecrm-v2.4.0 grep "twilio_account_sid" /bitnami/suitecrm/config.php
```

---

### Method 2: Manual File Replacement (For Non-Docker)

This method updates files directly on your server.

#### Step 1: Download New Version
```bash
cd /tmp
git clone https://github.com/yourusername/suitecrm.git
cd suitecrm
git checkout v2.4.0
```

#### Step 2: Copy New Files (Preserves Config)
```bash
# Navigate to SuiteCRM root
cd /var/www/html/suitecrm

# Copy new module files (this ONLY updates code, not config)
cp -r /tmp/suitecrm/custom-modules/TwilioIntegration/* modules/TwilioIntegration/
```

#### Step 3: Set Permissions
```bash
chown -R www-data:www-data modules/TwilioIntegration/
chmod -R 755 modules/TwilioIntegration/
```

#### Step 4: Run Database Migration
```bash
mysql -u root -p suitecrm_db < modules/TwilioIntegration/install/upgrade_to_v2.4.0.sql
```

#### Step 5: Clear Cache
```bash
# Clear SuiteCRM cache
rm -rf cache/*
php -r "require_once('modules/Administration/QuickRepairAndRebuild.php'); \$repair = new RepairAndClear(); \$repair->repairAndClearAll(['clearAll'], ['All'], false, false);"
```

---

### Method 3: SuiteCRM Module Loader (GUI Method)

This uses SuiteCRM's built-in upgrade system.

#### Step 1: Create Installable Package
```bash
cd /tmp/suitecrm/custom-modules/TwilioIntegration
zip -r TwilioIntegration-v2.4.0.zip *
```

#### Step 2: Upload via Module Loader
1. Login to SuiteCRM as Admin
2. Go to **Admin > Module Loader**
3. Click **Choose File** and select `TwilioIntegration-v2.4.0.zip`
4. Click **Upload**
5. Find the module in the list
6. Click **Install**
7. Review the pre-install information
8. Click **Commit**

#### Step 3: Run Quick Repair
1. Go to **Admin > Repair**
2. Click **Quick Repair and Rebuild**
3. Execute any SQL queries shown

---

## Database Migration Script

The upgrade includes a safe migration script that:
- ✅ Creates new tables only if they don't exist
- ✅ Adds new columns only if they don't exist
- ✅ Preserves all existing data

**Location**: `install/upgrade_to_v2.4.0.sql`

The script includes:
```sql
-- Creates twilio_audit_log if it doesn't exist
CREATE TABLE IF NOT EXISTS twilio_audit_log (...);

-- Adds new indexes if they don't exist (idempotent)
CREATE INDEX IF NOT EXISTS idx_phone_mobile ON leads(phone_mobile);
CREATE INDEX IF NOT EXISTS idx_phone_work ON leads(phone_work);

-- Creates views (replaces if exists, safe)
CREATE OR REPLACE VIEW twilio_call_metrics AS ...;
```

**Safe to run multiple times** - all operations are idempotent.

---

## Post-Upgrade Verification

### 1. Check Version
```bash
# Via PHP
php -r "require_once('modules/TwilioIntegration/manifest.php'); echo \$manifest['version'];"
# Should output: 2.4.0

# Via file
grep "version" modules/TwilioIntegration/manifest.php
```

### 2. Verify Configuration Preserved
```bash
# Check all your settings are still there
grep -E "^.sugar_config\['twilio_" config.php

# Compare with backup
diff twilio_config_backup.txt <(grep -E "^.sugar_config\['twilio_" config.php)
# Should show NO differences
```

### 3. Test Database Connectivity
```bash
mysql -u YOUR_USER -p -e "SELECT COUNT(*) FROM calls;" YOUR_DATABASE
mysql -u YOUR_USER -p -e "SELECT COUNT(*) FROM notes WHERE name LIKE '%SMS%';" YOUR_DATABASE
mysql -u YOUR_USER -p -e "SELECT COUNT(*) FROM twilio_audit_log;" YOUR_DATABASE
```

Expected: Same counts as before upgrade.

### 4. Test Recordings Still Accessible
```bash
ls -lh upload/twilio_recordings/ | wc -l
# Should match count before upgrade
```

### 5. Test Click-to-Call
1. Open a Lead record
2. Verify 📞 and 💬 buttons appear
3. Click call button
4. Verify call UI loads

### 6. Test Webhooks
```bash
# Test webhook endpoint is accessible
curl -I https://yourdomain.com/index.php?module=TwilioIntegration&action=webhook
# Should return 200 or 403 (403 is normal - signature validation)
```

### 7. Test Metrics API
```bash
curl "https://yourdomain.com/index.php?module=TwilioIntegration&action=metrics&type=summary"
# Should return JSON with data
```

### 8. Test New Features

#### Test Recording Webhook (NEW in v2.4.0)
```bash
curl -I https://yourdomain.com/index.php?module=TwilioIntegration&action=recording_webhook
# Should return 200 or 403
```

#### Test Scheduler Jobs (NEW in v2.4.0)
```bash
# Run manually
cd modules/TwilioIntegration/cron
php twilio_tasks.php
# Check logs for "TwilioScheduler: Completed"
```

#### Test Response Time Metrics (NEW in v2.4.0)
```bash
curl "https://yourdomain.com/index.php?module=TwilioIntegration&action=metrics&type=response_time"
# Should return JSON with response_time data
```

### 9. Check Logs for Errors
```bash
tail -100 suitecrm.log | grep -i "twilio"
# Look for any ERROR messages
```

### 10. Verify Scheduler Integration
1. Go to **Admin > Schedulers**
2. Verify these schedulers exist:
   - Twilio SMS Follow-up Check
   - Twilio Recording Cleanup
   - Twilio Daily Summary

---

## Rollback Procedure

If something goes wrong, you can rollback safely.

### Rollback Method 1: Restore from Backup (Safest)

#### Step 1: Stop Current System
```bash
# For Docker
docker stop <container_name>

# For Apache
sudo systemctl stop apache2
```

#### Step 2: Restore Database
```bash
# Restore full backup
mysql -u USERNAME -p DATABASE_NAME < suitecrm_backup_YYYYMMDD_HHMMSS.sql
```

#### Step 3: Restore Module Files
```bash
# Remove new version
rm -rf modules/TwilioIntegration/

# Restore backup
tar -xzf twilio_module_backup_YYYYMMDD_HHMMSS.tar.gz -C /
```

#### Step 4: Restore Config
```bash
cp config.php.backup_YYYYMMDD_HHMMSS config.php
```

#### Step 5: Restart System
```bash
# For Docker
docker start <container_name>

# For Apache
sudo systemctl start apache2
```

### Rollback Method 2: Docker Container Rollback

#### Step 1: Stop New Container
```bash
docker stop suitecrm-v2.4.0
```

#### Step 2: Start Old Container
```bash
docker start <old_container_name>
# Or if removed, recreate with old image:
docker run -d \
  --name suitecrm-old \
  -v suitecrm_data:/bitnami/suitecrm \
  mahir009/suitecrm-powerpack:v2.3.0
```

**Note**: Since volumes are preserved, all data remains intact.

---

## Migration Between Environments

### Development → Staging → Production

#### Step 1: Test in Development
```bash
# Pull latest
docker pull mahir009/suitecrm-powerpack:v2.4.0

# Start dev container
docker run -d --name suitecrm-dev \
  -v suitecrm_dev_data:/bitnami/suitecrm \
  mahir009/suitecrm-powerpack:v2.4.0
```

#### Step 2: Test in Staging (with production-like data)
```bash
# Copy production database to staging
mysqldump -u prod_user -p prod_db > prod_backup.sql
mysql -u staging_user -p staging_db < prod_backup.sql

# Update staging with new version
docker run -d --name suitecrm-staging \
  -v suitecrm_staging_data:/bitnami/suitecrm \
  mahir009/suitecrm-powerpack:v2.4.0
```

#### Step 3: Deploy to Production (after successful staging test)
```bash
# Schedule maintenance window
# Follow "Method 1: Docker Container Update" above
```

---

## Configuration Preservation Examples

### Example 1: Custom Recording Path
**Before upgrade**:
```php
$sugar_config['twilio_recording_path'] = '/mnt/nfs/recordings';
```

**After upgrade**:
```php
$sugar_config['twilio_recording_path'] = '/mnt/nfs/recordings'; // ✅ Preserved
```

### Example 2: Custom Follow-up Hours
**Before upgrade**:
```php
$sugar_config['twilio_sms_followup_hours'] = 48; // 2 days
```

**After upgrade**:
```php
$sugar_config['twilio_sms_followup_hours'] = 48; // ✅ Preserved
```

### Example 3: Development Mode
**Before upgrade**:
```php
$sugar_config['twilio_skip_validation'] = true; // Dev environment
```

**After upgrade**:
```php
$sugar_config['twilio_skip_validation'] = true; // ✅ Preserved
```

---

## Troubleshooting Upgrade Issues

### Issue: "Config settings missing after upgrade"

**Cause**: Config file was accidentally overwritten

**Solution**:
```bash
# Restore from backup
cp config.php.backup_YYYYMMDD_HHMMSS config.php

# Or manually re-add settings from backup
cat twilio_config_backup.txt >> config.php
```

### Issue: "Recordings disappeared"

**Cause**: Recording directory path changed or permissions issue

**Solution**:
```bash
# Check if files exist
ls -la upload/twilio_recordings/

# Check config points to correct path
grep twilio_recording_path config.php

# Fix permissions if needed
chown -R www-data:www-data upload/twilio_recordings/
chmod -R 755 upload/twilio_recordings/
```

### Issue: "Old data not showing in metrics"

**Cause**: Database migration didn't run

**Solution**:
```bash
# Re-run migration script
mysql -u root -p suitecrm_db < modules/TwilioIntegration/install/upgrade_to_v2.4.0.sql

# Verify tables exist
mysql -u root -p -e "SHOW TABLES LIKE 'twilio%';" suitecrm_db
```

### Issue: "Webhooks stopped working"

**Cause**: Signature validation enabled but URL changed

**Solution**:
```bash
# Temporarily disable validation for testing
# Add to config.php:
$sugar_config['twilio_skip_validation'] = true;

# Test webhook
curl -X POST https://yourdomain.com/index.php?module=TwilioIntegration&action=webhook

# Then re-enable validation and update Twilio Console URLs
$sugar_config['twilio_skip_validation'] = false;
```

---

## Best Practices

### 1. Always Backup Before Upgrade
- Database backup (full or Twilio tables)
- Config.php backup
- Recordings backup (if large, verify backup process works)

### 2. Test in Non-Production First
- Use staging environment
- Or use Docker dev container
- Verify all features work before production upgrade

### 3. Schedule Maintenance Window
- Inform users of upgrade time
- Enable maintenance mode
- Perform upgrade during low-traffic period

### 4. Document Your Configuration
- Keep list of all custom settings
- Note any customizations made
- Track integration points with other modules

### 5. Monitor After Upgrade
- Check logs for errors
- Verify webhooks still receiving calls/SMS
- Test key workflows (call, SMS, recordings)
- Monitor disk space (recordings)

### 6. Keep Backups for 30 Days
```bash
# Create dated backup directory
mkdir -p /backups/suitecrm/$(date +%Y%m)

# Move backups there
mv *.sql /backups/suitecrm/$(date +%Y%m)/
mv *.tar.gz /backups/suitecrm/$(date +%Y%m)/

# Auto-delete backups older than 30 days
find /backups/suitecrm -type f -mtime +30 -delete
```

---

## Quick Reference: Upgrade Checklist

```
☐ 1. Backup database
☐ 2. Backup config.php
☐ 3. Backup recordings
☐ 4. Document current version
☐ 5. Document current configuration
☐ 6. Enable maintenance mode (optional)
☐ 7. Pull new Docker image / Download new files
☐ 8. Stop old container / Copy new files
☐ 9. Start new container / Set permissions
☐ 10. Run database migration script
☐ 11. Verify configuration preserved
☐ 12. Clear cache
☐ 13. Test click-to-call
☐ 14. Test webhooks
☐ 15. Test metrics API
☐ 16. Test new features
☐ 17. Check logs for errors
☐ 18. Disable maintenance mode
☐ 19. Monitor for 24 hours
☐ 20. Archive backups
```

---

## Support

If you encounter issues during upgrade:

1. **Check logs**: `tail -f suitecrm.log | grep -i twilio`
2. **Verify backups**: Ensure rollback is possible
3. **Review this guide**: Most issues covered in Troubleshooting section
4. **Rollback if needed**: Use Rollback Procedure above

---

## Version History

- **v2.4.0** (2025-12-05): Added security, automation, and analytics features
- **v2.3.0** (2025-12-01): Added click-to-call and SMS features
- **v2.0.0** (2025-11-15): Initial release

---

**Upgrade Guide Version**: 1.0
**Last Updated**: 2025-12-05
**Tested On**: SuiteCRM 7.x, 8.x

---

*Safe upgrading! Your data and configuration are always preserved.* 🚀
