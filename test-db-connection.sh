#!/bin/bash

# Test database connectivity for SuiteCRM Extended

echo "======================================"
echo "Database Connection Test"
echo "======================================"
echo ""

# Load environment variables
if [ -f .env ]; then
    source .env
else
    echo "Error: .env file not found!"
    echo "Please copy .env.example to .env and configure it."
    exit 1
fi

# Set defaults
DB_HOST="${SUITECRM_DATABASE_HOST:-localhost}"
DB_PORT="${SUITECRM_DATABASE_PORT:-3306}"
DB_NAME="${SUITECRM_DATABASE_NAME:-suitecrm}"
DB_USER="${SUITECRM_DATABASE_USER:-suitecrm}"
DB_PASS="${SUITECRM_DATABASE_PASSWORD}"

echo "Testing connection to:"
echo "  Host: $DB_HOST"
echo "  Port: $DB_PORT"
echo "  Database: $DB_NAME"
echo "  User: $DB_USER"
echo ""

# Test 1: Check if MySQL client is available
if ! command -v mysql &> /dev/null; then
    echo "❌ MySQL client not found. Installing..."
    sudo apt-get update && sudo apt-get install -y mysql-client
fi

# Test 2: Test network connectivity
echo "Testing network connectivity..."
if command -v nc &> /dev/null; then
    if nc -z "$DB_HOST" "$DB_PORT" 2>/dev/null; then
        echo "✅ Network connection successful"
    else
        echo "❌ Cannot reach $DB_HOST:$DB_PORT"
        echo "   Please check:"
        echo "   - Database server is running"
        echo "   - Firewall allows port $DB_PORT"
        echo "   - Host address is correct"
        exit 1
    fi
else
    echo "⚠️  netcat not available, skipping network test"
fi

# Test 3: Test MySQL connection
echo ""
echo "Testing MySQL connection..."
if mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1;" > /dev/null 2>&1; then
    echo "✅ MySQL connection successful"
else
    echo "❌ MySQL connection failed"
    echo "   Please check:"
    echo "   - Database credentials in .env"
    echo "   - User has proper permissions"
    echo "   - Database server is running"
    exit 1
fi

# Test 4: Check database exists
echo ""
echo "Checking if database exists..."
if mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME;" > /dev/null 2>&1; then
    echo "✅ Database '$DB_NAME' exists"
else
    echo "⚠️  Database '$DB_NAME' does not exist"
    echo ""
    read -p "Do you want to create it? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        echo "✅ Database created"
    else
        echo "Please create the database manually before proceeding"
        exit 1
    fi
fi

# Test 5: Check user permissions
echo ""
echo "Checking user permissions..."
GRANTS=$(mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" -e "SHOW GRANTS FOR CURRENT_USER();" 2>/dev/null)
if [[ $GRANTS == *"ALL PRIVILEGES"* ]] || [[ $GRANTS == *"$DB_NAME"* ]]; then
    echo "✅ User has sufficient permissions"
else
    echo "⚠️  User may not have sufficient permissions"
    echo "   Please grant permissions with:"
    echo "   GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'%';"
fi

# Test 6: Check MySQL version
echo ""
echo "Checking MySQL version..."
VERSION=$(mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" -N -e "SELECT VERSION();" 2>/dev/null)
echo "  MySQL Version: $VERSION"

if [[ $VERSION == 8.* ]]; then
    echo "✅ MySQL 8 detected"
elif [[ $VERSION == 5.7.* ]]; then
    echo "⚠️  MySQL 5.7 detected (MySQL 8 recommended)"
else
    echo "⚠️  Unsupported MySQL version"
fi

# Test 7: Check authentication plugin
echo ""
echo "Checking authentication plugin..."
PLUGIN=$(mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" -N -e "SELECT plugin FROM mysql.user WHERE user='$DB_USER' LIMIT 1;" 2>/dev/null)
echo "  Plugin: $PLUGIN"

if [[ $PLUGIN == "mysql_native_password" ]]; then
    echo "✅ Using mysql_native_password (recommended for compatibility)"
elif [[ $PLUGIN == "caching_sha2_password" ]]; then
    echo "⚠️  Using caching_sha2_password"
    echo "   This may require additional PHP configuration"
    echo "   Consider changing to mysql_native_password:"
    echo "   ALTER USER '$DB_USER'@'%' IDENTIFIED WITH mysql_native_password BY 'your_password';"
fi

echo ""
echo "======================================"
echo "✅ All tests passed!"
echo "======================================"
echo ""
echo "Your database is ready for SuiteCRM Extended."
echo "You can now run: ./build.sh && ./start.sh"
echo ""
