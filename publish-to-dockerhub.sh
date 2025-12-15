#!/bin/bash
# Docker Hub Publishing Guide for SuiteCRM PowerPack

echo "=========================================="
echo "SuiteCRM PowerPack - Docker Hub Publishing"
echo "=========================================="
echo ""
echo "Repository: https://github.com/sakib9029/suitecrm-powerpack"
echo ""

# Step 1: Docker Hub Login
echo "STEP 1: Login to Docker Hub"
echo "----------------------------"
echo "Run: docker login"
echo "Enter your Docker Hub username and password when prompted"
echo ""
read -p "Press Enter when logged in..."

# Step 2: Build the image locally first
echo ""
echo "STEP 2: Build Docker Image Locally"
echo "-----------------------------------"
echo "Building image: sakib9029/suitecrm-powerpack:latest"
docker build -t sakib9029/suitecrm-powerpack:latest .
docker tag sakib9029/suitecrm-powerpack:latest sakib9029/suitecrm-powerpack:v1.0.0
docker tag sakib9029/suitecrm-powerpack:latest sakib9029/suitecrm-powerpack:1.0
docker tag sakib9029/suitecrm-powerpack:latest sakib9029/suitecrm-powerpack:1

echo ""
echo "✓ Image built successfully!"
echo ""

# Step 3: Test the image
echo "STEP 3: Test Docker Image (Optional)"
echo "-------------------------------------"
read -p "Do you want to test the image? (y/n): " test_choice
if [ "$test_choice" = "y" ]; then
    echo "Starting test container..."
    docker run -d --name suitecrm-powerpack-test \
        -p 8080:80 \
        -e SUITECRM_DATABASE_HOST=host.docker.internal \
        -e SUITECRM_DATABASE_NAME=suitecrm_test \
        -e SUITECRM_DATABASE_USER=test \
        -e SUITECRM_DATABASE_PASSWORD=test \
        sakib9029/suitecrm-powerpack:latest
    
    echo "Test container started on http://localhost:8080"
    read -p "Press Enter to stop test container..."
    docker stop suitecrm-powerpack-test
    docker rm suitecrm-powerpack-test
fi

# Step 4: Push to Docker Hub
echo ""
echo "STEP 4: Push to Docker Hub"
echo "--------------------------"
echo "Pushing images to Docker Hub..."
echo ""

docker push sakib9029/suitecrm-powerpack:latest
docker push sakib9029/suitecrm-powerpack:v1.0.0
docker push sakib9029/suitecrm-powerpack:1.0
docker push sakib9029/suitecrm-powerpack:1

echo ""
echo "=========================================="
echo "✅ SUCCESS! Images Published to Docker Hub"
echo "=========================================="
echo ""
echo "Available tags:"
echo "  • sakib9029/suitecrm-powerpack:latest"
echo "  • sakib9029/suitecrm-powerpack:v1.0.0"
echo "  • sakib9029/suitecrm-powerpack:1.0"
echo "  • sakib9029/suitecrm-powerpack:1"
echo ""
echo "View on Docker Hub:"
echo "  https://hub.docker.com/r/sakib9029/suitecrm-powerpack"
echo ""
echo "Pull with:"
echo "  docker pull sakib9029/suitecrm-powerpack:latest"
echo ""
echo "GitHub Repository:"
echo "  https://github.com/sakib-u-ahmed-r/suitecrm-powerpack"
echo ""
