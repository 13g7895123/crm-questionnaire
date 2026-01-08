#!/bin/bash
# ============================================
# 切換到開發環境
# Switch to Development Environment
# ============================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

echo "======================================"
echo "🔧 切換到開發環境"
echo "======================================"

# 複製開發環境配置
if [ -f "$PROJECT_ROOT/.env.development" ]; then
    cp "$PROJECT_ROOT/.env.development" "$PROJECT_ROOT/.env"
    echo "✅ 已複製 .env.development → .env"
else
    echo "❌ 錯誤: .env.development 檔案不存在"
    exit 1
fi

# 顯示當前配置
echo ""
echo "📋 當前開發環境配置:"
echo "------------------------------------"
grep -E "^(APP_ENV|COOKIE_SECURE|COOKIE_HTTPONLY|COOKIE_DOMAIN|CORS_ALLOWED_ORIGINS)=" "$PROJECT_ROOT/.env" || true
echo "------------------------------------"

echo ""
echo "✅ 已成功切換到開發環境！"
echo ""
echo "🚀 下一步操作:"
echo "  1. 啟動後端: docker-compose up -d"
echo "  2. 啟動前端: cd frontend && npm run dev"
echo ""
