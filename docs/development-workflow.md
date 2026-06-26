# Development Workflow

## Feature Development

New features should be implemented in small, reviewable steps.

Recommended order:

1. Read the relevant documentation.
2. Update documentation if the feature changes scope, architecture, permissions, or data model.
3. Add or update migrations.
4. Add or update models and relationships.
5. Add authorization through Policies, Gates, or Form Requests.
6. Add controllers and routes.
7. Add Inertia pages and React components.
8. Add tests where practical.
9. Run relevant checks.
10. Summarize changes and any remaining risks.

## Branch Naming

Suggested branch names:

- `feature/mod-submission`
- `feature/admin-moderation`
- `feature/rank-system`
- `fix/mod-policy-authorization`
- `docs/update-architecture`

## Commit Style

Keep commits focused and descriptive.

Examples:

- `docs: add initial project documentation`
- `feat: add mod submission workflow`
- `feat: add admin moderation queue`
- `fix: enforce mod update authorization`
- `test: cover rating uniqueness rule`

## Pull Request Style

Even when working locally, changes should be shaped like small pull requests:

- One clear purpose per change set
- No unrelated formatting churn
- Documentation updated when needed
- Migration and seed data included when needed
- Tests included for important behavior

## Tests and Checks

Local checks should run through the Docker-based development environment whenever practical. This keeps local development close to the CI environment and avoids host-specific setup issues.

Useful local commands:

```bash
docker compose build
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan test
docker compose run --rm node npm run build
```

For local email verification testing, the default `.env.example` uses `MAIL_MAILER=log`, so verification links are written to the Laravel logs instead of sent through a real mail provider.

## Production Deployment

OpenModHub supports two production targets in addition to the local Docker Compose setup:

- **Unraid** (single container, MariaDB as a separate container) — see `docs/deployment-unraid.md`.
- **Debian vServer with nginx and Certbot** (Docker-based) — see `docs/deployment-server.md`.
- **Plesk-managed PHP server** (no Docker, no root) — see `docs/deployment-plesk.md`.

For Plesk, the production deployment steps are:

```bash
ssh <plesk-user>@<server>
cd /var/www/vhosts/<sub>/openmodhub
cp .env.plesk.example .env
# edit .env with database, mail, and APP_URL
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan storage:link
php artisan migrate --seed --force
php artisan config:cache route:cache view:cache
```

The queue worker and the Laravel scheduler are configured as Plesk scheduled tasks, not as background processes. See `docs/deployment-plesk.md` for the full walkthrough.

Admins can also enable Debug mode in `/admin/settings` during local development. With Debug mode enabled, newly registered users see their signed verification URL directly on the email verification prompt. Keep this disabled outside trusted development environments.

For adaptive registration captcha testing, configure Cloudflare Turnstile keys in `.env` when needed:

```bash
TURNSTILE_SITE_KEY=
TURNSTILE_SECRET_KEY=
```

For backend changes, consider:

- Feature tests for workflows
- Policy tests for permissions
- Unit tests for services such as `RankService`
- Migration and seeder verification

For frontend changes, consider:

- Running the Vite build
- Checking form validation behavior
- Testing responsive layouts manually

GitHub Actions should run the project's core CI checks for pull-request-style changes:

- Composer dependency installation
- Node dependency installation
- Backend test suite
- Frontend build
- Formatting or static checks where practical

CD should be added after the deployment target is known. Until then, deployment steps should be documented but not treated as required for the MVP.

## Documentation Updates

Update documentation when changing:

- Feature scope
- Data model
- Permissions
- Architecture
- Development workflow
- Roadmap priorities
- Significant technical decisions

Large decisions should be added to `docs/decisions.md` with date, status, context, decision, and consequences.

## Translation Workflow

All UI translations are managed through a single CSV file:

```
resources/lang-source/translations.csv
```

### CSV Structure

The CSV has three columns: `key`, `en`, `de`.

Keys use dot notation matching Laravel's nested array structure:

```csv
key,en,de
navigation.mods,Mods,Mods
mods.submit_mod,Submit Mod,Mod einreichen
admin.ranks.heading,Manage ranks,Ränge verwalten
```

### Adding or Changing Translations

1. Edit `resources/lang-source/translations.csv` directly.
2. Run `docker compose exec app php artisan translations:generate --force` to regenerate PHP files.
3. Use `t('key.here', 'Fallback')` in React components.

### Important Rules

- **Never edit `lang/en/messages.php` or `lang/de/messages.php` directly.** These are generated files.
- Each key must be unique. Duplicates will be caught by tests.
- Both `en` and `de` columns must be filled. Empty translations will fail tests.
- Run `docker compose exec app php artisan test` to validate CSV structure.

### Generating PHP Files

```bash
docker compose exec app php artisan translations:generate --force
```

This command reads the CSV and regenerates `lang/en/messages.php` and `lang/de/messages.php`.
