# External MySQL 8 Database Setup Guide

## Overview

This SuiteCRM Extended setup now supports external MySQL 8 databases, including:
- Local MySQL 8 installations
- Remote MySQL servers
- Cloud databases (AWS RDS, Google Cloud SQL, Azure Database, etc.)

## Quick Setup

### 1. Prepare Your MySQL 8 Database

Create a database and user for SuiteCRM:

```sql
-- Connect to your MySQL 8 server
mysql -u root -p

-- Create database
CREATE DATABASE suitecrm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user (replace 'your_password' with a secure password)
CREATE USER 'suitecrm'@'%' IDENTIFIED BY 'your_password';

-- Grant privileges
GRANT ALL PRIVILEGES ON suitecrm.* TO 'suitecrm'@'%';
FLUSH PRIVILEGES;

-- Exit
EXIT;
```

### 2. Configure Connection

Edit your `.env` file:

```bash
cp .env.example .env
nano .env
```

Update the database settings:

```bash
# For LOCAL MySQL 8 (on host machine)
SUITECRM_DATABASE_HOST=host.docker.internal
SUITECRM_DATABASE_PORT=3306
SUITECRM_DATABASE_NAME=suitecrm
SUITECRM_DATABASE_USER=suitecrm
SUITECRM_DATABASE_PASSWORD=your_secure_password

# For REMOTE MySQL 8 (IP address or hostname)
# SUITECRM_DATABASE_HOST=192.168.1.100
# or
# SUITECRM_DATABASE_HOST=mysql.example.com

# For Cloud Database (AWS RDS example)
# SUITECRM_DATABASE_HOST=mydb.xxxxxxxxxxxx.us-east-1.rds.amazonaws.com
# SUITECRM_DATABASE_PORT=3306
```

### 3. Build and Start

```bash
./build.sh
./start.sh
```

## Connection Scenarios

### Scenario 1: Local MySQL 8 (Same Machine)

**Setup:**
```bash
# .env configuration
SUITECRM_DATABASE_HOST=host.docker.internal
SUITECRM_DATABASE_PORT=3306
SUITECRM_DATABASE_NAME=suitecrm
SUITECRM_DATABASE_USER=suitecrm
SUITECRM_DATABASE_PASSWORD=your_password
```

**MySQL Configuration:**
Ensure MySQL is listening on localhost. Check `/etc/mysql/mysql.conf.d/mysqld.cnf`:
```ini
bind-address = 0.0.0.0
```

Restart MySQL:
```bash
sudo systemctl restart mysql
```

### Scenario 2: Remote MySQL Server

**Setup:**
```bash
# .env configuration
SUITECRM_DATABASE_HOST=192.168.1.100
SUITECRM_DATABASE_PORT=3306
SUITECRM_DATABASE_NAME=suitecrm
SUITECRM_DATABASE_USER=suitecrm
SUITECRM_DATABASE_PASSWORD=your_password
```

**MySQL Configuration:**
1. Allow remote connections in MySQL config
2. Configure firewall to allow port 3306
3. Create user with appropriate host permissions

### Scenario 3: AWS RDS MySQL

**Setup:**
```bash
# .env configuration
SUITECRM_DATABASE_HOST=mydb.xxxxxxxxxxxx.us-east-1.rds.amazonaws.com
SUITECRM_DATABASE_PORT=3306
SUITECRM_DATABASE_NAME=suitecrm
SUITECRM_DATABASE_USER=admin
SUITECRM_DATABASE_PASSWORD=your_rds_password
```

**AWS Configuration:**
1. Create RDS MySQL 8.0 instance
2. Configure security group to allow inbound on port 3306
3. Use RDS endpoint as database host

### Scenario 4: Google Cloud SQL

**Setup:**
```bash
# .env configuration
SUITECRM_DATABASE_HOST=34.123.456.789  # Cloud SQL IP
SUITECRM_DATABASE_PORT=3306
SUITECRM_DATABASE_NAME=suitecrm
SUITECRM_DATABASE_USER=suitecrm
SUITECRM_DATABASE_PASSWORD=your_password
```

