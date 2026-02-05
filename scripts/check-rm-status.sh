#!/bin/bash
# ===========================================
# RM Feature Diagnostic Script
# ===========================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR/.."

COMPOSE_FILE="docker-compose.prod.yml"
ENV_FILE=".env.production"

echo "============================================="
echo "  RM Feature Diagnostic Check"
echo "============================================="
echo ""

# Check 1: Git status
echo "1️⃣  Checking Git status..."
echo "Current branch: $(git branch --show-current)"
echo "Last commit: $(git log -1 --oneline)"
echo "Local changes: $(git status --short | wc -l) files"
echo ""

# Check 2: RM files exist in repository
echo "2️⃣  Checking RM files in repository..."
rm_files=$(find backend/app/Controllers/Api/V1 -name "Rm*.php" 2>/dev/null | wc -l)
echo "✓ Found $rm_files RM controller files"
rm_models=$(find backend/app/Models -name "Rm*.php" 2>/dev/null | wc -l)
echo "✓ Found $rm_models RM model files"
rm_migrations=$(find backend/app/Database/Migrations -name "*Rm*.php" 2>/dev/null | wc -l)
echo "✓ Found $rm_migrations RM migration files"
echo ""

# Check 3: Container status
echo "3️⃣  Checking container status..."
docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" ps backend
echo ""

# Check 4: RM files in container
echo "4️⃣  Checking RM files in container..."
echo "Controllers:"
docker compose -f "$COMPOSE_FILE" exec backend find /var/www/html/app/Controllers/Api/V1 -name "Rm*.php" -exec basename {} \; 2>/dev/null || echo "❌ Error accessing container"
echo ""
echo "Models:"
docker compose -f "$COMPOSE_FILE" exec backend find /var/www/html/app/Models -name "Rm*.php" -exec basename {} \; 2>/dev/null || echo "❌ Error accessing container"
echo ""

# Check 5: Autoload files
echo "5️⃣  Checking autoload status..."
autoload_date=$(docker compose -f "$COMPOSE_FILE" exec backend stat -c %y /var/www/html/vendor/composer/autoload_classmap.php 2>/dev/null | cut -d' ' -f1)
echo "Autoload last updated: $autoload_date"
echo ""
echo "Checking if RM classes are in autoload:"
docker compose -f "$COMPOSE_FILE" exec backend grep -c "RmProject" /var/www/html/vendor/composer/autoload_classmap.php 2>/dev/null && echo "✓ RM classes found in autoload" || echo "❌ RM classes NOT in autoload"
echo ""

# Check 6: Routes
echo "6️⃣  Checking RM routes..."
rm_routes=$(docker compose -f "$COMPOSE_FILE" exec backend php spark routes 2>/dev/null | grep -c "api/v1/rm/" || echo "0")
echo "✓ Found $rm_routes RM routes registered"
echo ""

# Check 7: Database tables
echo "7️⃣  Checking RM database tables..."
docker compose -f "$COMPOSE_FILE" exec backend php -r "
try {
    \$db = \Config\Database::connect();
    \$tables = \$db->listTables();
    \$rmTables = array_filter(\$tables, function(\$t) { return strpos(\$t, 'rm_') === 0; });
    echo 'RM Tables: ' . count(\$rmTables) . PHP_EOL;
    foreach (\$rmTables as \$table) {
        echo '  ✓ ' . \$table . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Error: ' . \$e->getMessage() . PHP_EOL;
}
" 2>/dev/null || echo "❌ Error checking database"
echo ""

# Check 8: Backend container uptime
echo "8️⃣  Backend container info..."
container_started=$(docker inspect crm_backend --format='{{.State.StartedAt}}' 2>/dev/null | cut -d'T' -f1)
echo "Container started: $container_started"
echo ""

# Summary
echo "============================================="
echo "  Diagnostic Summary"
echo "============================================="
echo ""
echo "If you see issues above, try these fixes:"
echo ""
echo "1. If autoload is outdated:"
echo "   docker compose -f $COMPOSE_FILE exec backend composer dump-autoload --optimize --no-dev"
echo "   docker compose -f $COMPOSE_FILE restart backend"
echo ""
echo "2. If database tables are missing:"
echo "   docker compose -f $COMPOSE_FILE exec backend php spark migrate"
echo ""
echo "3. If RM routes are missing (0 routes):"
echo "   Check if autoload is up to date and restart backend"
echo ""
echo "4. Full redeploy:"
echo "   ./scripts/prod.sh deploy"
echo ""
