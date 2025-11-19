# Environment Variables Reference

## Database Configuration (Required)

### SUITECRM_DATABASE_HOST
- **Description**: MySQL database host address
- **Required**: Yes
- **Examples**: 
  - `localhost` (for local MySQL)
  - `host.docker.internal` (for MySQL on Docker host)
  - `mysql.example.com` (remote server)
  - `db-mysql-nyc3-12345.ondigitalocean.com` (DigitalOcean Managed Database)
  - `mydb.abc123.us-east-1.rds.amazonaws.com` (AWS RDS)

### SUITECRM_DATABASE_PORT
- **Description**: MySQL database port
- **Required**: No
- **Default**: `3306`
- **Example**: `25060` (DigitalOcean managed databases often use custom ports)

### SUITECRM_DATABASE_USER
- **Description**: MySQL database username
- **Required**: Yes
- **Example**: `doadmin`, `suitecrm_user`

### SUITECRM_DATABASE_PASSWORD
- **Description**: MySQL database password
- **Required**: Yes
- **Security**: Never commit passwords to version control

### SUITECRM_DATABASE_NAME
- **Description**: MySQL database name
- **Required**: Yes
- **Default**: `suitecrm`
- **Example**: `suitecrm_production`

## Site Configuration (Required)

### SUITECRM_SITE_URL
- **Description**: Full URL where SuiteCRM will be accessible
- **Required**: Yes
- **Format**: Must include protocol (http/https)
- **Examples**:
  - `http://localhost` (local development)
  - `https://crm.example.com` (production)
  - `https://suitecrm.yourdomain.com`
- **Important**: Must match your actual domain/URL

## Twilio Integration (Optional)

### TWILIO_ACCOUNT_SID
- **Description**: Twilio Account SID
- **Required**: Only if using Twilio features
- **Example**: `ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx` (32 characters)

### TWILIO_AUTH_TOKEN
- **Description**: Twilio Auth Token
- **Required**: Only if using Twilio features
- **Security**: Keep this secret
- **Example**: `xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx` (32 characters)

### TWILIO_PHONE_NUMBER
- **Description**: Twilio phone number for calls and SMS
- **Required**: Only if using Twilio features
- **Format**: E.164 format
- **Example**: `+15551234567`

## Docker Compose Example

```yaml
version: '3.8'

services:
  suitecrm:
    image: mahir009/suitecrm-powerpack:latest
    ports:
      - "80:80"  # Note: Container listens on port 80, NOT 8080
    environment:
      # Database Configuration
      SUITECRM_DATABASE_HOST: db-mysql-nyc3-12345.ondigitalocean.com
      SUITECRM_DATABASE_PORT: 25060
      SUITECRM_DATABASE_USER: doadmin
      SUITECRM_DATABASE_PASSWORD: your-secure-password
      SUITECRM_DATABASE_NAME: suitecrm
      
      # Site URL (must match your domain)
      SUITECRM_SITE_URL: https://crm.yourdomain.com
      
      # Twilio (Optional)
      TWILIO_ACCOUNT_SID: your-account-sid
      TWILIO_AUTH_TOKEN: your-auth-token
      TWILIO_PHONE_NUMBER: +15551234567
    volumes:
      - suitecrm-data:/var/www/html
    restart: unless-stopped

volumes:
  suitecrm-data:
```

## Managed Database Compatibility

This image is compatible with:
- ✅ DigitalOcean Managed MySQL
- ✅ AWS RDS
- ✅ Google Cloud SQL
- ✅ Azure Database for MySQL
- ✅ Any MySQL 8.0+ server

The image automatically handles:
- SSL connections
- `sql_require_primary_key` setting
- Different port configurations
- Connection pooling

## Testing Your Configuration

Test database connectivity:
```bash
docker run --rm \
  -e SUITECRM_DATABASE_HOST=your-host \
  -e SUITECRM_DATABASE_PORT=3306 \
  -e SUITECRM_DATABASE_USER=your-user \
  -e SUITECRM_DATABASE_PASSWORD=your-password \
  -e SUITECRM_DATABASE_NAME=suitecrm \
  mahir009/suitecrm-powerpack:latest \
  mysql -h "$SUITECRM_DATABASE_HOST" -P "$SUITECRM_DATABASE_PORT" \
    -u "$SUITECRM_DATABASE_USER" -p"$SUITECRM_DATABASE_PASSWORD" \
    -e "SELECT 1"
```

## Security Best Practices

1. **Use Docker Secrets** (Docker Swarm):
```yaml
secrets:
  db_password:
    external: true

services:
  suitecrm:
    secrets:
      - db_password
    environment:
      SUITECRM_DATABASE_PASSWORD_FILE: /run/secrets/db_password
```

2. **Use .env file** (never commit to git):
```bash
# .env
SUITECRM_DATABASE_HOST=your-host
SUITECRM_DATABASE_PASSWORD=your-password
```

Then in docker-compose.yml:
```yaml
env_file:
  - .env
```

3. **Add to .gitignore**:
```
.env
.env.local
.env.production
```

## Port Configuration Note

**IMPORTANT**: The container listens on port **80**, not 8080.

Correct nginx proxy configuration:
```nginx
location / {
    proxy_pass http://suitecrm:80;  # Use port 80
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
}
```

Correct port mapping:
```yaml
ports:
  - "8080:80"  # Map host 8080 to container 80
  # OR
  - "80:80"    # Direct mapping
```