**GCP Configuration:**
1. Create Cloud SQL MySQL 8.0 instance
2. Add your IP to authorized networks
3. Enable public IP or use Cloud SQL Proxy

## MySQL 8 Specific Configuration

### Authentication Plugin

MySQL 8 uses `caching_sha2_password` by default. If you encounter authentication issues, you can:

**Option 1:** Use native password (recommended for compatibility)
```sql
ALTER USER 'suitecrm'@'%' IDENTIFIED WITH mysql_native_password BY 'your_password';
FLUSH PRIVILEGES;
```

**Option 2:** Update the Dockerfile to support caching_sha2_password
(Already included in the setup)

### Performance Optimization

Add to your MySQL 8 configuration (`/etc/mysql/mysql.conf.d/mysqld.cnf`):

```ini
[mysqld]
# Performance settings
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
max_connections = 200
query_cache_size = 0
query_cache_type = 0

# Character set
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# MySQL 8 specific
default_authentication_plugin = mysql_native_password
```

## Testing Connection

### Test from Host Machine

```bash
mysql -h localhost -P 3306 -u suitecrm -p suitecrm
```

### Test from Docker Container

```bash
docker-compose exec suitecrm mysql -h $SUITECRM_DATABASE_HOST -P $SUITECRM_DATABASE_PORT -u $SUITECRM_DATABASE_USER -p$SUITECRM_DATABASE_PASSWORD $SUITECRM_DATABASE_NAME
```

### Test with PHP

```bash
docker-compose exec suitecrm php -r "
\$conn = new mysqli(
    getenv('SUITECRM_DATABASE_HOST'),
    getenv('SUITECRM_DATABASE_USER'),
    getenv('SUITECRM_DATABASE_PASSWORD'),
    getenv('SUITECRM_DATABASE_NAME'),
    getenv('SUITECRM_DATABASE_PORT')
);
if (\$conn->connect_error) {
    die('Connection failed: ' . \$conn->connect_error);
}
echo 'Successfully connected to MySQL ' . \$conn->server_info . PHP_EOL;
\$conn->close();
"
```

## Troubleshooting

### Connection Refused

**Problem:** Cannot connect to database

**Solutions:**
1. Check MySQL is running: `sudo systemctl status mysql`
2. Verify MySQL is listening: `netstat -tlnp | grep 3306`
3. Check firewall: `sudo ufw status`
4. For local MySQL, use `host.docker.internal` as host
5. Verify credentials in `.env` file

### Authentication Failed

**Problem:** Access denied for user

**Solutions:**
```sql
-- Recreate user with correct permissions
DROP USER IF EXISTS 'suitecrm'@'%';
CREATE USER 'suitecrm'@'%' IDENTIFIED WITH mysql_native_password BY 'your_password';
GRANT ALL PRIVILEGES ON suitecrm.* TO 'suitecrm'@'%';
FLUSH PRIVILEGES;
```

### Host Not Allowed

**Problem:** Host 'xxx.xxx.xxx.xxx' is not allowed to connect

**Solutions:**
```sql
-- Allow connection from specific IP
CREATE USER 'suitecrm'@'172.%.%.%' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON suitecrm.* TO 'suitecrm'@'172.%.%.%';
FLUSH PRIVILEGES;

-- Or allow from anywhere (less secure)
CREATE USER 'suitecrm'@'%' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON suitecrm.* TO 'suitecrm'@'%';
FLUSH PRIVILEGES;
```

### Timeout Issues

**Problem:** Database connection timeouts

**Solutions:**
1. Increase timeout in MySQL config:
```ini
wait_timeout = 600
interactive_timeout = 600
```

2. Check network connectivity:
```bash
ping your-db-host
telnet your-db-host 3306
```

## Security Best Practices

### 1. Use Strong Passwords
```bash
# Generate a strong password
openssl rand -base64 32
```

