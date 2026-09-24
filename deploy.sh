#!/bin/bash
# SmartHost Panel Deployment Script

set -e

echo "🚀 Starting deployment..."

# Variables
APP_DIR="/var/www/smarthost-panel"
RELEASE_DIR="/var/www/releases"
CURRENT_DIR="/var/www/current"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
NEW_RELEASE="$RELEASE_DIR/$TIMESTAMP"

echo "📁 Creating release directory: $NEW_RELEASE"
mkdir -p $NEW_RELEASE

echo "📦 Cloning repository..."
git clone git@github.com:your-org/smarthost-panel.git $NEW_RELEASE

cd $NEW_RELEASE

echo "📦 Installing composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "📦 Installing npm dependencies and building assets..."
npm install && npm run build

echo "🔧 Configuring environment..."
cp .env.production .env
php artisan key:generate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "🗄️ Running migrations..."
php artisan migrate --force

echo "🔗 Linking storage..."
php artisan storage:link

echo "🔄 Updating symbolic link..."
rm -rf $CURRENT_DIR
ln -s $NEW_RELEASE $CURRENT_DIR

echo "🔄 Reloading PHP-FPM..."
sudo systemctl reload php8.3-fpm 2>/dev/null || sudo systemctl reload php8.2-fpm 2>/dev/null || true

echo "🔄 Reloading Nginx..."
sudo systemctl reload nginx

echo "🔄 Restarting Horizon..."
cd $CURRENT_DIR
php artisan horizon:terminate 2>/dev/null || true

echo "📊 Updating permissions..."
sudo chown -R www-data:www-data $NEW_RELEASE
sudo chmod -R 755 $NEW_RELEASE/storage
sudo chmod -R 755 $NEW_RELEASE/bootstrap/cache

echo "🧹 Cleaning old releases..."
cd $RELEASE_DIR
ls -t | tail -n +6 | xargs -r rm -rf 2>/dev/null || true

echo "✅ Deployment completed successfully!"
echo "🔗 Application is now live at: $CURRENT_DIR"
