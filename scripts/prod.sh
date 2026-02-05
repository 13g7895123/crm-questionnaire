#!/bin/bash
# ===========================================
# Production Environment Deployment Script
# Frontend Blue-Green Deployment Strategy
# ===========================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR/.."

# Configuration
NGINX_CONF="./docker/nginx/conf.d/default.conf"
ENV_FILE=".env.production"
COMPOSE_FILE="docker-compose.prod.yml"

echo "============================================="
echo "  CRM Questionnaire - Production Deployment"
echo "  Frontend Blue-Green Deployment Strategy"
echo "============================================="

# Check if .env.production file exists
if [ ! -f "$ENV_FILE" ]; then
    echo "Error: $ENV_FILE file not found!"
    exit 1
fi

# Load environment variables
export $(grep -v '^#' "$ENV_FILE" | xargs)

# Function to get current active color from environment file
get_active_color() {
    if grep -q "ACTIVE_FRONTEND=green" "$ENV_FILE"; then
        echo "green"
    else
        echo "blue"
    fi
}

# Function to update active frontend in env file
update_env_active_frontend() {
    local target_color=$1
    
    # Update or add ACTIVE_FRONTEND in .env.production
    if grep -q "^ACTIVE_FRONTEND=" "$ENV_FILE"; then
        sed -i "s/^ACTIVE_FRONTEND=.*/ACTIVE_FRONTEND=$target_color/" "$ENV_FILE"
    else
        echo "ACTIVE_FRONTEND=$target_color" >> "$ENV_FILE"
    fi
}

# Function to switch traffic
switch_traffic() {
    local target_color=$1
    echo "Switching traffic to $target_color..."
    
    # Update environment variable
    update_env_active_frontend "$target_color"
    
    # Recreate nginx container with new environment variable
    docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" up -d nginx
    
    echo "Traffic switched to $target_color"
}

# Function to build frontend to a specific color
build_frontend() {
    local target_color=$1
    echo ""
    echo "Building frontend to $target_color..."
    
    if ! docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" --profile "build-$target_color" run --rm "frontend-builder-$target_color"; then
        echo ""
        echo "❌ Error: Frontend build failed for $target_color"
        echo "Deployment aborted. Current active frontend: $(get_active_color)"
        echo ""
        echo "To debug, check:"
        echo "  1. Frontend build logs above"
        echo "  2. Node.js memory issues: docker stats"
        echo "  3. Package installation errors"
        exit 1
    fi
    
    echo "✅ Frontend successfully built to $target_color!"
}

