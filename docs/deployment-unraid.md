# Local Unraid Deployment

This guide describes a local Unraid deployment that builds the OpenModHub image directly on the Unraid host and manages the app through an Unraid Docker template.

## Scope

The provided template manages only the OpenModHub application container.

It does not create or manage MariaDB. Use the existing MariaDB container named `openmodhub-db` and provide its database name, username, and password in the Unraid template.

The local template uses `QUEUE_CONNECTION=sync` by default, so no separate queue worker container is required for the first local deployment. If the queue connection is changed to `database`, a queue worker must be added later or jobs will remain unprocessed.

## Files

- `Dockerfile.unraid`: production-oriented local image build for Unraid.
- `docker/entrypoint-unraid.sh`: container startup script.
- `docker/unraid/templates/openmodhub-app.xml`: Unraid Docker template for the app container.
- `.env.unraid.example`: reference environment values.

## Persistent Paths

Create these folders on Unraid if they do not exist:

```bash
mkdir -p /mnt/user/appdata/openmodhub/storage
mkdir -p /mnt/user/appdata/openmodhub/source
```

The template maps:

```text
/mnt/user/appdata/openmodhub/storage -> /var/www/html/storage
```

The project source code should be placed in:

```text
/mnt/user/appdata/openmodhub/source
```

Do not place MariaDB data in this OpenModHub folder when MariaDB is managed by an external container.

## Quick Start (Option A: Local Build On Unraid)

This guide covers **Option A**: building the Docker image directly on Unraid from a local copy of the project source.

### Step 1: Copy Project To Unraid

Clone or copy the project to the Unraid host:

```bash
mkdir -p /mnt/user/appdata/openmodhub/source
cd /mnt/user/appdata/openmodhub/source
git clone <your-repo-url> .
# Or copy files via rsync, scp, etc.
```

### Step 2: Build The Local Image

From the project source folder on Unraid:

```bash
cd /mnt/user/appdata/openmodhub/source
docker build -f Dockerfile.unraid -t openmodhub:local .
```

### Step 3: Generate APP_KEY

Generate a persistent Laravel app key once:

```bash
docker run --rm openmodhub:local php artisan key:generate --show
```

Copy the complete `base64:...` value. You will need it in the Unraid template.

### Step 4: Install The Unraid Template

Copy the template XML to Unraid's user template folder:

```bash
cp /mnt/user/appdata/openmodhub/source/docker/unraid/templates/openmodhub-app.xml /boot/config/plugins/dockerMan/templates-user/my-openmodhub-app.xml
```

### Step 5: Create Container In Unraid UI

1. Open `Docker` in the Unraid web UI.
2. Select `Add Container`.
3. Choose the `openmodhub-app` template.
4. Fill in the required values below.
5. Apply the template.

### Step 6: Verify Deployment

After the container starts:

1. Open `http://UNRAID-IP:8088` in your browser.
2. The app should show the public mod listing.
3. Create an admin account via the registration form or seed users if available.

## Required Template Values

Set these values in the Unraid template before starting the app:

```text
Repository: openmodhub:local
WebUI Port: 8088 (or any free port)
Application Storage: /mnt/user/appdata/openmodhub/storage

APP_NAME: OpenModHub
APP_ENV: production
APP_DEBUG: false
APP_URL: http://UNRAID-IP:8088
APP_KEY: base64:...  (from Step 3 above)

DB_CONNECTION: mysql
DB_HOST: openmodhub-db
DB_PORT: 3306
DB_DATABASE: openmodhub
DB_USERNAME: your MariaDB user
DB_PASSWORD: your MariaDB password

SESSION_DRIVER: database
CACHE_STORE: database
QUEUE_CONNECTION: sync
MAIL_MAILER: log
```

## Docker Network

For `DB_HOST=openmodhub-db` to work reliably, the OpenModHub app container and the existing MariaDB container must share a Docker network where container-name DNS resolution works.

If the app cannot resolve `openmodhub-db`, either:

- move both containers to the same custom Docker network, or
- set `DB_HOST` to the MariaDB container IP or another reachable host/IP.

## Startup Behavior

On normal app startup, the entrypoint runs:

```bash
php artisan storage:link
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan serve --host=0.0.0.0 --port=8000
```

The startup script retries migrations while MariaDB is still starting.

It never runs `migrate:fresh` and never seeds the database automatically. Existing production data must not be deleted by deployment automation.

## Updating The App

When you have code updates (e.g., after a git pull):

1. Update the source on Unraid:
   ```bash
   cd /mnt/user/appdata/openmodhub/source
   git pull
   ```

2. Rebuild the image:
   ```bash
   docker build -f Dockerfile.unraid -t openmodhub:local .
   ```

3. In the Unraid Docker UI, force update the `openmodhub-app` container (or stop and start it).

The container will run pending migrations with `php artisan migrate --force` during startup.

## Troubleshooting

### Mixed Content Errors / Vite Dev Server References

If you see errors like:
```
Mixed Content: The page at 'https://...' was loaded over HTTPS, but requested an insecure script 'http://0.0.0.0:5173/...'
```

This means the `public/hot` file from local development was copied into the image. The Dockerfile automatically removes this file during build. If you still see this error:

1. Ensure you're using the updated `Dockerfile.unraid` (after May 15, 2026)
2. Manually remove the file before building:
   ```bash
   rm -f public/hot
   docker build -f Dockerfile.unraid -t openmodhub:local .
   ```

3. Clear browser cache (Ctrl+F5) after deployment

## Local Mail

The default template uses:

```text
MAIL_MAILER=log
```

Verification emails are written to Laravel logs inside the mounted storage path. Configure SMTP variables in the template if real email delivery is needed.

## Debug Mode

Keep both of these disabled outside trusted local development:

```text
APP_DEBUG=false
Debug mode in /admin/settings: off
```

Admin debug mode can expose registration verification URLs on the verification prompt and should not be enabled for public access.
