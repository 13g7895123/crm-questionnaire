#!/bin/bash
# ===========================================
# Production Environment Deployment Script
# Blue-Green Deployment Strategy
# ===========================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Configuration
NGINX_CONF="./docker/nginx/conf.d/default.conf"
ENV_FILE=".env.production"
COMPOSE_FILE="docker-compose.prod.yml"

echo "============================================="
echo "  CRM Questionnaire - Production Deployment"
echo "  Blue-Green Deployment Strategy"
echo "============================================="

# Check if .env.production file exists
if [ ! -f "$ENV_FILE" ]; then
    echo "Error: $ENV_FILE file not found!"
    exit 1
fi

# Load environment variables
export $(grep -v '^#' "$ENV_FILE" | xargs)

# Function to get current active color from nginx config
get_active_color() {
    if grep -q "default backend_blue" "$NGINX_CONF"; then
        echo "blue"
    else
        echo "green"
    fi
}

# Function to switch traffic
switch_traffic() {
    local target_color=$1
    echo "Switching traffic to $target_color..."
    
    if [ "$target_color" == "blue" ]; then
        sed -i 's/default backend_green/default backend_blue/g' "$NGINX_CONF"
    else
        sed -i 's/default backend_blue/default backend_green/g' "$NGINX_CONF"
    fi
    
    # Reload nginx
    docker compose -f "$COMPOSE_FILE" exec nginx nginx -s reload
    echo "Traffic switched to $target_color"
}

# Function to deploy to a specific color
deploy_to_color() {
    local target_color=$1
    echo ""
    echo "Deploying to $target_color environment..."
    
    # Build and start the target backend
    docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" up -d --build "backend-$target_color"
    
    # Wait for the service to be healthy
    echo "Waiting for backend-$target_color to be ready..."
    sleep 10
    
    # Health check
    local max_retries=30
    local retry=0
    while [ $retry -lt $max_retries ]; do
        if docker compose -f "$COMPOSE_FILE" exec "backend-$target_color" php spark list > /dev/null 2>&1; then
            echo "backend-$target_color is ready!"
            
            # Run database migrations
            echo "Running database migrations..."
            docker compose -f "$COMPOSE_FILE" exec "backend-$target_color" php spark migrate
            
            return 0
        fi
        retry=$((retry + 1))
        echo "Waiting... ($retry/$max_retries)"
        sleep 2
    done
    
    echo "Warning: Health check timed out, but continuing..."
    return 0
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
        echo "Current active: $CURRENT_COLOR"
        echo "Deploying to:   $NEW_COLOR"
        echo ""
        
        # Step 1: Build frontend
        echo "Step 1: Building frontend..."
        docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" --profile build run --rm frontend-builder
        
        # Step 2: Start database and shared services
        echo ""
        echo "Step 2: Starting database and shared services..."
        docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" up -d db phpmyadmin nginx
        
        # Wait for database
        echo "Waiting for database to be ready..."
        sleep 15
        
        # Step 3: Deploy to new color
        echo ""
        echo "Step 3: Deploying backend to $NEW_COLOR..."
        deploy_to_color "$NEW_COLOR"
        
        # Step 4: Switch traffic
        echo ""
        echo "Step 4: Switching traffic to $NEW_COLOR..."
        switch_traffic "$NEW_COLOR"
        
        echo ""
        echo "============================================="
        echo "  Deployment Complete!"
        echo "============================================="
        echo "  Active backend: $NEW_COLOR"
        echo "  Previous backend ($CURRENT_COLOR) is still running"
        echo ""
        echo "  To rollback, run: ./prod.sh rollback"
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
        echo "Rollback complete! Active backend: $ROLLBACK_COLOR"
        ;;
        
    switch)
        if [ -z "$2" ]; then
            echo "Usage: ./prod.sh switch [blue|green]"
            exit 1
        fi
        switch_traffic "$2"
        ;;
        
    status)
        CURRENT_COLOR=$(get_active_color)
        echo ""
        echo "Current active backend: $CURRENT_COLOR"
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
        
    *)
        echo "Usage: ./prod.sh [command]"
        echo ""
        echo "Commands:"
        echo "  deploy    - Deploy with blue-green strategy (default)"
        echo "  rollback  - Rollback to previous version"
        echo "  switch    - Switch traffic to specific color (blue|green)"
        echo "  status    - Show current deployment status"
        echo "  stop      - Stop all production services"
        echo "  logs      - View logs (optional: service name)"
        ;;
esac
