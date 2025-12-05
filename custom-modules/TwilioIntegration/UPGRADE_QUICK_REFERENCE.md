# Twilio Integration v2.4.0 - Quick Upgrade Reference

**Quick reference for production upgrades without data loss**

---

## 🐳 Docker Upgrade (Automated)

### One-Command Upgrade
```bash
./upgrade-docker.sh suitecrm
```

That's it! The script will:
- ✅ Backup config, database, and volumes
- ✅ Pull new image
- ✅ Create new container with same volumes
- ✅ Run database migration
- ✅ Verify data preserved
- ✅ Keep old container for rollback

---

## 🐳 Docker Upgrade (Manual)

### Quick Steps
```bash
# 1. Backup config
docker exec suitecrm cat /bitnami/suitecrm/config.php > config.backup

# 2. Backup database (Twilio tables only)
docker exec suitecrm mysqldump -u bn_suitecrm -p \
  bitnami_suitecrm calls notes tasks documents twilio_audit_log \
  > twilio_backup.sql

# 3. Pull new image
docker pull mahir009/suitecrm-powerpack:v2.4.0

# 4. Stop and rename current container
docker stop suitecrm
docker rename suitecrm suitecrm_old

# 5. Start new container (SAME VOLUMES!)
docker run -d --name suitecrm \
  -v suitecrm_data:/bitnami/suitecrm \
  -v suitecrm_recordings:/bitnami/suitecrm/upload/twilio_recordings \
  -e SUITECRM_DATABASE_HOST=mariadb \
  -e SUITECRM_DATABASE_USER=bn_suitecrm \
  -e SUITECRM_DATABASE_NAME=bitnami_suitecrm \
  -p 8080:8080 -p 8443:8443 \
  mahir009/suitecrm-powerpack:v2.4.0

# 6. Run migration
docker exec suitecrm mysql -u bn_suitecrm -p bitnami_suitecrm \
  < /bitnami/suitecrm/modules/TwilioIntegration/install/upgrade_to_v2.4.0.sql

# 7. Verify config preserved
docker exec suitecrm grep twilio_account_sid /bitnami/suitecrm/config.php
```

**Rollback if needed:**
```bash
docker stop suitecrm
docker rm suitecrm
docker rename suitecrm_old suitecrm
docker start suitecrm
```

---

## 💻 Manual File Upgrade (Non-Docker)

### Quick Steps
```bash
# 1. Backup
cp config.php config.php.backup
mysqldump -u root -p suitecrm_db calls notes tasks documents twilio_audit_log > backup.sql

# 2. Download new version
git pull origin main
# Or: download zip from GitHub releases

# 3. Copy new files
cp -r custom-modules/TwilioIntegration/* modules/TwilioIntegration/

# 4. Set permissions
chown -R www-data:www-data modules/TwilioIntegration/
chmod -R 755 modules/TwilioIntegration/

# 5. Run migration
mysql -u root -p suitecrm_db < modules/TwilioIntegration/install/upgrade_to_v2.4.0.sql

# 6. Clear cache
rm -rf cache/*
php -r "require_once('modules/Administration/QuickRepairAndRebuild.php'); \$r = new RepairAndClear(); \$r->repairAndClearAll(['clearAll'],['All'],false,false);"

# 7. Verify config
diff config.php.backup config.php
# Should show NO differences in twilio_ settings
```

---

## ✅ Post-Upgrade Verification

### Quick Tests
```bash
# 1. Check version
docker exec suitecrm grep version /bitnami/suitecrm/modules/TwilioIntegration/manifest.php
# Should show: 2.4.0

# 2. Verify data count matches
docker exec suitecrm mysql -u bn_suitecrm -p -e \
  "SELECT COUNT(*) FROM calls; SELECT COUNT(*) FROM notes WHERE name LIKE '%SMS%';" \
  bitnami_suitecrm

# 3. Test metrics API
curl "https://yourdomain.com/index.php?module=TwilioIntegration&action=metrics&type=summary"

# 4. Check new features exist
docker exec suitecrm ls -la /bitnami/suitecrm/modules/TwilioIntegration/ | grep -E "Security|Scheduler|Recording"
```

---

## 🔄 What's Preserved

### ✅ Always Safe
- **Config** - All `$sugar_config['twilio_*']` settings
- **Database** - All calls, notes, tasks, documents, audit logs
- **Recordings** - All MP3 files in `upload/twilio_recordings/`
- **Webhooks** - Same URLs, continue working
- **Schedulers** - Keep running (update job names if needed)

