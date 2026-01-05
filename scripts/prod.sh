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
    
    docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" --profile "build-$target_color" run --rm "frontend-builder-$target_color"
    
    echo "Frontend built to $target_color!"
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
        
        # Step 1: Start database and shared services
        echo "Step 1: Starting database and services..."
        docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" up -d db phpmyadmin backend nginx
        
        # Wait for database
        echo "Waiting for database to be ready..."
        sleep 15
        
        # Fix writable permissions
        echo ""
        echo "Fixing writable permissions..."
        docker compose -f "$COMPOSE_FILE" exec backend chmod -R 777 /var/www/html/writable
        
        # Step 2: Run database migrations
        echo ""
        echo "Step 2: Running database migrations..."
        docker compose -f "$COMPOSE_FILE" exec backend php spark migrate
        
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
        echo "  deploy    - Deploy with frontend blue-green strategy (default)"
        echo "  rollback  - Rollback to previous frontend version"
        echo "  switch    - Switch traffic to specific color (blue|green)"
        echo "  build     - Build frontend to specific color (blue|green)"
        echo "  status    - Show current deployment status"
        echo "  stop      - Stop all production services"
        echo "  logs      - View logs (optional: service name)"
        echo "  migrate   - Run database migrations"
        ;;
esac
