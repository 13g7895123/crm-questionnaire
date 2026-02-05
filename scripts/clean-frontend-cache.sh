#!/bin/bash
# ===========================================
# Frontend Cache Cleaning Script
# Use this when you suspect frontend is using old cached code
# ===========================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR/.."

COMPOSE_FILE="docker-compose.prod.yml"
ENV_FILE=".env.production"

echo "============================================="
echo "  Frontend Cache Cleaning"
echo "============================================="
echo ""
echo "⚠️  WARNING: This will clear ALL frontend caches"
echo "   - Nuxt build cache (.nuxt, .output)"
echo "   - Node modules"
echo "   - Docker volumes (blue & green)"
echo ""
read -p "Continue? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Cancelled."
    exit 0
fi

echo ""
echo "1️⃣  Cleaning local frontend build artifacts..."
rm -rf frontend/.nuxt frontend/.output frontend/node_modules
echo "✅ Removed .nuxt, .output, node_modules"

echo ""
echo "2️⃣  Cleaning Docker volumes..."
docker volume rm crm-questionnaire_frontend_dist_blue 2>/dev/null && echo "✅ Removed blue volume" || echo "ℹ️  Blue volume not found (OK)"
docker volume rm crm-questionnaire_frontend_dist_green 2>/dev/null && echo "✅ Removed green volume" || echo "ℹ️  Green volume not found (OK)"

echo ""
echo "3️⃣  Recreating volumes..."
docker volume create crm-questionnaire_frontend_dist_blue
docker volume create crm-questionnaire_frontend_dist_green
echo "✅ Volumes recreated"

echo ""
echo "============================================="
echo "  Cache Cleaning Complete!"
echo "============================================="
echo ""
echo "Next steps:"
echo "  1. Run: ./scripts/prod.sh deploy-fresh"
echo "     (This will rebuild both blue and green)"
echo ""
echo "  Or manually rebuild:"
echo "  2. Run: ./scripts/prod.sh build blue"
echo "  3. Run: ./scripts/prod.sh build green"
echo "  4. Run: ./scripts/prod.sh switch [blue|green]"
echo ""
