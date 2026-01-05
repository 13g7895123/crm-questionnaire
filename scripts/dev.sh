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
    # Update or add database settings
    sed -i "s/^database.default.hostname = .*/database.default.hostname = ${DB_HOST}/" backend/.env
    sed -i "s/^database.default.database = .*/database.default.database = ${DB_DATABASE}/" backend/.env
    sed -i "s/^database.default.username = .*/database.default.username = ${DB_USERNAME}/" backend/.env
    sed -i "s/^database.default.password = .*/database.default.password = ${DB_PASSWORD}/" backend/.env
    sed -i "s/^database.default.port = .*/database.default.port = ${DB_PORT:-3306}/" backend/.env
    
    # Update environment to development
    sed -i "s/^CI_ENVIRONMENT = .*/CI_ENVIRONMENT = development/" backend/.env
    
    echo "Backend configuration synced successfully"
else
    echo "Warning: backend/.env not found, skipping sync"
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
