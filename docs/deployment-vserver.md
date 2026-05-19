# vServer Production Deployment

This guide covers deploying OpenModHub to a blank Debian 13 vServer with a domain and HTTPS.

## Architecture

```
Internet
  │
  ▼
┌─────────────────┐
│   Nginx (443)   │  ← Let's Encrypt SSL
│  Reverse Proxy  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐     ┌──────────────┐
│  OpenModHub     │────▶│  MariaDB     │
│  App Container  │     │  Container   │
│  (port 8000)    │     │  (port 3306) │
└─────────────────┘     └──────────────┘
         │
         ▼
┌─────────────────┐
│  Queue Worker   │  ← Background jobs
│  Container      │
└─────────────────┘
```

## Prerequisites

- Debian 13 vServer with root access
- A domain pointing to your server IP (e.g., `mods.example.com`)
- GitHub account (for GitHub Container Registry)
- SSH access to the server

---

## Step 1: Set Up Container Registry

We use **GitHub Container Registry (GHCR)** — free with any GitHub account.

### 1.1 Create a Personal Access Token

1. Go to GitHub → Settings → Developer settings → Personal access tokens → Fine-grained tokens
2. Create a new token with:
   - **Repository access**: Only the OpenModHub repository
   - **Permissions**: `packages: read` (for pulling) and `packages: write` (for pushing)
3. Save the token — you will need it once to push the image

### 1.2 Authenticate Docker Locally

```bash
echo <YOUR_TOKEN> | docker login ghcr.io -u <YOUR_GITHUB_USERNAME> --password-stdin
```

---

## Step 2: Build and Push the Docker Image

### 2.1 Build the Production Image

From your local project directory:

```bash
docker build -f Dockerfile.unraid -t ghcr.io/<YOUR_GITHUB_USERNAME>/openmodhub:latest .
```

### 2.2 Push to GitHub Container Registry

```bash
docker push ghcr.io/<YOUR_GITHUB_USERNAME>/openmodhub:latest
```

> **Tip**: Use semantic version tags for releases, e.g., `ghcr.io/<user>/openmodhub:v1.0.0`

### 2.3 Optional: Set Up GitHub Actions for Automated Builds

Create `.github/workflows/docker-publish.yml` in your repository:

```yaml
name: Build and Publish Docker Image

on:
  push:
    branches: [main]
  release:
    types: [published]

env:
  REGISTRY: ghcr.io
  IMAGE_NAME: ${{ github.repository }}

jobs:
  build-and-push:
    runs-on: ubuntu-latest
    permissions:
      contents: read
      packages: write

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4

      - name: Log in to Container Registry
        uses: docker/login-action@v3
        with:
          registry: ${{ env.REGISTRY }}
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Extract metadata
        id: meta
        uses: docker/metadata-action@v5
        with:
          images: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}
          tags: |
            type=ref,event=branch
            type=semver,pattern={{version}}
            type=sha

      - name: Build and push
        uses: docker/build-push-action@v5
        with:
          context: .
          file: Dockerfile.unraid
          push: true
          tags: ${{ steps.meta.outputs.tags }}
          labels: ${{ steps.meta.outputs.labels }}
```

---

## Step 3: Prepare the vServer

SSH into your server as root:

```bash
ssh root@<YOUR_SERVER_IP>
```

### 3.1 Update the System

```bash
apt update && apt upgrade -y
```

### 3.2 Install Required Packages

```bash
apt install -y \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg \
    lsb-release \
    git \
    ufw
```

### 3.3 Install Docker

```bash
# Add Docker GPG key
install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/debian/gpg -o /etc/apt/keyrings/docker.asc
chmod a+r /etc/apt/keyrings/docker.asc

# Add Docker repository
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/debian \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  tee /etc/apt/sources.list.d/docker.list > /dev/null

# Install Docker
apt update
apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
```

### 3.4 Enable and Start Docker

```bash
systemctl enable docker
systemctl start docker
```

### 3.5 Verify Docker Installation

```bash
docker run --rm hello-world
```

### 3.6 Configure Firewall

```bash
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp    # SSH
ufw allow 80/tcp    # HTTP (for Let's Encrypt)
ufw allow 443/tcp   # HTTPS
ufw enable
```

---

## Step 4: Set Up Nginx Reverse Proxy and SSL

### 4.1 Install Nginx and Certbot

```bash
apt install -y nginx certbot python3-certbot-nginx
```

### 4.2 Create Nginx Configuration

Create `/etc/nginx/sites-available/openmodhub`:

```nginx
server {
    listen 80;
    server_name mods.example.com;

    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Forwarded-Port $server_port;

        # WebSocket support (if needed for future features)
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";

        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;

        # File upload size (adjust as needed)
        client_max_body_size 50M;
    }
}
```

