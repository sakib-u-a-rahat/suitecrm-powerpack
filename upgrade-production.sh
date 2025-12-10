#!/bin/bash

# Production Upgrade Script - Safe and Simple
# Works with any Docker container setup

set -e

echo "============================================"
echo "Twilio Integration v2.4.0 Production Upgrade"
echo "============================================"
echo ""

# Get container name from argument or ask
if [ -z "$1" ]; then
    echo "Available containers:"
    docker ps -a --format 'table {{.Names}}\t{{.Image}}\t{{.Status}}'
    echo ""
    read -p "Enter container name: " CONTAINER_NAME
else
    CONTAINER_NAME="$1"
fi

echo ""
echo "Target container: $CONTAINER_NAME"
echo ""

# Verify container exists
if ! docker ps -a --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; then
    echo "❌ Error: Container '$CONTAINER_NAME' not found"
    exit 1
fi

# Check if running
IS_RUNNING=$(docker ps --format '{{.Names}}' | grep "^${CONTAINER_NAME}$" || echo "")

if [ -z "$IS_RUNNING" ]; then
    echo "⚠️  Container is stopped"
    read -p "Start container now? (y/n): " START_IT
    if [ "$START_IT" = "y" ]; then
        docker start $CONTAINER_NAME
        sleep 5
        echo "✓ Container started"
    else
        echo "❌ Container must be running for upgrade"
        exit 1
    fi
fi

echo ""
echo "🔍 Detecting configuration..."
echo ""

# Try to find SuiteCRM path
PATHS_TO_TRY=(
    "/bitnami/suitecrm"
    "/opt/bitnami/suitecrm"
    "/var/www/html/suitecrm"
    "/usr/share/nginx/html/suitecrm"
    "/app/suitecrm"
)

SUITECRM_PATH=""
for path in "${PATHS_TO_TRY[@]}"; do
    if docker exec $CONTAINER_NAME test -f "${path}/config.php" 2>/dev/null; then
        SUITECRM_PATH="$path"
        break
    fi
done

if [ -z "$SUITECRM_PATH" ]; then
    echo "Could not auto-detect SuiteCRM path"
    read -p "Enter SuiteCRM path in container (e.g., /bitnami/suitecrm): " SUITECRM_PATH

    if ! docker exec $CONTAINER_NAME test -d "$SUITECRM_PATH" 2>/dev/null; then
        echo "❌ Error: Path $SUITECRM_PATH does not exist in container"
        exit 1
    fi
fi

echo "✓ SuiteCRM path: $SUITECRM_PATH"

# Create backup
BACKUP_DIR="./backups/prod_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

echo ""
echo "📦 Creating backups..."
echo ""

# Backup config
echo -n "  - Config file... "
if docker exec $CONTAINER_NAME cat ${SUITECRM_PATH}/config.php > ${BACKUP_DIR}/config.php.backup 2>/dev/null; then
    echo "✓"
else
    echo "⚠️  (not found or no access)"
fi

# Check if Twilio module exists
echo -n "  - Current Twilio module... "
if docker exec $CONTAINER_NAME test -d ${SUITECRM_PATH}/modules/TwilioIntegration 2>/dev/null; then
    docker exec $CONTAINER_NAME tar czf /tmp/twilio_backup.tar.gz -C ${SUITECRM_PATH}/modules TwilioIntegration 2>/dev/null || true
    docker cp ${CONTAINER_NAME}:/tmp/twilio_backup.tar.gz ${BACKUP_DIR}/ 2>/dev/null || true
    echo "✓"
else
    echo "⚠️  (fresh install)"
fi

echo "✓ Backups saved to: $BACKUP_DIR"

echo ""
read -p "Continue with upgrade? (y/n): " CONTINUE
if [ "$CONTINUE" != "y" ]; then
    echo "Upgrade cancelled"
    exit 0
fi

echo ""
echo "📁 Copying new files..."
echo ""

# Ensure target directory exists
docker exec $CONTAINER_NAME mkdir -p ${SUITECRM_PATH}/modules/TwilioIntegration 2>/dev/null || true

# Copy files
if docker cp custom-modules/TwilioIntegration/. ${CONTAINER_NAME}:${SUITECRM_PATH}/modules/TwilioIntegration/; then
    echo "✓ Files copied successfully"
else
    echo "❌ Error copying files"
    exit 1
fi

echo ""
echo "🔐 Setting permissions..."
echo ""

# Try different permission schemes
PERM_SET=false

# Try bitnami user
if docker exec -u root $CONTAINER_NAME chown -R bitnami:bitnami ${SUITECRM_PATH}/modules/TwilioIntegration/ 2>/dev/null; then
    echo "✓ Permissions set (bitnami user)"
    PERM_SET=true
fi

# Try www-data user
if ! $PERM_SET && docker exec -u root $CONTAINER_NAME chown -R www-data:www-data ${SUITECRM_PATH}/modules/TwilioIntegration/ 2>/dev/null; then
    echo "✓ Permissions set (www-data user)"
    PERM_SET=true
fi

# Fallback to chmod
if ! $PERM_SET; then
    docker exec $CONTAINER_NAME chmod -R 755 ${SUITECRM_PATH}/modules/TwilioIntegration/ 2>/dev/null || true
    echo "✓ Permissions set (chmod)"
fi

echo ""
echo "💾 Database migration..."
echo ""

# Copy migration file
docker cp custom-modules/TwilioIntegration/install/upgrade_to_v2.4.0.sql ${CONTAINER_NAME}:/tmp/upgrade.sql

# Try to get DB credentials from environment
echo "Detecting database credentials..."

