#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}PawnshopMS Docker Deployment Script${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo -e "${RED}Docker is not installed. Please install Docker first.${NC}"
    echo "Install with: curl -fsSL https://get.docker.com -o get-docker.sh && sudo sh get-docker.sh"
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker compose &> /dev/null && ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}Docker Compose is not installed. Please install Docker Compose first.${NC}"
    exit 1
fi

# Check if .env file exists
if [ ! -f .env ]; then
    echo -e "${YELLOW}.env file not found. Creating from .env.example...${NC}"
    if [ -f .env.example ]; then
        cp .env.example .env
        echo -e "${GREEN}.env file created. Please edit it with your configuration.${NC}"
    else
        echo -e "${RED}.env.example not found. Please create .env file manually.${NC}"
        exit 1
    fi
fi

# Stop existing containers if running
echo -e "${YELLOW}Stopping existing containers...${NC}"
docker compose down 2>/dev/null || true

# Build Docker image
echo -e "${YELLOW}Building Docker image...${NC}"
docker compose build --no-cache

if [ $? -ne 0 ]; then
    echo -e "${RED}Docker build failed!${NC}"
    exit 1
fi

# Start containers
echo -e "${YELLOW}Starting containers...${NC}"
docker compose up -d

if [ $? -ne 0 ]; then
    echo -e "${RED}Failed to start containers!${NC}"
    exit 1
fi

# Wait for container to be ready
echo -e "${YELLOW}Waiting for container to be ready...${NC}"
sleep 5

# Download QR code libraries
echo -e "${YELLOW}Downloading QR code libraries...${NC}"
mkdir -p public/js

# Download qrcode.js library
if [ ! -f public/js/qrcode.min.js ]; then
    echo -e "${YELLOW}Downloading qrcode.min.js...${NC}"
    curl -L -o public/js/qrcode.min.js https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}qrcode.min.js downloaded successfully.${NC}"
    else
        echo -e "${RED}Failed to download qrcode.min.js${NC}"
    fi
else
    echo -e "${GREEN}qrcode.min.js already exists. Skipping download.${NC}"
fi

# Download html5-qrcode library
if [ ! -f public/js/html5-qrcode.min.js ]; then
    echo -e "${YELLOW}Downloading html5-qrcode.min.js...${NC}"
    curl -L -o public/js/html5-qrcode.min.js https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}html5-qrcode.min.js downloaded successfully.${NC}"
    else
        echo -e "${RED}Failed to download html5-qrcode.min.js${NC}"
    fi
else
    echo -e "${GREEN}html5-qrcode.min.js already exists. Skipping download.${NC}"
fi

# Check and generate application key if not set
echo -e "${YELLOW}Checking application key...${NC}"
APP_KEY_LINE=$(grep "^APP_KEY=" .env 2>/dev/null || echo "")
if [ -z "$APP_KEY_LINE" ] || [ "$APP_KEY_LINE" = "APP_KEY=" ] || [ "$APP_KEY_LINE" = "APP_KEY=null" ]; then
    echo -e "${YELLOW}APP_KEY not set. Generating application key...${NC}"
    docker compose exec -T app php artisan key:generate --force 2>/dev/null || true
    echo -e "${GREEN}Application key generated successfully.${NC}"
else
    echo -e "${GREEN}APP_KEY is already set. Skipping key generation.${NC}"
fi

# Run migrations
echo -e "${YELLOW}Running database migrations...${NC}"
docker compose exec -T app php artisan migrate --force

if [ $? -ne 0 ]; then
    echo -e "${YELLOW}Migration failed. This might be normal if database is not configured.${NC}"
fi

# Run seeders
read -p "Do you want to run database seeders? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}Running database seeders...${NC}"
    docker compose exec -T app php artisan db:seed --force
fi

# Optimize Laravel
echo -e "${YELLOW}Optimizing Laravel...${NC}"
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache

# Restore file permissions on host (prevent Docker from making files executable)
echo -e "${YELLOW}Restoring file permissions...${NC}"
find . -type d -exec chmod 755 {} \; 2>/dev/null || true
find . -type f -exec chmod 644 {} \; 2>/dev/null || true
# Make scripts executable
find . -name "*.sh" -exec chmod +x {} \; 2>/dev/null || true
# Restore specific Laravel directories
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Get container status
echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Deployment Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${GREEN}Container Status:${NC}"
docker compose ps
echo ""
echo -e "${GREEN}Application is running on: http://localhost:8800${NC}"
echo ""
echo -e "${YELLOW}Useful commands:${NC}"
echo "  View logs:        docker compose logs -f"
echo "  Stop container:  docker compose down"
echo "  Restart:         docker compose restart"
echo "  Shell access:    docker compose exec app bash"
echo ""

