# 🎯 SuiteCRM Extended - Deployment Checklist

## Pre-Deployment

### Prerequisites Check
- [ ] Docker installed (version 20.10+)
- [ ] Docker Compose installed (version 1.29+)
- [ ] Twilio account created
- [ ] Twilio phone number purchased
- [ ] Twilio credentials available

### Configuration
- [ ] Copy `.env.example` to `.env`
- [ ] Set `TWILIO_ACCOUNT_SID` in `.env`
- [ ] Set `TWILIO_AUTH_TOKEN` in `.env`
- [ ] Set `TWILIO_PHONE_NUMBER` in `.env`
- [ ] Update `SUITECRM_SITE_URL` if needed
- [ ] Set strong database passwords for production

## Build

### Local Development
```bash
# 1. Make scripts executable
chmod +x build.sh start.sh stop.sh

# 2. Build image
./build.sh

# 3. Start containers
./start.sh

# 4. Verify containers are running
docker-compose ps

# 5. Check logs
docker-compose logs -f
```

### Production Build
```bash
# 1. Update .env with production values
nano .env

# 2. Build with no cache
docker-compose build --no-cache

# 3. Start in detached mode
docker-compose up -d

# 4. Verify all services
docker-compose ps
```

## Post-Deployment

### Initial Setup
- [ ] Access SuiteCRM at http://localhost:8080 (or your domain)
- [ ] Complete SuiteCRM installation wizard
- [ ] Create admin user
- [ ] Complete database setup

### Module Configuration

#### Twilio Integration
- [ ] Navigate to Admin > Twilio Integration
- [ ] Click "Configuration"
- [ ] Enter Twilio Account SID
- [ ] Enter Twilio Auth Token
- [ ] Enter Twilio Phone Number
- [ ] Enable Click-to-Call
- [ ] Enable Auto Logging
- [ ] Enable Recordings
- [ ] Save configuration
- [ ] Test by clicking a phone number

#### Lead Journey Timeline
- [ ] Create a test Lead
- [ ] Add sample interactions (call, email, meeting)
- [ ] Navigate to Lead Journey > Timeline
- [ ] Verify all touchpoints appear
- [ ] Test filters (All, Calls, Emails, etc.)
- [ ] Verify statistics are correct

#### Funnel Dashboard
- [ ] Navigate to Funnel Dashboard module
- [ ] Verify funnel stages load
- [ ] Test category filter
- [ ] Test date range filter
- [ ] Verify conversion rates calculate
- [ ] Check top categories section
- [ ] Verify velocity metrics

### Testing

#### Functional Tests
- [ ] Click-to-call works from Contact page
- [ ] Click-to-call works from Lead page
- [ ] Calls are logged automatically
- [ ] Call duration is recorded
- [ ] Timeline aggregates all touchpoints
- [ ] Timeline filters work correctly
- [ ] Funnel shows all stages
- [ ] Conversion rates calculate correctly
- [ ] Date filtering works

#### Performance Tests
- [ ] Dashboard loads in <3 seconds
- [ ] Timeline loads in <2 seconds
- [ ] Click-to-call responds instantly
- [ ] No console errors in browser
- [ ] Database queries are optimized

#### Security Tests
- [ ] Twilio credentials not exposed in UI
- [ ] SQL injection protection verified
- [ ] XSS protection verified
- [ ] CSRF tokens present
- [ ] Admin-only access to configuration

## Verification

### Service Health
```bash
# Check all containers running
docker-compose ps

# Expected output:
# suitecrm-extended     Up      0.0.0.0:8080->80/tcp
# suitecrm-db          Up      3306/tcp
# suitecrm-phpmyadmin  Up      0.0.0.0:8081->80/tcp
```

### Database Check
```bash
# Access database
docker-compose exec db mysql -usuitecrm -psuitecrm suitecrm

# Verify tables exist
SHOW TABLES LIKE 'twilio%';
SHOW TABLES LIKE 'lead_journey';
SHOW TABLES LIKE 'funnel%';
```

### Module Check
```bash
# Check module files
docker-compose exec suitecrm ls -la /var/www/html/modules/TwilioIntegration
docker-compose exec suitecrm ls -la /var/www/html/modules/LeadJourney
docker-compose exec suitecrm ls -la /var/www/html/modules/FunnelDashboard
```

## Backup

