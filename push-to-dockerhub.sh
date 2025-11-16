#!/bin/bash
# Quick Docker Hub Push Script
# Run this after: docker login

set -e

echo "🚀 Pushing SuiteCRM PowerPack to Docker Hub..."
echo ""

# Check if logged in
if ! docker info 2>&1 | grep -q "Username"; then
    echo "❌ Not logged into Docker Hub!"
    echo "Please run: docker login"
    exit 1
fi

echo "✓ Logged into Docker Hub"
echo ""

# Push all tags
echo "Pushing acnologiaslayer/suitecrm-powerpack:latest..."
docker push acnologiaslayer/suitecrm-powerpack:latest

echo "Pushing acnologiaslayer/suitecrm-powerpack:v1.0.0..."
docker push acnologiaslayer/suitecrm-powerpack:v1.0.0

echo "Pushing acnologiaslayer/suitecrm-powerpack:1.0..."
docker push acnologiaslayer/suitecrm-powerpack:1.0

echo "Pushing acnologiaslayer/suitecrm-powerpack:1..."
docker push acnologiaslayer/suitecrm-powerpack:1

echo ""
echo "✅ All images pushed successfully!"
echo ""
echo "View on Docker Hub:"
echo "  https://hub.docker.com/r/acnologiaslayer/suitecrm-powerpack"
echo ""
echo "Pull with:"
echo "  docker pull acnologiaslayer/suitecrm-powerpack:latest"
echo ""
