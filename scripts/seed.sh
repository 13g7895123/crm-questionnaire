#!/bin/bash
# ===========================================
# Database Seeder Script
# ===========================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR/.."

# Configuration
ENV_FILE=".env.production"
COMPOSE_FILE="docker-compose.prod.yml"
DEV_COMPOSE_FILE="docker-compose.yml"

echo "============================================="
echo "  CRM Questionnaire - Database Seeder"
echo "============================================="

# Detect environment
if [ -f "$ENV_FILE" ] && docker compose -f "$COMPOSE_FILE" ps backend &>/dev/null; then
    echo "Environment: Production"
    IS_PRODUCTION=true
    BACKEND_CONTAINER="backend"
else
    echo "Environment: Development"
    IS_PRODUCTION=false
    BACKEND_CONTAINER="backend"
fi

# Function to run seeder
run_seeder() {
    local seeder_name=$1
    echo ""
    echo "Running seeder: $seeder_name"
    
    if [ "$IS_PRODUCTION" = true ]; then
        docker compose -f "$COMPOSE_FILE" exec "$BACKEND_CONTAINER" php spark db:seed "$seeder_name"
    else
        docker compose -f "$DEV_COMPOSE_FILE" exec "$BACKEND_CONTAINER" php spark db:seed "$seeder_name"
    fi
    
    if [ $? -eq 0 ]; then
        echo "✓ $seeder_name completed successfully"
    else
        echo "✗ $seeder_name failed"
        return 1
    fi
}

# Main logic
case "${1:-all}" in
    all)
        echo ""
        echo "Running all seeders in sequence..."
        echo ""
        
        # Run seeders in order
        run_seeder "InitialDataSeeder"
        run_seeder "TemplateSectionsSeeder"
        run_seeder "CompleteSAQTemplateSeeder"
        
        echo ""
        echo "============================================="
        echo "  All seeders completed!"
        echo "============================================="
        ;;
        
    initial)
        run_seeder "InitialDataSeeder"
        ;;
        
    template)
        run_seeder "TemplateSectionsSeeder"
        ;;
        
    saq)
        run_seeder "CompleteSAQTemplateSeeder"
        ;;
        
    custom)
        if [ -z "$2" ]; then
            echo "Error: Please specify seeder name"
            echo "Usage: ./scripts/seed.sh custom SeederName"
            exit 1
        fi
        run_seeder "$2"
        ;;
        
    list)
        echo ""
        echo "Available seeders:"
        echo "  - InitialDataSeeder"
        echo "  - TemplateSectionsSeeder"
        echo "  - CompleteSAQTemplateSeeder"
        echo ""
        ;;
        
    *)
        echo "Usage: ./scripts/seed.sh [command]"
        echo ""
        echo "Commands:"
        echo "  all       - Run all seeders (default)"
        echo "  initial   - Run InitialDataSeeder only"
        echo "  template  - Run TemplateSectionsSeeder only"
        echo "  saq       - Run CompleteSAQTemplateSeeder only"
        echo "  custom    - Run custom seeder (e.g., ./scripts/seed.sh custom YourSeeder)"
        echo "  list      - Show available seeders"
        echo ""
        echo "Examples:"
        echo "  ./scripts/seed.sh              # Run all seeders"
        echo "  ./scripts/seed.sh initial      # Run initial data only"
        echo "  ./scripts/seed.sh custom TestSeeder  # Run custom seeder"
        ;;
esac
