#!/bin/bash
# ============================================
# 切換到正式環境
# Switch to Production Environment
# ============================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

echo "======================================"
echo "🔒 切換到正式環境"
echo "======================================"

# 檢查是否存在正式環境配置
if [ ! -f "$PROJECT_ROOT/.env.production" ]; then
    echo "⚠️  警告: .env.production 檔案不存在"
    echo "📝 請先根據 .env.production.example 創建 .env.production"
    echo ""
    read -p "是否要複製 .env.production.example 為 .env.production？(y/N) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        cp "$PROJECT_ROOT/.env.production.example" "$PROJECT_ROOT/.env.production"
        echo "✅ 已創建 .env.production"
        echo "⚠️  請編輯 .env.production 並更新敏感資訊（如資料庫密碼）"
        exit 0
    else
        echo "❌ 取消切換"
        exit 1
    fi
fi

# 複製正式環境配置
cp "$PROJECT_ROOT/.env.production" "$PROJECT_ROOT/.env"
echo "✅ 已複製 .env.production → .env"

# 顯示當前配置（隱藏敏感資訊）
echo ""
echo "📋 當前正式環境配置:"
echo "------------------------------------"
grep -E "^(APP_ENV|COOKIE_SECURE|COOKIE_HTTPONLY|COOKIE_DOMAIN|CORS_ALLOWED_ORIGINS)=" "$PROJECT_ROOT/.env" || true
echo "------------------------------------"

echo ""
echo "✅ 已成功切換到正式環境！"
echo ""
echo "⚠️  警告: 正式環境配置已啟用，請確認:"
echo "  - HTTPS 已正確配置"
echo "  - SSL 憑證有效"
echo "  - 資料庫密碼已更新"
echo ""
echo "🚀 下一步操作:"
echo "  執行部署腳本: ./scripts/prod.sh"
echo ""
