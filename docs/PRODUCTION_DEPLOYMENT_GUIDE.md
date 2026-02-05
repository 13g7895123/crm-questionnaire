# 生产环境部署指南

## 🚀 快速开始

### 确保使用最新代码（推荐）

```bash
# VPS 上执行
cd /path/to/crm-questionnaire
git pull
./scripts/prod.sh deploy-fresh
```

**`deploy-fresh` 会做什么？**
- ✅ 清除所有 Nuxt 构建缓存（.nuxt, .output）
- ✅ 清除 node_modules 强制重新安装
- ✅ 重新生成 Composer autoload
- ✅ 执行数据库迁移
- ✅ 同时构建 Blue 和 Green 两个前端（确保都是最新）
- ✅ 切换到新版本

## 📋 部署命令对比

| 命令 | 用途 | 前端构建 | 是否清缓存 |
|------|------|---------|-----------|
| `./scripts/prod.sh deploy` | 普通部署 | 只构建一个颜色 | ❌ 不清缓存 |
| `./scripts/prod.sh deploy-fresh` | **完整部署** | **构建两个颜色** | **✅ 清除所有缓存** |
| `./scripts/prod.sh rollback` | 回退 | 不构建 | - |

## 🔍 诊断问题

### 检查系统状态

```bash
./scripts/check-rm-status.sh
```

会检查：
- Git 代码状态
- RM 文件是否存在
- Autoload 是否最新
- 数据库表是否创建
- 前端缓存状态

### 前端显示旧代码？

```bash
# 方案1：清理前端缓存后重新部署
./scripts/clean-frontend-cache.sh
./scripts/prod.sh deploy-fresh

# 方案2：手动重建两个颜色
./scripts/prod.sh build blue
./scripts/prod.sh build green
./scripts/prod.sh switch blue  # 或 green
```

### 后端找不到 RM 类？

```bash
# 重新生成 autoload 并重启
docker compose -f docker-compose.prod.yml exec backend composer dump-autoload --optimize --no-dev
docker compose -f docker-compose.prod.yml restart backend
```

## 🎯 最佳实践

### 每次 pull 代码后

```bash
git pull
./scripts/prod.sh deploy-fresh  # 推荐！确保没有缓存问题
```

### 只更新了前端代码

```bash
# 只重建前端，不影响后端
./scripts/prod.sh build green  # 或 blue
./scripts/prod.sh switch green
```

### 只更新了后端代码

```bash
# 重新生成 autoload
docker compose -f docker-compose.prod.yml exec backend composer dump-autoload --optimize --no-dev
docker compose -f docker-compose.prod.yml restart backend

# 如果有新的 migration
docker compose -f docker-compose.prod.yml exec backend php spark migrate
```

## ⚡ 常用命令

```bash
# 查看状态
./scripts/prod.sh status

# 查看日志
./scripts/prod.sh logs backend
./scripts/prod.sh logs nginx

# 执行数据库迁移
./scripts/prod.sh migrate

# 停止所有服务
./scripts/prod.sh stop
```

## 🔧 故障排除

### 问题：RM 功能 404

**原因：**
- Autoload 缓存未更新
- Backend 容器未重启

**解决：**
```bash
docker compose -f docker-compose.prod.yml exec backend composer dump-autoload --optimize --no-dev
docker compose -f docker-compose.prod.yml restart backend
./scripts/check-rm-status.sh  # 验证修复
```

### 问题：前端显示旧页面

**原因：**
- Nuxt 构建缓存
- Docker volume 缓存
- 浏览器缓存

**解决：**
```bash
# 服务端修复
./scripts/clean-frontend-cache.sh
./scripts/prod.sh deploy-fresh

# 客户端修复
# 浏览器按 Ctrl+Shift+R 强制刷新
# 或清除浏览器缓存
```

### 问题：数据库迁移失败

```bash
# 查看当前 migration 状态
docker compose -f docker-compose.prod.yml exec backend php spark migrate:status

# 查看后端日志
docker compose -f docker-compose.prod.yml logs backend

# 检查数据库连接
docker compose -f docker-compose.prod.yml exec backend php -r "
  \$db = \Config\Database::connect();
  var_dump(\$db->getDatabase());
"
```

## 📊 部署流程图

```
git pull
   ↓
deploy-fresh
   ↓
├─ Backend
│  ├─ 更新 .env
│  ├─ 安装 Composer 依赖
│  ├─ 重新生成 autoload ✨
│  ├─ 重启容器 ✨
│  └─ 执行 migration
│
└─ Frontend
   ├─ 清除 .nuxt/.output/node_modules ✨
   ├─ 构建 Blue ✨
   ├─ 构建 Green ✨
   └─ 切换流量
```

✨ = 确保使用最新代码的关键步骤

## 🎓 进阶使用

### Blue-Green 部署流程

```bash
# 当前 active: blue

# 1. 只构建 green（新版本）
./scripts/prod.sh build green

# 2. 切换到 green
./scripts/prod.sh switch green

# 3. 测试 OK，blue 闲置可用于回退

# 4. 如果有问题，立即回退
./scripts/prod.sh rollback  # 切回 blue
```

### 预发布测试

```bash
# 构建新版本但不切换
./scripts/prod.sh build green

# 手动测试 green（需要修改 nginx 配置或使用端口）
# 测试 OK 后再切换
./scripts/prod.sh switch green
```
