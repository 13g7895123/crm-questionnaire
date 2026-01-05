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

if [ -f "backend/.env" ]; then
    # Backup current .env
    cp backend/.env backend/.env.bak 2>/dev/null || true
    
    # Update or add database settings
    sed -i "s/^database.default.hostname = .*/database.default.hostname = ${DB_HOST}/" backend/.env
    sed -i "s/^database.default.database = .*/database.default.database = ${DB_DATABASE}/" backend/.env
    sed -i "s/^database.default.username = .*/database.default.username = ${DB_USERNAME}/" backend/.env
    sed -i "s/^database.default.password = .*/database.default.password = ${DB_PASSWORD}/" backend/.env
    sed -i "s/^database.default.port = .*/database.default.port = ${DB_PORT:-3306}/" backend/.env
    
    # Ensure no localhost (which triggers socket connection)
    sed -i "s/^database.default.hostname = localhost$/database.default.hostname = ${DB_HOST}/" backend/.env
    
    # Update environment to development
    sed -i "s/^CI_ENVIRONMENT = .*/CI_ENVIRONMENT = development/" backend/.env
    
    echo "  - Hostname: ${DB_HOST}"
    echo "  - Database: ${DB_DATABASE}"
    
    # Verify the configuration
    if grep -q "database.default.hostname = ${DB_HOST}" backend/.env; then
        echo "✓ Configuration verified"
    else
        echo "✗ Warning: Configuration may not be correct"
    fi
else
    echo "Error: backend/.env not found!"
    exit 1
fi

echo ""
echo "Starting services..."
docker compose up -d --build

echo ""
echo "Waiting for services to be ready..."
sleep 5

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
