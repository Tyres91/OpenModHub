# Plesk Deployment Guide

This guide covers installing and running OpenModHub on a Plesk-managed PHP server with MySQL or MariaDB. No Docker or root access is required. Everything runs under the Plesk subscription user.

## Target Environment

- Plesk Obsidian (any recent version)
- PHP 8.4 as the domain handler
- MySQL 8 or MariaDB 10.6+ database created in Plesk
- Domain with HTTPS (Plesk Let's Encrypt or custom certificate)
- SSH access to the Plesk subscription user (recommended, but optional)
- Outbound SMTP for email verification and notifications

## Assumed Paths

Replace `<sub>` with your Plesk subscription identifier. Typical paths:

```text
Debian / Ubuntu:
/var/www/vhosts/<sub>/openmodhub

RHEL / AlmaLinux / CentOS:
/var/www/vhosts/<sub>/openmodhub
```

Both distributions use the same `/var/www/vhosts/<sub>/` layout. The Linux user owning the files is `psaserv` (Debian) or `apache` (RHEL-based). The readable group is `psacln` (Debian) or `psacln` (RHEL). Adjust commands in this guide when the system user differs.

---

## Step 1: Create the Database in Plesk

1. Open Plesk → **Databases** → **Add Database**.
2. Database name: `openmodhub` (or similar)
3. Database user: `openmodhub` with a strong password
4. Use **Local MySQL/MariaDB server** unless your provider supplies a remote one.

Note the values. You will need them in the `.env` file later:

- Database host: usually `127.0.0.1` for the local Plesk database server
- Database port: `3306`
- Database name, user, and password

---

## Step 2: Set the Document Root

Plesk's default document root is `httpdocs/`. OpenModHub expects `public/`. Change it per domain:

1. Plesk → **Websites & Domains** → select the domain → **Hosting Settings**.
2. Set **Document root** to `openmodhub/public` (or to `public` if you upload directly into the subscription root).
3. Save.

If your Plesk plan does not allow changing the document root, see the **Fallback: Symlink Document Root** section below.

---

## Step 3: Upload the Application

You have three options. Pick the one that fits your workflow.

### Option A: Git via SSH (Recommended)

```bash
ssh <plesk-user>@<server>
cd /var/www/vhosts/<sub>
git clone <your-repo-url> openmodhub
cd openmodhub
```

### Option B: SFTP Upload

1. Build the frontend assets locally before uploading:

   ```bash
   npm ci
   npm run build
   ```

2. Zip the project (excluding `node_modules` and `.git`):

   ```bash
   rsync -avz --exclude=node_modules --exclude=.git --exclude=storage/logs/* \
       ./ <plesk-user>@<server>:/var/www/vhosts/<sub>/openmodhub/
   ```

   Or use any SFTP client such as FileZilla or WinSCP.

### Option C: Plesk File Manager

1. Plesk → **Files** → navigate to the subscription folder.
2. Upload a zip archive and extract it to `openmodhub/`.

### Important: Frontend Build Directory

`public/build/` is in `.gitignore` and is not tracked in Git. The Laravel app serves compiled assets from this directory. Make sure the uploaded `public/build/` folder contains the production build (look for hashed `app-*.js` and `app-*.css` files). If it is empty or missing, rebuild the assets locally before uploading:

```bash
npm ci
npm run build
```

---

## Step 4: Create the `.env` File

Copy the Plesk example and edit it:

```bash
cd /var/www/vhosts/<sub>/openmodhub
cp .env.plesk.example .env
nano .env
```

Update at minimum:

- `APP_URL` to your real HTTPS domain
- `APP_NAME` if you want a different display name
- `DB_HOST` to `127.0.0.1` (or the value shown in Plesk for the database)
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` from Step 1
- `MAIL_*` with your SMTP provider settings
- `VIRUSTOTAL_*` only if you enable VirusTotal API checks

Generate the `APP_KEY` after the first install (see next step).

---

## Step 5: Install PHP Dependencies and Run Initial Setup

SSH into the server:

```bash
ssh <plesk-user>@<server>
cd /var/www/vhosts/<sub>/openmodhub
composer install --no-dev --optimize-autoloader --no-interaction
php artisan key:generate
php artisan storage:link
php artisan migrate --seed --force
```

`storage:link` creates the symlink from `public/storage` to `storage/app/public`. On Plesk this is the correct relative path. The previous symlink in the repository that pointed to `/var/www/html/...` was a Docker artifact and must be removed first:

```bash
rm -f public/storage
php artisan storage:link
```

`migrate --seed` runs all 36 migrations and seeds the default roles, permissions, categories, ranks, rank point rules, settings, and email templates.

If `composer install` fails with a memory error, increase the PHP memory limit:

```bash
php -d memory_limit=512M /usr/bin/composer install --no-dev --optimize-autoloader --no-interaction
```

Or set `memory_limit = 512M` in the Plesk PHP settings (see Step 9) and run `composer install` again.

---

## Step 6: Cache Configuration and Routes

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

These commands precompile the Laravel configuration, routes, and Blade views for production performance. Re-run them after every deployment.

---

## Step 7: Set File Permissions

Plesk requires the subscription user to own the storage and cache directories. Pick the matching command for your OS:

**Debian / Ubuntu:**

```bash
cd /var/www/vhosts/<sub>/openmodhub
chown -R psaserv:psacln storage bootstrap/cache
chmod -R ug+rw storage bootstrap/cache
```

**RHEL / AlmaLinux / CentOS:**

```bash
cd /var/www/vhosts/<sub>/openmodhub
chown -R apache:psacln storage bootstrap/cache
chmod -R ug+rw storage bootstrap/cache
```

The repository includes uploaded media (screenshots, MP3, branding) under `storage/app/public/`. These are written at build time and must remain readable by the web server.

---

## Step 8: Configure Scheduled Tasks

OpenModHub uses the Laravel scheduler for cleanup and background tasks. Plesk provides cron-like scheduled tasks per subscription.

1. Plesk → **Websites & Domains** → **Scheduled Tasks** → **Add Task**.
2. **Run:** `* * * * *` (every minute).
3. **Command:**

   ```bash
   cd /var/www/vhosts/<sub>/openmodhub && php artisan schedule:run >> /dev/null 2>&1
   ```

4. Save.

`schedule:run` only triggers when tasks are due, so a one-minute interval is safe and standard for Laravel on shared hosting.

---

## Step 9: Configure the Queue Worker as a Cron Job

Plesk does not support long-running background processes. The recommended approach is a short-lived worker triggered every few minutes via the Plesk scheduler.

1. Plesk → **Websites & Domains** → **Scheduled Tasks** → **Add Task**.
2. **Run:** every 5 minutes (`*/5 * * * *`).
3. **Command:**

   ```bash
   cd /var/www/vhosts/<sub>/openmodhub && php artisan queue:work --max-time=240 --stop-when-empty --tries=3 >> /dev/null 2>&1
   ```

Explanation of the flags:

- `--max-time=240` stops the worker after 4 minutes so it does not overlap with the next cron tick.
- `--stop-when-empty` exits as soon as the queue is empty, so most runs finish in a few seconds.
- `--tries=3` matches the local Docker default and gives transient failures a fair retry budget.

This setup covers the VirusTotal submit and poll jobs, queued email notifications, and any other `ShouldQueue` jobs added in the future.

If you have no background jobs to process, you can alternatively set `QUEUE_CONNECTION=sync` in `.env`. Email will then be sent synchronously and the application will block until the SMTP provider responds. This is **not recommended** for production.

---

## Step 10: Verify the Deployment

Open the domain in a browser. You should see the public mod overview at `https://<your-domain>/`.

Check the application status:

```bash
php artisan about
```

Check the scheduled tasks:

```bash
php artisan schedule:list
```

Check the queue:

```bash
php artisan queue:failed
```

---

## Step 11: Create the First Admin User

OpenModHub does not auto-create an admin account. Register a user through the web interface, then promote the account via SSH:

```bash
cd /var/www/vhosts/<sub>/openmodhub
php artisan tinker
```

```php
$user = \App\Models\User::where('email', 'your-admin@example.com')->first();
$user->roles()->sync([\App\Models\Role::where('slug', 'admin')->first()->id]);
$user->markEmailAsVerified();
$user->save();
```

The seeded development accounts (`admin@example.com`, `test@example.com` with password `password`) only exist if you ran `php artisan migrate --seed`. Rotate or delete them on a public installation.

---

## Step 12: Configure Email and Optional Services

### SMTP for Email Verification and Notifications

Email verification and moderation notifications are required for the application to function correctly. Set the following in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=<your-smtp-host>
MAIL_PORT=587
MAIL_USERNAME=<your-smtp-user>
MAIL_PASSWORD=<your-smtp-password>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@<your-domain>"
MAIL_FROM_NAME="${APP_NAME}"
```

Plesk itself can be used as a mail relay: configure `MAIL_HOST=127.0.0.1` and ensure the local Postfix accepts outbound mail for the subscription's domain.

After editing `.env`, clear the config cache:

```bash
php artisan config:clear
php artisan config:cache
```

### Cloudflare Turnstile (Optional)

```env
TURNSTILE_SITE_KEY=<your-site-key>
TURNSTILE_SECRET_KEY=<your-secret-key>
```

### VirusTotal API (Optional)

```env
VIRUSTOTAL_ENABLED=true
VIRUSTOTAL_API_KEY=<your-api-key>
VIRUSTOTAL_POLL_DELAY_SECONDS=90
```

VirusTotal results are stored as moderation context only. They never auto-approve mods.

---

## Updates and Re-Deployment

When a new version of the application is available:

```bash
ssh <plesk-user>@<server>
cd /var/www/vhosts/<sub>/openmodhub
git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan storage:link 2>/dev/null || true
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

A ready-to-use script is provided at `scripts/deploy-plesk.sh`. Copy it to the server and adjust the path variable at the top.

The frontend build (`public/build/`) is rebuilt locally and uploaded with the release. Re-upload this directory when the frontend changes.

---

## Backups

### Database Backup

Schedule a daily database dump via Plesk's **Backup Manager** or via a scheduled task:

```bash
*/5 * * * * mysqldump -u<db-user> -p<db-password> <db-name> | gzip > /var/www/vhosts/<sub>/backups/db-$(date +\%F).sql.gz
```

Plesk's built-in backup manager is usually the simpler option and supports cloud storage.

### Storage Backup

Uploaded media, branding, and generated favicons live under `storage/app/public/`. Back them up with a scheduled task that archives the directory:

```bash
*/5 * * * * tar -czf /var/www/vhosts/<sub>/backups/storage-$(date +\%F).tar.gz -C /var/www/vhosts/<sub>/openmodhub storage/app/public
```

---

## Troubleshooting

### 500 Internal Server Error After Deployment

Clear all caches and re-run permissions:

```bash
php artisan optimize:clear
chown -R psaserv:psacln storage bootstrap/cache
chmod -R ug+rw storage bootstrap/cache
```

Check `storage/logs/laravel.log` for the underlying error.

### `SQLSTATE[HY000] [2002] No such file or directory`

The DB host is wrong. Use `127.0.0.1` instead of `localhost`. Plesk's local database server listens on TCP even though the CLI default is a socket.

### `Permission denied` on `storage/` or `bootstrap/cache/`

Re-run Step 7. The web server must be able to write to these directories.

### Frontend Shows 404 or Old CSS / JS

`public/build/` is missing or out of date. Rebuild the assets locally and re-upload:

```bash
npm ci
npm run build
```

Verify the `public/build/manifest.json` file is present on the server.

### Email Verification Links Not Arriving

SMTP settings in `.env` are not correct. Check `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, and `MAIL_PASSWORD`. Test with:

```bash
php artisan tinker
\Illuminate\Support\Facades\Mail::raw('Test', function ($message) { $message->to('you@example.com')->subject('Test'); });
```

### Queue Jobs Not Processing

Check that the scheduled task in Step 9 is enabled and that the command path is correct. Inspect failed jobs:

```bash
php artisan queue:failed
php artisan queue:retry all
```

### Composer Install Runs Out of Memory

Set `memory_limit` to at least 512M in the Plesk PHP settings (Websites & Domains → PHP Settings), or run Composer with `-d memory_limit=512M`.

### Mixed Content Warnings

`APP_URL` in `.env` does not match the URL the browser uses. Update it to your HTTPS domain and clear the config cache.

---

## Fallback: Symlink Document Root

If your Plesk plan does not allow editing the document root:

1. Upload the project as usual.
2. Plesk → **Files** → open `httpdocs/`.
3. Remove or rename the default `httpdocs/index.html`.
4. Create a symlink:

   ```bash
   cd /var/www/vhosts/<sub>/httpdocs
   ln -s ../openmodhub/public/* .
   ```

The `public/` contents are then reachable under `httpdocs/`. This is a less clean solution and is more sensitive to leftover files in `httpdocs/`. Prefer the document root setting if available.

---

## Security Checklist

- `APP_DEBUG=false` in production
- `APP_ENV=production` in `.env`
- `SESSION_ENCRYPT=true` in `.env`
- Strong database password (Plesk's random generator is fine)
- HTTPS enforced (Plesk → SSL/TLS Certificates → set the certificate to "used")
- Admin account created and the seeded `admin@example.com` deleted if you used seeders
- `storage/` and `bootstrap/cache/` not accessible from the web (Plesk protects this by default)
- `APP_KEY` present and unique per environment
- SMTP credentials stored only in `.env`, never in source control
- `php artisan storage:link` symlink verified to point inside the project, not the absolute `/var/www/html/...` Docker path

---

## Quick Reference

| Path / Command | Purpose |
|---|---|
| `/var/www/vhosts/<sub>/openmodhub` | Application root |
| `/var/www/vhosts/<sub>/openmodhub/.env` | Environment configuration |
| `/var/www/vhosts/<sub>/openmodhub/public` | Web entry point (document root) |
| `/var/www/vhosts/<sub>/openmodhub/storage/app/public` | Uploaded media (screenshots, MP3, branding) |
| `php artisan about` | Show environment and version info |
| `php artisan migrate --force` | Apply pending migrations |
| `php artisan optimize:clear` | Clear all cached config, routes, and views |
| `php artisan config:cache` | Cache configuration |
| `php artisan route:cache` | Cache routes |
| `php artisan view:cache` | Cache Blade views |
| `php artisan storage:link` | Re-create the `public/storage` symlink |
| `php artisan queue:work --stop-when-empty` | Run the queue worker once |
| `php artisan schedule:run` | Run due scheduled tasks |