# Main deployment logic
case "${1:-deploy}" in
    deploy)
        CURRENT_COLOR=$(get_active_color)
        if [ "$CURRENT_COLOR" == "blue" ]; then
            NEW_COLOR="green"
        else
            NEW_COLOR="blue"
        fi
        
        echo ""
        echo "Current active frontend: $CURRENT_COLOR"
        echo "Deploying to:            $NEW_COLOR"
        echo ""
        
        # Function to update or add configuration line
        update_or_add_config() {
            local file=$1
            local key=$2
            local value=$3
            
            if grep -q "^${key} = " "$file" 2>/dev/null; then
                sed -i "s|^${key} = .*|${key} = ${value}|" "$file"
            else
                echo "${key} = ${value}" >> "$file"
            fi
        }
        
        # Step 0: Sync backend database configuration
        echo "Step 0: Syncing backend database configuration..."
        
        # Ensure backend/.env exists
        if [ ! -f "backend/.env" ]; then
            echo "⚠️  backend/.env not found, creating from template..."
            
            if [ -f "backend/env" ]; then
                cp backend/env backend/.env
                echo "✅ Created backend/.env from backend/env"
            else
                echo "❌ Error: Neither backend/.env nor backend/env template found!"
                echo "Please ensure backend/env exists or create backend/.env manually."
                exit 1
            fi
        fi
        
        # Backup current .env
        cp backend/.env backend/.env.bak
        echo "✓ Backed up backend/.env to backend/.env.bak"
        
        # Update or add database settings using robust function
        update_or_add_config "backend/.env" "database.default.hostname" "${DB_HOST}"
        update_or_add_config "backend/.env" "database.default.database" "${DB_DATABASE}"
        update_or_add_config "backend/.env" "database.default.username" "${DB_USERNAME}"
        update_or_add_config "backend/.env" "database.default.password" "${DB_PASSWORD}"
        update_or_add_config "backend/.env" "database.default.port" "3306"
        update_or_add_config "backend/.env" "database.default.DBDriver" "MySQLi"
        update_or_add_config "backend/.env" "CI_ENVIRONMENT" "production"
        
        echo ""
        echo "Configuration updated:"
        echo "  - Hostname: ${DB_HOST}"
        echo "  - Database: ${DB_DATABASE}"
        echo "  - Username: ${DB_USERNAME}"
        echo "  - Port: 3306"
        echo "  - Environment: production"
        
        # Verify the configuration
        echo ""
        echo "Verifying configuration..."
        if grep -q "database.default.hostname = ${DB_HOST}" backend/.env; then
            echo "✓ Hostname configured correctly"
        else
            echo "✗ Warning: Hostname may not be set correctly"
        fi
        
        if grep -q "CI_ENVIRONMENT = production" backend/.env; then
            echo "✓ CI_ENVIRONMENT configured correctly"
        else
            echo "✗ Warning: CI_ENVIRONMENT may not be set correctly"
        fi
        
        # Step 1: Start database and shared services
        echo ""
        echo "Step 1: Starting database and services..."
        docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" up -d db phpmyadmin backend nginx
        
        # Wait for database
        echo "Waiting for database to be ready..."
        sleep 15
        
        # Restart backend to ensure .env changes are loaded
        echo ""
        echo "Restarting backend to load configuration..."
        docker compose -f "$COMPOSE_FILE" restart backend
        sleep 5
        
        # Install/Update Composer dependencies
        echo ""
        echo "Installing/Updating Composer dependencies..."
        if ! docker compose -f "$COMPOSE_FILE" exec backend composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev; then
            echo "⚠️  Warning: Composer install failed, attempting to continue..."
            echo "  The backend container will attempt to install dependencies on startup"
        else
            echo "✅ Composer dependencies installed successfully"
        fi
        
        # Force regenerate autoload to include newly added classes
        echo ""
        echo "Regenerating Composer autoload files..."
        if docker compose -f "$COMPOSE_FILE" exec backend composer dump-autoload --optimize --no-dev; then
            echo "✅ Autoload files regenerated successfully"
        else
            echo "⚠️  Warning: Autoload regeneration failed"
        fi
        
        # Restart backend again to reload autoload files
        echo ""
        echo "Restarting backend to reload autoload files..."
        docker compose -f "$COMPOSE_FILE" restart backend
        echo "Waiting for backend to be ready..."
        sleep 5
        
        # Fix writable permissions
        echo ""
        echo "Fixing writable permissions..."
        docker compose -f "$COMPOSE_FILE" exec backend chmod -R 777 /var/www/html/writable
        
        # Test database connection
        echo ""
        echo "Testing database connection..."
        if docker compose -f "$COMPOSE_FILE" exec backend php -r "
            try {
                \$mysqli = new mysqli('${DB_HOST}', '${DB_USERNAME}', '${DB_PASSWORD}', '${DB_DATABASE}', 3306);
                if (\$mysqli->connect_error) {
                    echo 'Connection failed: ' . \$mysqli->connect_error . PHP_EOL;
                    exit(1);
                }
                echo '✓ Database connection successful' . PHP_EOL;
                echo '  Server: ' . \$mysqli->host_info . PHP_EOL;
                \$mysqli->close();
                exit(0);
            } catch (Exception \$e) {
                echo 'Connection error: ' . \$e->getMessage() . PHP_EOL;
                exit(1);
            }
        "; then
            echo "Database is ready for migrations"
        else
            echo ""
            echo "✗ Database connection failed!"
            echo "Please check the following:"
            echo "  1. Database container is running: docker compose -f $COMPOSE_FILE ps db"
            echo "  2. Database credentials in .env.production"
            echo "  3. backend/.env configuration"
            echo ""
            echo "Current backend/.env database settings:"
            grep "^database.default" backend/.env
            echo ""
            exit 1
        fi
        
        # Step 2: Run database migrations
        echo ""
        echo "Step 2: Running database migrations..."
        if ! docker compose -f "$COMPOSE_FILE" exec backend php spark migrate; then
            echo ""
            echo "❌ Error: Database migration failed"
            echo "Deployment aborted. Please check migration errors and try again."
            echo ""
            echo "To view backend logs:"
            echo "  docker compose -f $COMPOSE_FILE logs backend"
            echo ""
            echo "To rollback .env changes:"
            echo "  cp backend/.env.bak backend/.env"
            exit 1
        fi
        echo "✅ Database migrations completed successfully"
        
        # Step 3: Build frontend to new color
        echo ""
        echo "Step 3: Building frontend to $NEW_COLOR..."
        build_frontend "$NEW_COLOR"
        
        # Step 4: Switch traffic
        echo ""
        echo "Step 4: Switching traffic to $NEW_COLOR..."
        switch_traffic "$NEW_COLOR"
        
        echo ""
        echo "============================================="
        echo "  Deployment Complete!"
        echo "============================================="
        echo "  Active frontend: $NEW_COLOR"
        echo "  Previous frontend ($CURRENT_COLOR) is still available"
        echo ""
        echo "  To rollback, run: ./scripts/prod.sh rollback"
        echo "============================================="
        ;;
        
    rollback)
        CURRENT_COLOR=$(get_active_color)
        if [ "$CURRENT_COLOR" == "blue" ]; then
            ROLLBACK_COLOR="green"
        else
            ROLLBACK_COLOR="blue"
        fi
        
        echo "Rolling back to $ROLLBACK_COLOR..."
        switch_traffic "$ROLLBACK_COLOR"
        
        echo ""
        echo "Rollback complete! Active frontend: $ROLLBACK_COLOR"
        ;;
        
    deploy-fresh)
        echo ""
        echo "🔄 Fresh deployment mode - rebuilding BOTH frontend colors"
        echo "   This ensures all caches are cleared and latest code is used"
        echo ""
        
        CURRENT_COLOR=$(get_active_color)
        if [ "$CURRENT_COLOR" == "blue" ]; then
            NEW_COLOR="green"
            OLD_COLOR="blue"
        else
            NEW_COLOR="blue"
            OLD_COLOR="green"
        fi
        
        echo "Current active: $CURRENT_COLOR"
        echo "Will switch to: $NEW_COLOR"
        echo "Will also rebuild: $OLD_COLOR (for future rollback)"
        echo ""
        
        # Same backend steps as normal deploy
        # ... (copy backend deployment steps from deploy case)
        
        # Function to update or add configuration line
        update_or_add_config() {
            local file=$1
            local key=$2
            local value=$3
            
            if grep -q "^${key} = " "$file" 2>/dev/null; then
                sed -i "s|^${key} = .*|${key} = ${value}|" "$file"
            else
                echo "${key} = ${value}" >> "$file"
            fi
        }
        
        # Step 0: Sync backend database configuration
        echo "Step 0: Syncing backend database configuration..."
        
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
        
        cp backend/.env backend/.env.bak
        echo "✓ Backed up backend/.env to backend/.env.bak"
        
        update_or_add_config "backend/.env" "database.default.hostname" "${DB_HOST}"
        update_or_add_config "backend/.env" "database.default.database" "${DB_DATABASE}"
        update_or_add_config "backend/.env" "database.default.username" "${DB_USERNAME}"
        update_or_add_config "backend/.env" "database.default.password" "${DB_PASSWORD}"
        update_or_add_config "backend/.env" "database.default.port" "3306"
        update_or_add_config "backend/.env" "database.default.DBDriver" "MySQLi"
        update_or_add_config "backend/.env" "CI_ENVIRONMENT" "production"
        
        echo ""
        echo "Configuration updated:"
        echo "  - Hostname: ${DB_HOST}"
        echo "  - Database: ${DB_DATABASE}"
        echo "  - Username: ${DB_USERNAME}"
        echo "  - Port: 3306"
        echo "  - Environment: production"
        
        # Step 1: Start services
        echo ""
        echo "Step 1: Starting database and services..."
        docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" up -d db phpmyadmin backend nginx
        sleep 15
        
        echo ""
        echo "Restarting backend to load configuration..."
        docker compose -f "$COMPOSE_FILE" restart backend
        sleep 5
        
        # Composer
        echo ""
        echo "Installing/Updating Composer dependencies..."
        docker compose -f "$COMPOSE_FILE" exec backend composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev || true
        
        echo ""
        echo "Regenerating Composer autoload files..."
        docker compose -f "$COMPOSE_FILE" exec backend composer dump-autoload --optimize --no-dev || true
        
        echo ""
        echo "Restarting backend to reload autoload files..."
        docker compose -f "$COMPOSE_FILE" restart backend
        sleep 5
        
        docker compose -f "$COMPOSE_FILE" exec backend chmod -R 777 /var/www/html/writable
        
        # Database
        echo ""
        echo "Step 2: Running database migrations..."
        docker compose -f "$COMPOSE_FILE" exec backend php spark migrate
        echo "✅ Database migrations completed"
        
        # Build BOTH frontends
        echo ""
        echo "Step 3: Building frontend to $NEW_COLOR (will be active)..."
        build_frontend "$NEW_COLOR"
        
        echo ""
        echo "Step 4: Also building frontend to $OLD_COLOR (for rollback)..."
        build_frontend "$OLD_COLOR"
        
        # Switch traffic
        echo ""
        echo "Step 5: Switching traffic to $NEW_COLOR..."
        switch_traffic "$NEW_COLOR"
        
        echo ""
        echo "============================================="
        echo "  Fresh Deployment Complete!"
        echo "============================================="
        echo "  Active frontend: $NEW_COLOR (latest code)"
        echo "  Standby frontend: $OLD_COLOR (also latest code)"
        echo ""
        echo "  Both colors are now on the latest version!"
        echo "  To rollback: ./scripts/prod.sh rollback"
        echo "============================================="
        ;;
        
    switch)
        if [ -z "$2" ]; then
            echo "Usage: ./scripts/prod.sh switch [blue|green]"
            exit 1
        fi
        switch_traffic "$2"
        ;;
        
    build)
        if [ -z "$2" ]; then
            echo "Usage: ./scripts/prod.sh build [blue|green]"
            exit 1
        fi
        build_frontend "$2"
        ;;
        
    status)
        CURRENT_COLOR=$(get_active_color)
        echo ""
        echo "Current active frontend: $CURRENT_COLOR"
        echo ""
        echo "Service status:"
        docker compose -f "$COMPOSE_FILE" ps
        ;;
        
    stop)
        echo "Stopping all production services..."
        docker compose -f "$COMPOSE_FILE" down
        echo "All services stopped."
        ;;
        
    logs)
        docker compose -f "$COMPOSE_FILE" logs -f "${2:-}"
        ;;
        
    migrate)
        echo "Running database migrations..."
        docker compose -f "$COMPOSE_FILE" exec backend php spark migrate
        echo "Migrations complete."
        ;;
        
    *)
        echo "Usage: ./scripts/prod.sh [command]"
        echo ""
        echo "Commands:"
        echo "  deploy        - Deploy with frontend blue-green strategy (default)"
        echo "  deploy-fresh  - Fresh deploy: rebuild BOTH colors with cache clearing"
        echo "  rollback      - Rollback to previous frontend version"
        echo "  switch        - Switch traffic to specific color (blue|green)"
        echo "  build         - Build frontend to specific color (blue|green)"
        echo "  status        - Show current deployment status"
        echo "  stop          - Stop all production services"
        echo "  logs          - View logs (optional: service name)"
        echo "  migrate       - Run database migrations"
        echo ""
        echo "Recommended for ensuring latest code:"
        echo "  ./scripts/prod.sh deploy-fresh"
        ;;
esac