### Initial Backup
```bash
# Create backup directory
mkdir -p backups

# Backup database
docker-compose exec db mysqldump -usuitecrm -psuitecrm suitecrm > backups/initial_backup.sql

# Backup files
docker-compose exec suitecrm tar -czf /tmp/files.tar.gz /var/www/html
docker cp suitecrm-extended:/tmp/files.tar.gz backups/initial_files.tar.gz
```

### Schedule Automated Backups (Production)
```bash
# Add to crontab
0 2 * * * cd /path/to/suitecrm && docker-compose exec db mysqldump -usuitecrm -psuitecrm suitecrm > backups/backup_$(date +\%Y\%m\%d).sql
```

## Monitoring

### Set Up Monitoring
- [ ] Configure log rotation
- [ ] Set up health checks
- [ ] Monitor disk space
- [ ] Monitor database size
- [ ] Monitor container resource usage

### Health Check Commands
```bash
# Container status
docker-compose ps

# Container resource usage
docker stats

# Disk usage
df -h

# Database size
docker-compose exec db mysql -usuitecrm -psuitecrm -e "
SELECT 
    table_schema AS 'Database',
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
FROM information_schema.TABLES 
GROUP BY table_schema;
"
```

## Production Hardening

### Security
- [ ] Change default database passwords
- [ ] Enable HTTPS/SSL
- [ ] Configure firewall rules
- [ ] Restrict database access
- [ ] Set up fail2ban
- [ ] Enable audit logging
- [ ] Regular security updates

### Performance
- [ ] Enable PHP OpCache
- [ ] Configure Redis caching
- [ ] Optimize database indexes
- [ ] Enable gzip compression
- [ ] Set up CDN for static assets
- [ ] Configure load balancer (if needed)

### Reliability
- [ ] Set up database replication
- [ ] Configure automated backups
- [ ] Set up monitoring alerts
- [ ] Document recovery procedures
- [ ] Test disaster recovery
- [ ] Set up log aggregation

## Maintenance

### Regular Tasks
- [ ] Weekly: Review logs for errors
- [ ] Weekly: Check disk space
- [ ] Monthly: Update SuiteCRM
- [ ] Monthly: Review security patches
- [ ] Quarterly: Test backup restoration
- [ ] Quarterly: Performance review

### Update Procedure
```bash
# 1. Backup current state
./backup.sh

# 2. Pull updates
git pull origin main

# 3. Rebuild
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# 4. Verify modules
# Check each module in SuiteCRM admin

# 5. Test functionality
# Run through testing checklist
```

## Troubleshooting

### Common Issues

#### Modules Not Visible
```bash
docker-compose exec suitecrm php -r "
require_once('include/entryPoint.php');
require_once('modules/Administration/QuickRepairAndRebuild.php');
\$repair = new RepairAndClear();
\$repair->repairAndClearAll(['clearAll'], ['All'], false, false);
"
```

#### Permission Errors
```bash
docker-compose exec suitecrm chown -R www-data:www-data /var/www/html
docker-compose exec suitecrm chmod -R 755 /var/www/html
```

#### Database Connection Issues
```bash
# Check database container
docker-compose logs db

# Verify credentials
cat .env | grep DATABASE

# Test connection
docker-compose exec suitecrm php -r "
\$conn = new mysqli('db', 'suitecrm', 'suitecrm', 'suitecrm');
echo \$conn->connect_error ? 'Failed' : 'Success';
"
```

## Documentation

### Keep Updated
- [ ] Document custom configurations
- [ ] Record Twilio webhook URLs
- [ ] Document any custom modifications
- [ ] Update this checklist as needed
- [ ] Maintain change log

### Share with Team
- [ ] Share access credentials securely
- [ ] Document access procedures
- [ ] Create user guides
- [ ] Schedule training sessions
- [ ] Set up support process

## Sign-Off

### Development
- [ ] All features implemented
- [ ] All tests passed
- [ ] Documentation complete
- [ ] Code reviewed
- [ ] Signed off by: ________________ Date: __________

### Staging
- [ ] Deployed to staging
- [ ] Smoke tests passed
- [ ] User acceptance testing complete
- [ ] Performance validated
- [ ] Signed off by: ________________ Date: __________

### Production
- [ ] Deployed to production
- [ ] All services healthy
- [ ] Monitoring configured
- [ ] Backups verified
- [ ] Team trained
- [ ] Signed off by: ________________ Date: __________

---

**Deployment Date**: _______________  
**Deployed By**: _______________  
**Environment**: ☐ Development ☐ Staging ☐ Production  
**Version**: 1.0.0
