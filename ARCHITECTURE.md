# SuiteCRM Extended - Architecture Overview

## System Architecture with External MySQL 8

```
┌─────────────────────────────────────────────────────────────────┐
│                         USER / BROWSER                           │
│                    http://localhost:8080                         │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             │ HTTP
                             │
┌────────────────────────────▼────────────────────────────────────┐
│                   DOCKER CONTAINER                               │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │              SuiteCRM Extended (Port 8080)                 │  │
│  │  ┌─────────────────────────────────────────────────────┐  │  │
│  │  │                  Apache + PHP 8.1                    │  │  │
│  │  │  ┌──────────────────────────────────────────────┐  │  │  │
│  │  │  │           SuiteCRM Core                      │  │  │  │
│  │  │  │                                              │  │  │  │
│  │  │  │  ┌────────────────────────────────────┐    │  │  │  │
│  │  │  │  │    Custom Modules                  │    │  │  │  │
│  │  │  │  │  • TwilioIntegration               │    │  │  │  │
│  │  │  │  │    - Click-to-call                 │    │  │  │  │
│  │  │  │  │    - Auto logging                  │    │  │  │  │
│  │  │  │  │    - Recordings                    │    │  │  │  │
│  │  │  │  │                                     │    │  │  │  │
│  │  │  │  │  • LeadJourney                     │    │  │  │  │
│  │  │  │  │    - Timeline view                 │    │  │  │  │
│  │  │  │  │    - Touchpoint tracking           │    │  │  │  │
│  │  │  │  │                                     │    │  │  │  │
│  │  │  │  │  • FunnelDashboard                 │    │  │  │  │
│  │  │  │  │    - Analytics                     │    │  │  │  │
│  │  │  │  │    - Stage tracking                │    │  │  │  │
│  │  │  │  └────────────────────────────────────┘    │  │  │  │
│  │  │  └──────────────────────────────────────────────┘  │  │  │
│  │  └─────────────────────────────────────────────────────┘  │  │
│  └───────────────────────────────────────────────────────────┘  │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             │ MySQL Protocol
                             │ (Port 3306)
                             │
                ┌────────────┴────────────┐
                │                         │
                │                         │
┌───────────────▼────────┐  ┌─────────────▼──────────────┐
│   LOCAL MySQL 8        │  │   REMOTE / CLOUD MySQL 8   │
│   (host.docker.internal)│  │                            │
│                        │  │  • AWS RDS                 │
│  • On host machine     │  │  • Google Cloud SQL        │
│  • localhost:3306      │  │  • Azure Database          │
│                        │  │  • Remote Server           │
└────────────────────────┘  └────────────────────────────┘
```

## Data Flow

### 1. User Interaction
```
User → Browser → SuiteCRM (Port 8080)
```

### 2. Click-to-Call Flow
```
User clicks phone → JavaScript → TwilioIntegration API
                                      ↓
                              Twilio REST API
                                      ↓
                              Call Initiated
                                      ↓
                              Auto-logged to MySQL
```

### 3. Lead Journey Timeline
```
Touchpoint occurs → LeadJourney module → MySQL database
    ↓
Timeline aggregation ← Read from MySQL
    ↓
Displayed to user
```

### 4. Funnel Dashboard
```
User views dashboard → FunnelDashboard queries MySQL
                              ↓
                       Analytics calculations
                              ↓
                       Visual funnel display
```

## Database Connection Options

### Option 1: Local MySQL (Development)
```
Docker Container ──host.docker.internal──> Host MySQL (localhost:3306)
```

### Option 2: Remote Server
```
Docker Container ──network──> Remote MySQL Server (IP:3306)
```

### Option 3: Cloud Database (Production)
```
Docker Container ──internet──> AWS RDS / Cloud SQL (endpoint:3306)
```

### Option 4: Containerized (Optional)
```
Docker Network
    ├── SuiteCRM Container (suitecrm)
    └── MySQL Container (db) - Commented out by default
```

## External Integrations

```
┌─────────────────────────────────────────────────────────────┐
│                    SuiteCRM Extended                         │
└───────┬──────────────────────────┬──────────────────────────┘
        │                          │
        │                          │
        ▼                          ▼
┌────────────────┐      ┌────────────────────────┐
│  Twilio API    │      │   External MySQL 8     │
│                │      │                        │
│  • Make calls  │      │  • suitecrm database   │
│  • Recordings  │      │  • All CRM data        │
│  • Status      │      │  • Module tables       │
└────────────────┘      └────────────────────────┘
```

