#!/bin/bash
# ===========================================
# Development Environment Startup Script
# ===========================================

set -e
 
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR/.."

echo "============================================="
echo "  CRM Questionnaire - Development Environment"
echo "============================================="

# Check if .env file exists
if [ ! -f .env ]; then
    echo "Error: .env file not found!"
    echo "Please create .env file from .env.example"
    exit 1
fi

# Load environment variables
export $(grep -v '^#' .env | xargs)

# Sync backend database configuration
echo ""
echo "Syncing backend database configuration..."

# Ensure backend/.env exists
if [ ! -f "backend/.env" ]; then
    echo "⚠️  backend/.env not found, creating from template..."
    if [ -f "backend/env" ]; then
        cp backend/env backend/.env
        echo "✅ Created backend/.env from backend/env"
    else
        echo "❌ Error: Neither backend/.env nor backend/env template found!"
        exit 1
    fi
fi

# Backup current .env
cp backend/.env backend/.env.bak 2>/dev/null || true

# Update or add database settings
sed -i "s/^database.default.hostname = .*/database.default.hostname = ${DB_HOST}/" backend/.env
sed -i "s/^database.default.database = .*/database.default.database = ${DB_DATABASE}/" backend/.env
sed -i "s/^database.default.username = .*/database.default.username = ${DB_USERNAME}/" backend/.env
sed -i "s/^database.default.password = .*/database.default.password = ${DB_PASSWORD}/" backend/.env
sed -i "s/^database.default.port = .*/database.default.port = 3306/" backend/.env
sed -i "s/^CI_ENVIRONMENT = .*/CI_ENVIRONMENT = development/" backend/.env

echo "  - Hostname: ${DB_HOST}"
echo "  - Database: ${DB_DATABASE}"
echo "  - Port: 3306"

# Verify the configuration
if grep -q "database.default.hostname = ${DB_HOST}" backend/.env; then
    echo "✓ Configuration verified"
else
    echo "✗ Warning: Configuration may not be correct"
fi

echo ""
echo "Starting services..."
docker compose up -d --build

echo ""
echo "Waiting for services to be ready..."
sleep 5

# Install/Update Composer dependencies
echo ""
echo "Installing/Updating Composer dependencies..."
if docker compose exec backend composer install --no-interaction --prefer-dist --optimize-autoloader; then
    echo "✅ Composer dependencies installed successfully"
else
    echo "⚠️  Warning: Composer install failed"
    echo "  Dependencies will be installed when backend container starts"
fi

echo ""
echo "============================================="
echo "  Services Started Successfully!"
echo "============================================="
echo ""
echo "  Backend API:    http://localhost:${BACKEND_PORT}"
echo "  phpMyAdmin:     http://localhost:${PMA_PORT}"
echo "  Database:       localhost:${DB_PORT}"
echo ""
echo "  To start frontend development server:"
echo "  cd frontend && npm run dev"
echo ""
echo "  To view logs:"
echo "  docker compose logs -f"
echo ""
echo "  To stop services:"
echo "  docker compose down"
echo "============================================="