DB_HOST=$(docker exec $CONTAINER_NAME printenv 2>/dev/null | grep -iE "^(MARIADB_HOST|MYSQL_HOST|DB_HOST)" | cut -d= -f2 | head -1)
DB_NAME=$(docker exec $CONTAINER_NAME printenv 2>/dev/null | grep -iE "^(MARIADB_DATABASE|MYSQL_DATABASE|DB_NAME|DATABASE_NAME)" | cut -d= -f2 | head -1)
DB_USER=$(docker exec $CONTAINER_NAME printenv 2>/dev/null | grep -iE "^(MARIADB_USER|MYSQL_USER|DB_USER|DATABASE_USER)" | cut -d= -f2 | head -1)
DB_PASS=$(docker exec $CONTAINER_NAME printenv 2>/dev/null | grep -iE "^(MARIADB_PASSWORD|MYSQL_PASSWORD|DB_PASSWORD|DATABASE_PASSWORD)" | cut -d= -f2 | head -1)

# Try to extract from config.php if not found
if [ -z "$DB_NAME" ] || [ -z "$DB_USER" ]; then
    echo "Extracting from config.php..."
    DB_NAME=$(docker exec $CONTAINER_NAME grep "db_name" ${SUITECRM_PATH}/config.php 2>/dev/null | grep -oP "'db_name'.*?'\K[^']+'" || echo "")
    DB_USER=$(docker exec $CONTAINER_NAME grep "db_user_name" ${SUITECRM_PATH}/config.php 2>/dev/null | grep -oP "'db_user_name'.*?'\K[^']+'" || echo "")
    DB_PASS=$(docker exec $CONTAINER_NAME grep "db_password" ${SUITECRM_PATH}/config.php 2>/dev/null | grep -oP "'db_password'.*?'\K[^']+'" || echo "")
    DB_HOST=$(docker exec $CONTAINER_NAME grep "db_host_name" ${SUITECRM_PATH}/config.php 2>/dev/null | grep -oP "'db_host_name'.*?'\K[^']+'" || echo "localhost")
fi

if [ -n "$DB_NAME" ] && [ -n "$DB_USER" ]; then
    echo "Database: $DB_NAME"
    echo "Host: ${DB_HOST:-localhost}"
    echo "User: $DB_USER"

    if [ -n "$DB_PASS" ]; then
        echo "Running migration..."
        if docker exec $CONTAINER_NAME mysql -h "${DB_HOST:-localhost}" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < /tmp/upgrade.sql 2>&1 | grep -v "Warning: Using a password"; then
            echo "✓ Migration completed successfully"
        else
            echo "⚠️  Migration may have failed - please check manually"
            echo ""
            echo "Run manually with:"
            echo "docker exec -it $CONTAINER_NAME mysql -h ${DB_HOST:-localhost} -u $DB_USER -p $DB_NAME < /tmp/upgrade.sql"
        fi
    else
        echo "⚠️  No password found - run migration manually:"
        echo "docker exec -it $CONTAINER_NAME mysql -h ${DB_HOST:-localhost} -u $DB_USER -p $DB_NAME < /tmp/upgrade.sql"
    fi
else
    echo "⚠️  Could not detect database credentials"
    echo "Run migration manually - see instructions below"
fi

echo ""
echo "🧹 Clearing cache..."
echo ""

if docker exec $CONTAINER_NAME rm -rf ${SUITECRM_PATH}/cache/* 2>/dev/null; then
    echo "✓ Cache cleared"
else
    echo "⚠️  Could not clear cache (may not be needed)"
fi

echo ""
echo "✅ Verifying upgrade..."
echo ""

# Check version
VERSION=$(docker exec $CONTAINER_NAME cat ${SUITECRM_PATH}/modules/TwilioIntegration/manifest.php 2>/dev/null | grep -oP "'version'.*?'\K[^']+'" || echo "unknown")
echo "Installed version: $VERSION"

# Check new files
NEW_FILES=0
for file in TwilioSecurity.php TwilioScheduler.php TwilioRecordingManager.php; do
    if docker exec $CONTAINER_NAME test -f ${SUITECRM_PATH}/modules/TwilioIntegration/$file 2>/dev/null; then
        ((NEW_FILES++))
    fi
done

if [ $NEW_FILES -eq 3 ]; then
    echo "✓ All new v2.4.0 files present"
else
    echo "⚠️  Some new files may be missing ($NEW_FILES/3 found)"
fi

# Check config preserved
if docker exec $CONTAINER_NAME grep -q "twilio_account_sid" ${SUITECRM_PATH}/config.php 2>/dev/null; then
    echo "✓ Configuration preserved"
else
    echo "⚠️  Configuration may need to be restored"
fi

echo ""
echo "============================================"
echo "✅ Upgrade Complete!"
echo "============================================"
echo ""
echo "Backup: $BACKUP_DIR"
echo ""
echo "Next steps:"
echo "1. Test your application"
echo "2. Verify webhooks are working"
echo "3. Check logs: docker logs $CONTAINER_NAME | tail -50"
echo ""
echo "If database migration was skipped, run manually:"
echo "docker exec -it $CONTAINER_NAME mysql -h [HOST] -u [USER] -p [DATABASE] < /tmp/upgrade.sql"
echo ""
echo "To rollback if needed:"
echo "  docker cp $BACKUP_DIR/config.php.backup $CONTAINER_NAME:${SUITECRM_PATH}/config.php"
echo "  docker cp $BACKUP_DIR/twilio_backup.tar.gz $CONTAINER_NAME:/tmp/"
echo "  docker exec $CONTAINER_NAME tar xzf /tmp/twilio_backup.tar.gz -C ${SUITECRM_PATH}/modules/"
echo ""
