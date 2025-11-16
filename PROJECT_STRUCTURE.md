# SuiteCRM Extended - Project Structure

## 📁 Directory Structure

```
suitecrm/
├── Dockerfile                          # Main Docker image configuration
├── docker-compose.yml                  # Multi-container orchestration
├── docker-entrypoint.sh               # Container startup script
├── build.sh                           # Build script
├── start.sh                           # Start containers script
├── stop.sh                            # Stop containers script
├── .env.example                       # Environment variables template
├── .gitignore                         # Git ignore rules
├── README.md                          # Comprehensive documentation
├── QUICKSTART.md                      # Quick start guide
│
├── config/
│   └── config_override.php.template   # SuiteCRM configuration template
│
├── install-scripts/
│   └── install-modules.sh             # Module installation script
│
└── custom-modules/
    ├── TwilioIntegration/             # Twilio calling module
    │   ├── manifest.php               # Module manifest
    │   ├── TwilioIntegration.php      # Main business logic
    │   ├── TwilioClient.php           # Twilio API client
    │   ├── vardefs.php                # Field definitions
    │   ├── click-to-call.js           # Frontend click-to-call
    │   └── views/
    │       └── view.config.php        # Configuration UI
    │
    ├── LeadJourney/                   # Journey timeline module
    │   ├── manifest.php               # Module manifest
    │   ├── LeadJourney.php            # Timeline aggregation logic
    │   ├── vardefs.php                # Field definitions
    │   ├── tracking.js                # Site visit tracking
    │   └── views/
    │       └── view.timeline.php      # Timeline visualization
    │
    └── FunnelDashboard/               # Funnel analytics module
        ├── manifest.php               # Module manifest
        ├── FunnelDashboard.php        # Funnel calculations
        ├── vardefs.php                # Field definitions
        └── views/
            └── view.dashboard.php     # Dashboard UI
```

## 🎯 Module Features

### 1. Twilio Integration Module
**Files**: `custom-modules/TwilioIntegration/`

**Features**:
- ✅ Click-to-call from any phone field
- ✅ Automatic call logging to CRM
- ✅ Call recording storage
- ✅ UI-based configuration
- ✅ Real-time call status tracking
- ✅ Webhook integration for call events

**Main Components**:
- `TwilioIntegration.php` - Core module logic, call logging
- `TwilioClient.php` - Twilio API wrapper for making calls
- `view.config.php` - Admin configuration interface
- `click-to-call.js` - Frontend click-to-call functionality

### 2. Lead Journey Timeline Module
**Files**: `custom-modules/LeadJourney/`

**Features**:
- ✅ Unified timeline of all touchpoints
- ✅ Aggregates calls, emails, meetings
- ✅ Site visit tracking
- ✅ LinkedIn click tracking
- ✅ Campaign interaction tracking
- ✅ Filterable by touchpoint type
- ✅ Engagement statistics

**Main Components**:
- `LeadJourney.php` - Timeline aggregation and data retrieval
- `view.timeline.php` - Beautiful timeline visualization
- `tracking.js` - JavaScript for website visit tracking

### 3. Funnel Dashboard Module
**Files**: `custom-modules/FunnelDashboard/`

**Features**:
- ✅ Visual sales funnel
- ✅ Category/lead source segmentation
- ✅ Stage-by-stage tracking
- ✅ Conversion rate calculations
- ✅ Funnel velocity metrics
- ✅ Top performing categories
- ✅ Date range filtering
- ✅ Revenue tracking

**Main Components**:
- `FunnelDashboard.php` - Funnel calculations and analytics
- `view.dashboard.php` - Interactive dashboard UI

## 🚀 Deployment

### Local Development
```bash
./build.sh
./start.sh
```

### Production
1. Update `.env` with production credentials
2. Configure SSL certificates
3. Update `SUITECRM_SITE_URL` to production URL
4. Build and deploy

## 🔧 Configuration

### Environment Variables (.env)
- `TWILIO_ACCOUNT_SID` - Your Twilio Account SID
- `TWILIO_AUTH_TOKEN` - Your Twilio Auth Token
- `TWILIO_PHONE_NUMBER` - Your Twilio phone number
- `SUITECRM_DATABASE_*` - Database connection details
- `SUITECRM_SITE_URL` - Your SuiteCRM URL

### Module Configuration (UI)
All modules are configurable through the SuiteCRM admin interface:
- Admin > Twilio Integration > Configuration
- Lead Journey > Timeline (per record)
- Funnel Dashboard > Dashboard (with filters)

## 📊 Database Tables

### twilio_integration
Stores Twilio configuration and settings

### lead_journey
Stores all touchpoint data for timeline tracking

### funnel_dashboard
Stores funnel configuration (optional, mainly uses existing tables)

## 🔐 Security Features

- Environment-based credential management
- Twilio webhook signature verification (can be added)
- SQL injection protection (parameterized queries)
- XSS protection in UI rendering
- CSRF token support (SuiteCRM built-in)

## 📈 Scalability

- Docker-based architecture for easy scaling
- Separate database container
- Stateless application design
- Cacheable static assets
- Optimized database queries with indexes

## 🧪 Testing

### Manual Testing Checklist
- [ ] Twilio configuration saves correctly
- [ ] Click-to-call initiates calls
- [ ] Calls are logged automatically
- [ ] Timeline shows all touchpoints
- [ ] Timeline filters work
- [ ] Funnel dashboard loads data
- [ ] Category filtering works
- [ ] Date range filtering works

### Test Data
Create test leads with various touchpoints to verify timeline and funnel functionality.

## 📦 Backup Strategy

### Files
```bash
docker-compose exec suitecrm tar -czf /tmp/backup.tar.gz /var/www/html
docker cp suitecrm-extended:/tmp/backup.tar.gz ./backup.tar.gz
```

### Database
```bash
docker-compose exec db mysqldump -usuitecrm -psuitecrm suitecrm > backup.sql
```

## 🔄 Update Strategy

1. Pull latest changes
2. Rebuild image: `docker-compose build --no-cache`
3. Stop containers: `docker-compose down`
4. Start with new image: `docker-compose up -d`
5. Clear cache if needed

## 🐛 Known Limitations

1. **Twilio**: Requires active Twilio account and credits
2. **Site Tracking**: Requires JavaScript enabled on target website
3. **LinkedIn**: Manual logging required (no direct API integration)
4. **Scalability**: Single-server deployment (can be enhanced with load balancer)

## 🚀 Future Enhancements

- [ ] WhatsApp integration via Twilio
- [ ] SMS campaign tracking
- [ ] Advanced analytics with Chart.js
- [ ] Export funnel data to CSV/PDF
- [ ] Email notification for funnel milestones
- [ ] AI-powered lead scoring
- [ ] Multi-language support
- [ ] Mobile app integration

## 📞 API Reference

### Twilio Endpoints
- `POST /index.php?module=TwilioIntegration&action=makeCall`
- `POST /index.php?module=TwilioIntegration&action=webhook`
- `GET /index.php?module=TwilioIntegration&action=config`

### Journey Endpoints
- `POST /index.php?module=LeadJourney&action=trackVisit`
- `GET /index.php?module=LeadJourney&action=timeline`

### Dashboard Endpoints
- `GET /index.php?module=FunnelDashboard&action=dashboard`

---

**Version**: 1.0.0  
**Last Updated**: 2025-11-16  
**Maintainer**: SuiteCRM Extended Team
