#!/bin/bash

# Laravel Deployment script: pull code, update deploy directory, restart nginx
# Usage: ./deploy.sh [git_repo_path] [deploy_path]

set -e  # Exit on any error

# Configuration - modify these as needed
GIT_REPO_PATH=${1:-"."}  # Default to current directory
DEPLOY_PATH=${2:-"/home/perfect_fit"}  # Laravel app root directory
PUBLIC_PATH="$DEPLOY_PATH/public"  # Nginx serve directory
BACKUP_SUFFIX=".backup.$(date +%Y%m%d_%H%M%S)"

echo "Starting Laravel deployment..."
echo "Git repo path: $GIT_REPO_PATH"
echo "Deploy path: $DEPLOY_PATH"
echo "Public path: $PUBLIC_PATH"

# Change to git repository directory
cd "$GIT_REPO_PATH"

# Pull latest changes
echo "Pulling latest code..."
git pull origin $(git branch --show-current)

# Install/update composer dependencies
if [ -f "composer.json" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-dev --optimize-autoloader
fi

# Install/update npm dependencies and build assets (uncomment if needed)
# if [ -f "package.json" ]; then
#     echo "Installing npm dependencies..."
#     npm ci
#     echo "Building assets..."
#     npm run production
# fi

# Run Laravel deployment commands
if [ -f "artisan" ]; then
    echo "Running Laravel deployment tasks..."
    
    # Clear and cache config
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    # Run migrations (uncomment if you want auto-migration)
    # php artisan migrate --force
    
    # Additional commands as needed
    # php artisan queue:restart
    # php artisan scout:flush
fi

# Create backup of current deployment
if [ -d "$DEPLOY_PATH" ]; then
    echo "Creating backup..."
    sudo cp -r "$DEPLOY_PATH" "${DEPLOY_PATH}${BACKUP_SUFFIX}"
fi

# Update deployment directory
echo "Updating deployment..."
sudo rsync -av --delete \
    --exclude='.git' \
    --exclude='deploy.sh' \
    --exclude='node_modules' \
    --exclude='.env' \
    --exclude='storage/app' \
    --exclude='storage/framework/cache' \
    --exclude='storage/framework/sessions' \
    --exclude='storage/framework/testing' \
    --exclude='storage/framework/views' \
    --exclude='storage/logs' \
    ./ "$DEPLOY_PATH/"

# Set proper Laravel permissions
echo "Setting Laravel permissions..."
sudo chown -R www-data:www-data "$DEPLOY_PATH"
sudo chown -R www-data:www-data "$DEPLOY_PATH/storage"
sudo chown -R www-data:www-data "$DEPLOY_PATH/bootstrap/cache"
sudo chmod -R 755 "$DEPLOY_PATH"
sudo chmod -R 775 "$DEPLOY_PATH/storage"
sudo chmod -R 775 "$DEPLOY_PATH/bootstrap/cache"

# Create Laravel storage symlinks if needed
if [ -f "$DEPLOY_PATH/artisan" ]; then
    echo "Creating storage symlinks..."
    sudo -u www-data php "$DEPLOY_PATH/artisan" storage:link
fi

# Restart PHP-FPM and nginx
echo "Restarting services..."
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx

# Verify services are running
if sudo systemctl is-active --quiet nginx && sudo systemctl is-active --quiet php8.3-fpm; then
    echo "✅ Laravel deployment completed successfully!"
    echo "Nginx and PHP-FPM are running and serving updated code"
else
    echo "❌ Error: Services failed to restart"
    exit 1
fi

# Clean up old backups (keep last 5)
echo "Cleaning up old backups..."
sudo find "$(dirname "$DEPLOY_PATH")" -name "$(basename "$DEPLOY_PATH").backup.*" -type d | sort -r | tail -n +6 | xargs -r sudo rm -rf

echo "Deployment finished at $(date)"