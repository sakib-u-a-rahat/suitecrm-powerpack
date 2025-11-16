#!/bin/bash

echo "======================================"
echo "Starting SuiteCRM Extended..."
echo "======================================"

# Start the containers
docker-compose up -d

echo ""
echo "Containers started successfully!"
echo ""
echo "Access URLs:"
echo "  SuiteCRM: http://localhost:8080"
echo "  phpMyAdmin: http://localhost:8081"
echo ""
echo "To view logs: docker-compose logs -f"
echo "To stop: docker-compose down"
echo ""
