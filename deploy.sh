#!/bin/bash
# DeepTouch ISP-ERP Automated Deployment Script
# Repository: https://github.com/deeptouchit/deeptouchit-isp-erp.git

set -e

APP_DIR="${DEPLOY_APP_DIR:-/var/www/isp-erp}"
BRANCH="${DEPLOY_BRANCH:-main}"

echo "================================================="
echo "🚀 Starting DeepTouch ISP-ERP Deployment: $(date)"
echo "📁 Application Directory: $APP_DIR"
echo "🌿 Branch: $BRANCH"
echo "================================================="

if [ ! -d "$APP_DIR" ]; then
    echo "❌ Error: Application directory $APP_DIR does not exist!"
    exit 1
fi

cd "$APP_DIR"

# 1. Pull latest changes
echo "📦 Pulling latest commits from GitHub origin/$BRANCH..."
git fetch --all --prune
git checkout -f "$BRANCH"
git reset --hard "origin/$BRANCH"

# 2. Install PHP dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# 3. Build frontend
echo "⚡ Installing NPM packages & building Vite assets..."
if command -v npm &> /dev/null; then
    npm install --no-audit --no-fund
    npm run build
fi

# 4. Run migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# 5. Optimize caches
echo "🧹 Optimizing Laravel caches..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. Storage link
echo "🔗 Ensuring storage link exists..."
php artisan storage:link 2>/dev/null || true

# 7. Update file permissions
echo "🔒 Updating directory permissions..."
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" 2>/dev/null || true
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" 2>/dev/null || true

# 8. Reload web services
echo "🔄 Reloading web services..."
systemctl reload php8.3-fpm 2>/dev/null || systemctl reload php8.2-fpm 2>/dev/null || true
systemctl reload nginx 2>/dev/null || true

# 9. Restart queue workers
php artisan queue:restart 2>/dev/null || true

echo "================================================="
echo "✅ DeepTouch ISP-ERP deployment completed successfully at $(date)"
echo "================================================="
