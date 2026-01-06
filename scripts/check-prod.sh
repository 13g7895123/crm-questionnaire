#!/bin/bash
# ===========================================
# Production Environment Pre-flight Check
# ===========================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR/.."

echo "============================================="
echo "  Production Environment Pre-flight Check"
echo "============================================="
echo ""

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

ERRORS=0
WARNINGS=0

# Function to check file existence
check_file() {
    local file=$1
    local description=$2
    
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓${NC} $description: $file"
        return 0
    else
        echo -e "${RED}✗${NC} $description not found: $file"
        ((ERRORS++))
        return 1
    fi
}

# Function to check directory existence
check_dir() {
    local dir=$1
    local description=$2
    
    if [ -d "$dir" ]; then
        echo -e "${GREEN}✓${NC} $description exists: $dir"
        return 0
    else
        echo -e "${RED}✗${NC} $description not found: $dir"
        ((ERRORS++))
        return 1
    fi
}

# Function to check environment variable
check_env_var() {
    local var_name=$1
    local env_file=$2
    
    if grep -q "^${var_name}=" "$env_file"; then
        local value=$(grep "^${var_name}=" "$env_file" | cut -d'=' -f2)
        echo -e "${GREEN}✓${NC} $var_name is set: $value"
        return 0
    else
        echo -e "${YELLOW}⚠${NC} $var_name not found in $env_file"
        ((WARNINGS++))
        return 1
    fi
}

echo "1. Checking required files..."
echo "---------------------------------------------"
check_file ".env.production" "Production environment file"
check_file "docker-compose.prod.yml" "Production docker compose file"
check_file "scripts/prod.sh" "Production deployment script"
check_file "backend/.env" "Backend environment file"
check_file "docker/nginx/entrypoint.sh" "Nginx entrypoint script"
check_file "docker/backend/Dockerfile" "Backend Dockerfile"
check_file "docker/backend/start.sh" "Backend start script"
echo ""

echo "2. Checking required directories..."
echo "---------------------------------------------"
check_dir "frontend" "Frontend directory"
check_dir "backend" "Backend directory"
check_dir "backend/app" "Backend app directory"
check_dir "backend/writable" "Backend writable directory"
check_dir "docker" "Docker directory"
echo ""

echo "3. Checking .env.production configuration..."
echo "---------------------------------------------"
if [ -f ".env.production" ]; then
    check_env_var "PROD_PORT" ".env.production"
    check_env_var "DB_HOST" ".env.production"
    check_env_var "DB_DATABASE" ".env.production"
    check_env_var "DB_USERNAME" ".env.production"
    check_env_var "DB_PASSWORD" ".env.production"
    check_env_var "DB_ROOT_PASSWORD" ".env.production"
    
    # Check if ACTIVE_FRONTEND exists
    if ! check_env_var "ACTIVE_FRONTEND" ".env.production"; then
        echo -e "${YELLOW}⚠${NC} ACTIVE_FRONTEND not set, will default to 'blue'"
    fi
    
    # Check for default passwords
    if grep -q "change_me" ".env.production"; then
        echo -e "${YELLOW}⚠${NC} Warning: Default passwords detected in .env.production"
        echo "  Please update DB_ROOT_PASSWORD and DB_PASSWORD before deploying to production!"
        ((WARNINGS++))
    fi
fi
echo ""

echo "4. Checking backend/.env configuration..."
echo "---------------------------------------------"
if [ -f "backend/.env" ]; then
    # Check database port
    if grep -q "database.default.port = 3306" backend/.env; then
        echo -e "${GREEN}✓${NC} Database port correctly set to 3306"
    elif grep -q "database.default.port" backend/.env; then
        port=$(grep "database.default.port" backend/.env | awk '{print $NF}')
        echo -e "${YELLOW}⚠${NC} Database port is set to $port (should be 3306 in Docker network)"
        ((WARNINGS++))
    fi
    
    # Check CI_ENVIRONMENT
    if grep -q "CI_ENVIRONMENT = production" backend/.env; then
        echo -e "${GREEN}✓${NC} CI_ENVIRONMENT set to production"
    else
        current_env=$(grep "CI_ENVIRONMENT" backend/.env | awk '{print $NF}')
        echo -e "${YELLOW}⚠${NC} CI_ENVIRONMENT is '$current_env' (will be updated to production during deployment)"
    fi