### 4.3 Enable the Site

```bash
ln -s /etc/nginx/sites-available/openmodhub /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

### 4.4 Obtain SSL Certificate

```bash
certbot --nginx -d mods.example.com
```

Follow the prompts. Certbot will automatically update your Nginx config with SSL settings.

### 4.5 Auto-Renew SSL Certificate

Certbot sets up a systemd timer automatically. Verify it:

```bash
systemctl status certbot.timer
```

Test dry-run renewal:

```bash
certbot renew --dry-run
```

---

## Step 5: Authenticate vServer with Container Registry

### 5.1 Create a Docker Config Directory

```bash
mkdir -p /root/.docker
```

### 5.2 Create Authenticated Login

```bash
echo <YOUR_TOKEN> | docker login ghcr.io -u <YOUR_GITHUB_USERNAME> --password-stdin
```

This creates `/root/.docker/config.json` with your credentials.

> **Security note**: For production, consider using a dedicated GitHub machine user or deploy token with read-only package access.

---

## Step 6: Deploy the Application

### 6.1 Create Application Directory

```bash
mkdir -p /opt/openmodhub
cd /opt/openmodhub
```

### 6.2 Create Production Docker Compose File

Create `/opt/openmodhub/docker-compose.yml`:

```yaml
services:
  app:
    image: ghcr.io/<YOUR_GITHUB_USERNAME>/openmodhub:latest
    container_name: openmodhub-app
    restart: unless-stopped
    working_dir: /var/www/html
    volumes:
      - storage-data:/var/www/html/storage
    ports:
      - "127.0.0.1:8000:8000"
    env_file:
      - .env
    depends_on:
      db:
        condition: service_healthy
    networks:
      - openmodhub

  queue:
    image: ghcr.io/<YOUR_GITHUB_USERNAME>/openmodhub:latest
    container_name: openmodhub-queue
    restart: unless-stopped
    working_dir: /var/www/html
    volumes:
      - storage-data:/var/www/html/storage
    command: php artisan queue:work --tries=3
    env_file:
      - .env
    depends_on:
      db:
        condition: service_healthy
    networks:
      - openmodhub

  db:
    image: mariadb:11
    container_name: openmodhub-db
    restart: unless-stopped
    environment:
      MARIADB_DATABASE: openmodhub
      MARIADB_USER: openmodhub
      MARIADB_PASSWORD: <STRONG_DB_PASSWORD>
      MARIADB_ROOT_PASSWORD: <STRONG_ROOT_PASSWORD>
    volumes:
      - db-data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "healthcheck.sh", "--connect", "--innodb_initialized"]
      interval: 10s
      timeout: 5s
      retries: 5
    networks:
      - openmodhub

volumes:
  storage-data:
  db-data:

networks:
  openmodhub:
    driver: bridge
```

### 6.3 Create Production Environment File

Create `/opt/openmodhub/.env`:

```env
APP_NAME=OpenModHub
APP_ENV=production
APP_DEBUG=false
APP_URL=https://mods.example.com

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=openmodhub
DB_USERNAME=openmodhub
DB_PASSWORD=<STRONG_DB_PASSWORD>

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=.example.com

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=<YOUR_SMTP_HOST>
MAIL_PORT=587
MAIL_USERNAME=<YOUR_SMTP_USER>
MAIL_PASSWORD=<YOUR_SMTP_PASSWORD>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@mods.example.com"
MAIL_FROM_NAME="${APP_NAME}"

TURNSTILE_SITE_KEY=<YOUR_CLOUDFLARE_TURNSTILE_SITE_KEY>
TURNSTILE_SECRET_KEY=<YOUR_CLOUDFLARE_TURNSTILE_SECRET_KEY>

VIRUSTOTAL_ENABLED=false
VIRUSTOTAL_API_KEY=
VIRUSTOTAL_POLL_DELAY_SECONDS=90

