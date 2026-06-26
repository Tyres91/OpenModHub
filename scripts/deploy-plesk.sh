#!/usr/bin/env bash
# OpenModHub Plesk deployment helper.
#
# Usage:
#   1. Edit APP_DIR below to match your Plesk subscription path.
#   2. Upload this script to the server (e.g. ~/deploy-plesk.sh).
#   3. Run:  bash deploy-plesk.sh
#
# This script is idempotent for steps that have no work to do
# (e.g. `storage:link` is skipped if the symlink already exists).

set -euo pipefail

# --- Configuration ----------------------------------------------------------

# Absolute path to the OpenModHub installation on the Plesk server.
# Replace <sub> with the Plesk subscription identifier.
APP_DIR="${OPENMODHUB_DIR:-/var/www/vhosts/<sub>/openmodhub}"

# Git branch to deploy. Leave empty to keep the current branch.
BRANCH="${OPENMODHUB_BRANCH:-main}"

# Skip `composer install` for tiny front-end-only updates.
# Set to "1" to skip; default "0" runs composer install.
SKIP_COMPOSER="${OPENMODHUB_SKIP_COMPOSER:-0}"

# Skip `npm run build`. On Plesk itself, Node.js is not always available.
# The production build is usually produced on a build host and uploaded
# with the release. Default "1" skips the build.
SKIP_NPM="${OPENMODHUB_SKIP_NPM:-1}"

# --- Pre-flight -------------------------------------------------------------

if [ ! -d "$APP_DIR" ]; then
    echo "Error: $APP_DIR does not exist."
    echo "Set OPENMODHUB_DIR to the correct path or edit this script."
    exit 1
fi

cd "$APP_DIR"

if [ ! -f ".env" ]; then
    echo "Error: .env not found in $APP_DIR."
    echo "Copy .env.plesk.example to .env and fill in your values first."
    exit 1
fi

# --- Pull latest source -----------------------------------------------------

if [ -d ".git" ]; then
    if [ -n "$BRANCH" ]; then
        echo "==> Checking out branch $BRANCH"
        git fetch origin
        git checkout "$BRANCH"
        git pull --ff-only origin "$BRANCH"
    else
        echo "==> Pulling current branch"
        git pull --ff-only
    fi
else
    echo "Skipping git pull: $APP_DIR is not a git repository."
fi

# --- Composer ---------------------------------------------------------------

if [ "$SKIP_COMPOSER" != "1" ]; then
    echo "==> Installing PHP dependencies (no dev)"
    composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --optimize-autoloader
else
    echo "Skipping composer install (OPENMODHUB_SKIP_COMPOSER=1)"
fi

# --- Frontend build ---------------------------------------------------------

if [ "$SKIP_NPM" != "1" ]; then
    if [ -f "package.json" ]; then
        echo "==> Building frontend assets"
        npm ci
        npm run build
    else
        echo "Skipping npm build: package.json not found"
    fi
else
    echo "Skipping npm build (OPENMODHUB_SKIP_NPM=1). Make sure public/build/ is up to date."
fi

# --- Storage symlink --------------------------------------------------------

# Remove a broken symlink left over from a Docker-based build (the repo's
# public/storage previously pointed at /var/www/html/...). Re-create it on
# the Plesk path so the public disk works.
if [ -L "public/storage" ] && [ ! -d "public/storage" ]; then
    echo "==> Removing broken public/storage symlink"
    rm -f public/storage
fi

echo "==> Linking public/storage -> storage/app/public"
php artisan storage:link 2>/dev/null || true

# --- Migrations -------------------------------------------------------------

echo "==> Running database migrations"
php artisan migrate --force

# --- Cache rebuild ----------------------------------------------------------

echo "==> Clearing and rebuilding caches"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# --- Queue ------------------------------------------------------------------

# Restart the queue workers so the new code is picked up on the next
# cron-triggered `queue:work` run.
if php artisan list 2>/dev/null | grep -q "queue:restart"; then
    echo "==> Restarting queue workers"
    php artisan queue:restart || true
fi

echo "==> Deployment finished successfully"