fi
echo ""

echo "5. Checking Docker environment..."
echo "---------------------------------------------"
if command -v docker &> /dev/null; then
    echo -e "${GREEN}✓${NC} Docker is installed"
    docker --version
    
    if docker compose version &> /dev/null; then
        echo -e "${GREEN}✓${NC} Docker Compose is available"
        docker compose version
    else
        echo -e "${RED}✗${NC} Docker Compose not available"
        ((ERRORS++))
    fi
else
    echo -e "${RED}✗${NC} Docker is not installed"
    ((ERRORS++))
fi
echo ""

echo "6. Checking script permissions..."
echo "---------------------------------------------"
if [ -x "scripts/prod.sh" ]; then
    echo -e "${GREEN}✓${NC} prod.sh is executable"
else
    echo -e "${YELLOW}⚠${NC} prod.sh is not executable (fixing...)"
    chmod +x scripts/prod.sh
    echo -e "${GREEN}✓${NC} prod.sh is now executable"
fi

if [ -f "scripts/seed.sh" ]; then
    if [ -x "scripts/seed.sh" ]; then
        echo -e "${GREEN}✓${NC} seed.sh is executable"
    else
        echo -e "${YELLOW}⚠${NC} seed.sh is not executable (fixing...)"
        chmod +x scripts/seed.sh
        echo -e "${GREEN}✓${NC} seed.sh is now executable"
    fi
fi
echo ""

echo "7. Checking frontend dependencies..."
echo "---------------------------------------------"
if [ -f "frontend/package.json" ]; then
    echo -e "${GREEN}✓${NC} Frontend package.json exists"
    
    if [ -d "frontend/node_modules" ]; then
        echo -e "${GREEN}✓${NC} Frontend node_modules exists"
    else
        echo -e "${YELLOW}⚠${NC} Frontend node_modules not found"
        echo "  This is OK - dependencies will be installed during build"
    fi
else
    echo -e "${RED}✗${NC} Frontend package.json not found"
    ((ERRORS++))
fi
echo ""

echo "8. Checking backend dependencies..."
echo "---------------------------------------------"
if [ -f "backend/composer.json" ]; then
    echo -e "${GREEN}✓${NC} Backend composer.json exists"
    
    if [ -d "backend/vendor" ]; then
        echo -e "${GREEN}✓${NC} Backend vendor directory exists"
    else
        echo -e "${YELLOW}⚠${NC} Backend vendor directory not found"
        echo "  Dependencies will be installed on first container start"
    fi
else
    echo -e "${RED}✗${NC} Backend composer.json not found"
    ((ERRORS++))
fi
echo ""

echo "============================================="
echo "  Check Summary"
echo "============================================="
if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}All checks passed!${NC}"
    echo "Your production environment is ready to deploy."
    echo ""
    echo "To deploy, run:"
    echo "  ./scripts/prod.sh deploy"
    exit 0
elif [ $ERRORS -eq 0 ]; then
    echo -e "${YELLOW}Checks passed with $WARNINGS warning(s)${NC}"
    echo "You can proceed with deployment, but please review the warnings above."
    echo ""
    echo "To deploy, run:"
    echo "  ./scripts/prod.sh deploy"
    exit 0
else
    echo -e "${RED}Found $ERRORS error(s) and $WARNINGS warning(s)${NC}"
    echo "Please fix the errors above before deploying to production."
    exit 1
fi