VITE_APP_NAME="${APP_NAME}"
```

> **Important**: Replace all `<...>` placeholders with actual values. Generate `APP_KEY` in the next step.

### 6.4 Generate APP_KEY

```bash
docker run --rm ghcr.io/<YOUR_GITHUB_USERNAME>/openmodhub:latest php artisan key:generate --show
```

Copy the `base64:...` output and add it to your `.env`:

```env
APP_KEY=base64:...
```

### 6.5 Pull and Start Containers

```bash
cd /opt/openmodhub
docker compose pull
docker compose up -d
```

### 6.6 Verify Deployment

Check container status:

```bash
docker compose ps
```

Check application logs:

```bash
docker compose logs -f app
```

The app should be running. Test it:

```bash
curl -I http://127.0.0.1:8000
```

You should see a `200 OK` response.

---

## Step 7: Post-Deployment

### 7.1 Create Admin Account

If you seeded users in development, use those credentials. Otherwise, register a new account through the web interface and promote it to admin via the database:

```bash
docker compose exec app php artisan tinker
```

```php
$user = \App\Models\User::where('email', 'admin@example.com')->first();
$user->role_id = \App\Models\Role::where('name', 'admin')->first()->id;
$user->save();
```

### 7.2 Configure SMTP for Email Verification

The application requires email verification. Configure a real SMTP provider (e.g., Mailgun, Postmark, SendGrid, or your own mail server) in the `.env` file.

### 7.3 Set Up Cloudflare Turnstile (Optional but Recommended)

1. Go to https://developers.cloudflare.com/turnstile/
2. Create a new site
3. Add the site key and secret key to your `.env`

### 7.4 Enable VirusTotal Integration (Optional)

1. Get a free API key from https://www.virustotal.com/gui/
2. Add to `.env`:
   ```env
   VIRUSTOTAL_ENABLED=true
   VIRUSTOTAL_API_KEY=<YOUR_API_KEY>
   ```

---

## Maintenance

### Update the Application

When a new image is available:

```bash
cd /opt/openmodhub
docker compose pull
docker compose up -d
```

The container runs `php artisan migrate --force` on startup, so pending migrations are applied automatically.

### View Logs

```bash
# All containers
docker compose logs -f

# Specific container
docker compose logs -f app
docker compose logs -f queue
docker compose logs -f db
```

### Run Artisan Commands

```bash
docker compose exec app php artisan <command>
```

Examples:

```bash
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan queue:restart
```

### Database Backup

```bash
docker compose exec db sh -c 'exec mysqldump -u openmodhub -p"$MARIADB_PASSWORD" openmodhub' > /backup/openmodhub-$(date +%F).sql
```

### Database Restore

```bash
docker compose exec -T db sh -c 'exec mysql -u openmodhub -p"$MARIADB_PASSWORD" openmodhub' < /backup/openmodhub-2025-01-15.sql
```

### Storage Backup

The application data (uploads, logs) is stored in the `storage-data` Docker volume. Back it up:

```bash
docker run --rm -v openmodhub_storage-data:/data -v /backup:/backup alpine tar czf /backup/openmodhub-storage-$(date +%F).tar.gz -C /data .
```

### Renew SSL Certificate Manually

```bash
certbot renew
systemctl reload nginx
```

---

## Troubleshooting

### Container Won't Start

```bash
docker compose logs app
```

Common issues:
- **Database connection failed**: Ensure `DB_HOST=db` and credentials match
- **APP_KEY missing**: Generate one and add to `.env`
- **Migrations failed**: Check database container is healthy first

### 502 Bad Gateway

Nginx cannot reach the app container. Check:

```bash
# Is the app running?
docker compose ps

# Is it listening on port 8000?
docker compose exec app curl -I http://127.0.0.1:8000

# Nginx error log
tail -f /var/log/nginx/error.log
```

### Mixed Content Errors

Ensure `APP_URL` in `.env` matches your HTTPS domain:

```env
APP_URL=https://mods.example.com
```

Then clear caches:

```bash
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
```

### Queue Jobs Not Processing

Ensure the queue worker container is running:

```bash
docker compose ps queue
```

Restart it:

```bash
docker compose restart queue
```

### "Permission Denied" on Storage

```bash
docker compose exec app chmod -R ug+rw storage bootstrap/cache
```

---

## Security Checklist

- [x] `APP_DEBUG=false` in production
- [x] `SESSION_ENCRYPT=true`
- [x] Strong database passwords
- [x] Firewall configured (only 22, 80, 443 open)
- [x] SSL certificate active
- [x] SMTP configured for email verification
- [x] Container registry token stored securely
- [ ] Regular database backups scheduled (cron)
- [ ] Log rotation configured
- [ ] Fail2ban installed for SSH protection

---

## Quick Reference

| Path / Command | Purpose |
|---|---|
| `/opt/openmodhub/` | Application directory |
| `/opt/openmodhub/.env` | Environment configuration |
| `/opt/openmodhub/docker-compose.yml` | Docker Compose configuration |
| `/etc/nginx/sites-available/openmodhub` | Nginx configuration |
| `/etc/letsencrypt/live/` | SSL certificates |
| `docker compose up -d` | Start all containers |
| `docker compose down` | Stop all containers |
| `docker compose logs -f` | View logs |
| `docker compose exec app php artisan` | Run artisan commands |
| `certbot renew` | Renew SSL certificate |
