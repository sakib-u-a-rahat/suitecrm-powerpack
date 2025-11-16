# SuiteCRM Extended - Quick Start Guide

## 🚀 Quick Start (5 Minutes)

### 0. Setup MySQL 8 Database

**Create database:**
```sql
CREATE DATABASE suitecrm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'suitecrm'@'%' IDENTIFIED WITH mysql_native_password BY 'your_password';
GRANT ALL PRIVILEGES ON suitecrm.* TO 'suitecrm'@'%';
FLUSH PRIVILEGES;
```

### 1. Configure Environment

```bash
cp .env.example .env
nano .env  # Edit with your database and Twilio credentials
```

**For local MySQL 8:**
```bash
SUITECRM_DATABASE_HOST=host.docker.internal
SUITECRM_DATABASE_PORT=3306
SUITECRM_DATABASE_NAME=suitecrm
SUITECRM_DATABASE_USER=suitecrm
SUITECRM_DATABASE_PASSWORD=your_password

TWILIO_ACCOUNT_SID=your_account_sid_here
TWILIO_AUTH_TOKEN=your_auth_token_here
TWILIO_PHONE_NUMBER=+1234567890
```

**For remote/cloud MySQL:**
```bash
SUITECRM_DATABASE_HOST=your-db-host.com
# Rest same as above
```

### 2. Test Database Connection (Optional but Recommended)

```bash
./test-db-connection.sh
```

### 3. Build and Start

```bash
chmod +x build.sh start.sh stop.sh
./build.sh
./start.sh
```

### 4. Access SuiteCRM

Open in browser: http://localhost:8080

### 5. Complete SuiteCRM Setup Wizard

Follow the on-screen instructions to complete the initial setup.

### 6. Configure Modules

#### Twilio Integration
1. Go to **Admin** > **Twilio Integration** > **Configuration**
2. Enter your credentials
3. Enable features
4. Save

#### Test Click-to-Call
1. Open any Contact or Lead
2. Click a phone number
3. Call initiated!

#### View Lead Journey
1. Open any Contact or Lead
2. Navigate to **Lead Journey** > **Timeline**
3. See all touchpoints

#### View Funnel Dashboard
1. Go to **Funnel Dashboard** module
2. Select filters
3. Analyze your funnel!

---

## 🔧 Common Commands

```bash
# Test database connection
./test-db-connection.sh

# Start containers
./start.sh

# Stop containers
./stop.sh

# View logs
docker-compose logs -f

# Rebuild
docker-compose down && docker-compose build --no-cache && docker-compose up -d

# Access database
source .env
mysql -h $SUITECRM_DATABASE_HOST -P $SUITECRM_DATABASE_PORT -u $SUITECRM_DATABASE_USER -p$SUITECRM_DATABASE_PASSWORD $SUITECRM_DATABASE_NAME

# Clear cache
docker-compose exec suitecrm rm -rf cache/*
```

---

## 📊 Module URLs

- **Twilio Config**: http://localhost:8080/index.php?module=TwilioIntegration&action=config
- **Journey Timeline**: http://localhost:8080/index.php?module=LeadJourney&action=timeline&parent_type=Leads&parent_id={ID}
- **Funnel Dashboard**: http://localhost:8080/index.php?module=FunnelDashboard&action=dashboard

---

## 🐛 Troubleshooting

**Modules not visible?**
```bash
docker-compose restart suitecrm
```

**Permission errors?**
```bash
docker-compose exec suitecrm chown -R www-data:www-data /var/www/html
```

**Database connection failed?**
- Run: `./test-db-connection.sh`
- Check `.env` credentials
- Ensure database server is running
- For local MySQL, use `host.docker.internal`
- Check firewall allows port 3306

See [EXTERNAL_DATABASE.md](EXTERNAL_DATABASE.md) for detailed setup.

---

For detailed documentation, see [README.md](README.md)
