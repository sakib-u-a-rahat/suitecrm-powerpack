#!/bin/bash
set -e

echo "======================================"
echo "SuiteCRM Extended - Build Script"
echo "======================================"

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "Error: Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "Error: Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "Creating .env file from template..."
    cp .env.example .env
    echo "Please edit .env file with your Twilio credentials before starting."
fi

# Build the Docker image
echo "Building Docker image..."
docker-compose build

echo ""
echo "======================================"
echo "Build completed successfully!"
echo "======================================"
echo ""
echo "Next steps:"
echo "1. Edit .env file with your Twilio credentials"
echo "2. Run: docker-compose up -d"
echo "3. Access SuiteCRM at: http://localhost:8080"
echo "4. Access phpMyAdmin at: http://localhost:8081"
echo ""
echo "Default database credentials:"
echo "  Username: suitecrm"
echo "  Password: suitecrm"
echo ""
