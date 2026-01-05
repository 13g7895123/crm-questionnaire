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