## File Structure

```
/var/www/html (SuiteCRM Root)
├── include/            # SuiteCRM core
├── modules/            # Standard + custom modules
│   ├── TwilioIntegration/
│   ├── LeadJourney/
│   └── FunnelDashboard/
├── custom/
│   └── modules/        # Mounted from host
│       ├── TwilioIntegration/
│       ├── LeadJourney/
│       └── FunnelDashboard/
├── cache/              # Application cache
├── upload/             # File uploads
└── config_override.php # Database connection config
```

## Environment Variables Flow

```
.env file
    │
    ├─> SUITECRM_DATABASE_HOST ──┐
    ├─> SUITECRM_DATABASE_PORT ──┤
    ├─> SUITECRM_DATABASE_NAME ──┼─> Docker Environment
    ├─> SUITECRM_DATABASE_USER ──┤
    ├─> SUITECRM_DATABASE_PASSWORD┘
    │
    ├─> TWILIO_ACCOUNT_SID ──────┐
    ├─> TWILIO_AUTH_TOKEN ────────┼─> Twilio Config
    └─> TWILIO_PHONE_NUMBER ──────┘
         │
         └─> config_override.php
                   │
                   └─> SuiteCRM reads configuration
```

## Network Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    Host Machine                          │
│                                                          │
│  ┌────────────────────────────────────────────────┐    │
│  │  Docker Network (suitecrm-network)             │    │
│  │                                                 │    │
│  │  ┌──────────────────────────────────────────┐ │    │
│  │  │   SuiteCRM Container                     │ │    │
│  │  │   - Port 8080:80                         │ │    │
│  │  │   - Extra host: host.docker.internal     │ │    │
│  │  └──────────────────────────────────────────┘ │    │
│  │                                                 │    │
│  └─────────────────────┬───────────────────────────┘    │
│                        │                                 │
│  ┌─────────────────────▼───────────────────────────┐    │
│  │   MySQL 8 (localhost:3306)                      │    │
│  │   - Accessible via host.docker.internal         │    │
│  └─────────────────────────────────────────────────┘    │
│                                                          │
└──────────────────────────────────────────────────────────┘
```

## Security Layers

```
┌─────────────────────────────────────────────────┐
│  User Authentication (SuiteCRM)                 │
└────────────────────┬────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────┐
│  Application Layer Security                     │
│  • CSRF tokens                                  │
│  • XSS protection                               │
│  • SQL injection prevention                     │
└────────────────────┬────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────┐
│  Database Authentication                        │
│  • MySQL user/password                          │
│  • Host-based access control                    │
│  • SSL (optional)                               │
└─────────────────────────────────────────────────┘
```

## Deployment Scenarios

### Development
```
Developer Machine
    └── Docker Desktop
        └── SuiteCRM Container → Local MySQL (host.docker.internal)
```

### Staging
```
Staging Server
    └── Docker
        └── SuiteCRM Container → Staging MySQL (remote server)
```

### Production
```
Cloud Infrastructure
    ├── EC2 / Compute Instance
    │   └── Docker
    │       └── SuiteCRM Container
    │
    └── RDS / Cloud SQL
        └── MySQL 8 Database
```

## Module Integration Points

```
SuiteCRM Core
    │
    ├─> TwilioIntegration
    │   ├─> Contacts module (phone field extension)
    │   ├─> Leads module (phone field extension)
    │   ├─> Calls module (auto-logging)
    │   └─> Admin panel (configuration)
    │
    ├─> LeadJourney
    │   ├─> Leads module (timeline view)
    │   ├─> Contacts module (timeline view)
    │   ├─> Calls module (touchpoint source)
    │   ├─> Emails module (touchpoint source)
    │   ├─> Meetings module (touchpoint source)
    │   └─> Custom tracking (site visits, LinkedIn)
    │
    └─> FunnelDashboard
        ├─> Leads module (funnel data source)
        ├─> Opportunities module (funnel stages)
        ├─> Campaigns module (source tracking)
        └─> Reports module (analytics integration)
```

---

This architecture provides:
- ✅ Flexibility: Choose your database deployment
- ✅ Scalability: Easy to scale database independently
- ✅ Security: Multiple authentication layers
- ✅ Performance: Optimized for external databases
- ✅ Maintainability: Clear separation of concerns