### 2. Limit User Permissions
```sql
-- Only grant necessary privileges
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, 
      CREATE TEMPORARY TABLES, LOCK TABLES 
ON suitecrm.* TO 'suitecrm'@'%';
```

### 3. Use SSL Connections (Production)

In your MySQL config:
```ini
[mysqld]
require_secure_transport = ON
ssl-ca=/path/to/ca.pem
ssl-cert=/path/to/server-cert.pem
ssl-key=/path/to/server-key.pem
```

Update `.env`:
```bash
SUITECRM_DATABASE_SSL=true
```

### 4. IP Whitelisting

Only allow connections from specific IPs:
```sql
CREATE USER 'suitecrm'@'your.server.ip' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON suitecrm.* TO 'suitecrm'@'your.server.ip';
```

## Migration from Containerized to External Database

### Export from Container

```bash
# Export data from containerized database
docker-compose exec db mysqldump -u suitecrm -p suitecrm > backup.sql
```

### Import to External Database

```bash
# Import to external MySQL 8
mysql -h your-external-host -u suitecrm -p suitecrm < backup.sql
```

### Update Configuration

```bash
# Update .env with external database settings
nano .env

# Restart containers
docker-compose down
docker-compose up -d
```

## Using Containerized Database (Optional)

If you prefer to use a containerized MySQL 8 instead of external, uncomment the database service in `docker-compose.yml`:

```yaml
services:
  db:
    image: mysql:8.0
    container_name: suitecrm-db
    command: --default-authentication-plugin=mysql_native_password
    environment:
      - MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD:-rootpassword}
      - MYSQL_DATABASE=${SUITECRM_DATABASE_NAME:-suitecrm}
      - MYSQL_USER=${SUITECRM_DATABASE_USER:-suitecrm}
      - MYSQL_PASSWORD=${SUITECRM_DATABASE_PASSWORD:-suitecrm}
    volumes:
      - db-data:/var/lib/mysql
    networks:
      - suitecrm-network
```

Then update `.env`:
```bash
SUITECRM_DATABASE_HOST=db
```

## Monitoring

### Check Database Status

```bash
# Connection count
docker-compose exec suitecrm mysql -h $SUITECRM_DATABASE_HOST -u $SUITECRM_DATABASE_USER -p$SUITECRM_DATABASE_PASSWORD -e "SHOW STATUS LIKE 'Threads_connected';"

# Database size
docker-compose exec suitecrm mysql -h $SUITECRM_DATABASE_HOST -u $SUITECRM_DATABASE_USER -p$SUITECRM_DATABASE_PASSWORD -e "
SELECT 
    table_schema AS 'Database',
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
FROM information_schema.TABLES 
WHERE table_schema = '$SUITECRM_DATABASE_NAME'
GROUP BY table_schema;
"
```

## Backup Strategy

### Automated Backups

Create a backup script:

```bash
#!/bin/bash
# backup.sh

BACKUP_DIR="/path/to/backups"
DATE=$(date +%Y%m%d_%H%M%S)

# Source database credentials
source .env

# Create backup
mysqldump -h $SUITECRM_DATABASE_HOST \
          -P $SUITECRM_DATABASE_PORT \
          -u $SUITECRM_DATABASE_USER \
          -p$SUITECRM_DATABASE_PASSWORD \
          $SUITECRM_DATABASE_NAME > "$BACKUP_DIR/suitecrm_$DATE.sql"

# Compress
gzip "$BACKUP_DIR/suitecrm_$DATE.sql"

# Delete old backups (older than 30 days)
find $BACKUP_DIR -name "suitecrm_*.sql.gz" -mtime +30 -delete

echo "Backup completed: suitecrm_$DATE.sql.gz"
```

Schedule with cron:
```bash
# Run daily at 2 AM
0 2 * * * /path/to/backup.sh
```

---

**Note:** Always test database connectivity before deploying to production. Ensure your database server has adequate resources and proper security configurations.