### ⚠️ What Changes
- **Code Files** - PHP files updated to v2.4.0
- **New Files Added** - TwilioSecurity.php, TwilioScheduler.php, etc.
- **Database Schema** - New `twilio_audit_log` table (data preserved)
- **Views** - New `twilio_call_metrics` and `twilio_sms_metrics` views

---

## 🚨 Common Issues

### Issue: Config Lost
**Fix:**
```bash
# Restore from backup
docker cp config.backup suitecrm:/bitnami/suitecrm/config.php
# Or: cp config.php.backup config.php
```

### Issue: Recordings Missing
**Fix:**
```bash
# Check volume is mounted
docker inspect suitecrm | grep -A 5 Mounts

# Verify path in config
docker exec suitecrm grep twilio_recording_path /bitnami/suitecrm/config.php
```

### Issue: Webhooks Not Working
**Fix:**
```bash
# Temporarily disable validation
docker exec suitecrm bash -c "echo \"\\\$sugar_config['twilio_skip_validation'] = true;\" >> /bitnami/suitecrm/config.php"

# Test webhook
curl -X POST https://yourdomain.com/index.php?module=TwilioIntegration&action=webhook

# Re-enable validation after testing
```

---

## 📊 Key Differences: v2.3.0 → v2.4.0

### New Features
- ✅ Webhook security (signature validation)
- ✅ Auto-download call recordings
- ✅ SMS auto-follow-up scheduler
- ✅ Response time metrics
- ✅ Recording cleanup automation

### New Files
- `TwilioSecurity.php`
- `TwilioRecordingManager.php`
- `TwilioScheduler.php`
- `TwilioSchedulerJob.php`
- `views/view.recording_webhook.php`
- `cron/twilio_tasks.php`

### New Database Objects
- Table: `twilio_audit_log`
- View: `twilio_call_metrics`
- View: `twilio_sms_metrics`
- Indexes: Phone number indexes on leads/contacts

### New Config Options (Optional)
```php
$sugar_config['twilio_recording_retention_days'] = 365;
$sugar_config['twilio_sms_followup_hours'] = 24;
$sugar_config['twilio_sms_followup_email'] = true;
$sugar_config['twilio_skip_validation'] = false; // Dev only!
```

---

## 🕐 Typical Upgrade Time

- **Docker (automated)**: 5-10 minutes
- **Docker (manual)**: 15 minutes
- **Manual files**: 20-30 minutes
- **Module Loader**: 10-15 minutes

**Downtime**: 2-5 minutes (during container restart/file copy)

---

## 📞 Emergency Rollback

### Docker (Fast)
```bash
docker stop suitecrm && docker rm suitecrm
docker rename suitecrm_old suitecrm
docker start suitecrm
```

### Manual (Fast)
```bash
rm -rf modules/TwilioIntegration/
tar -xzf twilio_module_backup.tar.gz -C /
cp config.php.backup config.php
mysql -u root -p suitecrm_db < backup.sql
```

---

## ✅ Success Indicators

After upgrade, you should see:

1. Version shows `2.4.0` in manifest.php
2. All config settings preserved in config.php
3. Data counts match pre-upgrade counts
4. Metrics API returns data
5. Click-to-call buttons still appear
6. Webhooks return 403 (signature validation working)
7. New files present: TwilioSecurity.php, etc.
8. No errors in logs

---

## 📚 Full Documentation

- **Complete Guide**: [UPGRADE_GUIDE.md](UPGRADE_GUIDE.md)
- **Installation**: [INSTALLATION.md](INSTALLATION.md)
- **Features**: [README.md](README.md)
- **Testing**: [TEST_REPORT.md](TEST_REPORT.md)

---

## 🎯 TL;DR - Absolute Quickest

**Docker users:**
```bash
./upgrade-docker.sh suitecrm
```

**Manual users:**
```bash
# Backup
cp config.php config.backup && mysqldump DB > backup.sql

# Upgrade
git pull && cp -r custom-modules/TwilioIntegration/* modules/TwilioIntegration/

# Migrate
mysql DB < modules/TwilioIntegration/install/upgrade_to_v2.4.0.sql

# Verify
grep twilio_account_sid config.php && curl "https://domain.com/index.php?module=TwilioIntegration&action=metrics"
```

---

**Version**: 1.0
**Updated**: 2025-12-05
**Safe for**: All production environments ✅
