#!/bin/bash
# ============================================
# 環境配置驗證腳本
# Environment Configuration Verification
# ============================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

echo "======================================"
echo "🔍 驗證環境配置"
echo "======================================"
echo ""

# 檢查必要檔案
echo "📁 檢查配置檔案..."
FILES=(
    ".env"
    ".env.development"
    ".env.production.example"
    "backend/app/Config/Cookie.php"
    "backend/app/Config/Cors.php"
    "frontend/nuxt.config.ts"
)

ALL_EXISTS=true
for file in "${FILES[@]}"; do
    if [ -f "$PROJECT_ROOT/$file" ]; then
        echo "  ✅ $file"
    else
        echo "  ❌ $file (不存在)"
        ALL_EXISTS=false
    fi
done

if [ "$ALL_EXISTS" = false ]; then
    echo ""
    echo "❌ 部分檔案不存在，請檢查配置"
    exit 1
fi

echo ""
echo "📋 當前 .env 配置:"
echo "------------------------------------"
if [ -f "$PROJECT_ROOT/.env" ]; then
    grep -E "^(APP_ENV|BACKEND_PORT|COOKIE_SECURE|COOKIE_HTTPONLY|COOKIE_DOMAIN|CORS_ALLOWED_ORIGINS)=" "$PROJECT_ROOT/.env" || echo "  (無相關配置)"
else
    echo "  ❌ .env 檔案不存在"
fi
echo "------------------------------------"

echo ""
echo "🔍 環境判斷:"
if grep -q "^APP_ENV=development" "$PROJECT_ROOT/.env" 2>/dev/null; then
    echo "  📌 目前使用: 🔧 開發環境 (Development)"
    echo ""
    echo "  預期配置:"
    echo "    - COOKIE_SECURE=false"
    echo "    - COOKIE_HTTPONLY=false"
    echo "    - COOKIE_DOMAIN=localhost"
    echo "    - CORS 允許 localhost 端口"
elif grep -q "^APP_ENV=production" "$PROJECT_ROOT/.env" 2>/dev/null; then
    echo "  📌 目前使用: 🔒 正式環境 (Production)"
    echo ""
    echo "  預期配置:"
    echo "    - COOKIE_SECURE=true"
    echo "    - COOKIE_HTTPONLY=true"
    echo "    - COOKIE_DOMAIN=crm.l"
    echo "    - CORS 僅允許 https://crm.l"
else
    echo "  ⚠️  未知環境或 APP_ENV 未設定"
fi

echo ""
echo "🐳 Docker 狀態:"
if command -v docker-compose &> /dev/null; then
    if docker-compose ps backend 2>/dev/null | grep -q "Up"; then
        echo "  ✅ 後端容器運行中"
    else
        echo "  ⚠️  後端容器未運行"
    fi
else
    echo "  ⚠️  docker-compose 未安裝"
fi

echo ""
echo "✅ 驗證完成！"
echo ""
echo "📚 詳細說明請參考: docs/DEVELOPMENT_SETUP.md"
echo ""
