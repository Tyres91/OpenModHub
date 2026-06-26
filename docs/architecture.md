# Architecture

## Application Style

OpenModHub is a Laravel monolith with a React frontend delivered through Inertia.js. This keeps the MVP simple while still using a modern component-based frontend.

## Backend

Laravel handles:

- Routing
- Controllers
- Authentication
- Authorization
- Validation
- Eloquent models and relationships
- Migrations and seeders
- Business services
- Optional queues and jobs

## Frontend

React with TypeScript handles:

- Inertia pages
- Reusable UI components
- Interactive forms
- Admin and moderation screens
- Public browsing and detail pages

Tailwind CSS is used for styling.

## Inertia.js

Inertia.js connects Laravel routes and controllers to React pages without requiring a separate REST API for the MVP.

Laravel controllers should return Inertia responses with only the data needed by each page. Authorization must stay server-side.

The authenticated layout receives global moderation todo counts through Inertia shared props. These counts are computed from pending mods, pending mod versions, and pending reports for admins and users with moderation permissions only; they are not stored as persistent notification records.

## Database

MariaDB or MySQL is the planned database. The schema should use Laravel migrations and clear Eloquent relationships.

## Docker

OpenModHub is built around a Docker-first development workflow. A new developer should be able to start the project with Docker Compose without manually installing every runtime dependency on the host system.

Docker and Docker Compose provide a reproducible local development environment. The expected services are:

- PHP/Laravel application service
- Web server or Laravel development server service, depending on setup
- MariaDB/MySQL service
- Node/Vite workflow, either inside the app container or locally documented
- Optional queue worker service for background jobs

The Docker setup stays practical and understandable. It supports local development, database migrations, frontend asset compilation, and test execution without becoming a production orchestration platform.

Local Unraid deployment is supported through `Dockerfile.unraid` and an Unraid Docker template in `docker/unraid/templates/openmodhub-app.xml`. The Unraid setup builds a local `openmodhub:local` image, runs a single app container, and connects to an existing MariaDB container such as `openmodhub-db` through environment variables.

## Plesk-Managed Hosting

OpenModHub also supports Plesk-managed PHP servers as a production target. The Plesk setup requires no Docker, no root access, and no manual server provisioning. It is documented in `docs/deployment-plesk.md`.

Key differences from the Docker / Unraid deployment:

- The web entry point is the Plesk domain document root, which is configured to point to the project's `public/` directory.
- The queue worker is a short-lived `php artisan queue:work` invocation triggered by a Plesk scheduled task every few minutes, not a long-running process.
- The Laravel scheduler is triggered by a Plesk scheduled task calling `php artisan schedule:run` every minute.
- The database is the Plesk-managed local MySQL or MariaDB instance, accessed over `127.0.0.1:3306`.
- File ownership follows the Plesk subscription user model (`psaserv:psacln` on Debian-based systems, `apache:psacln` on RHEL-based systems).
- The `public/storage` symlink is created with a relative target so it works on every host path.

## CI/CD

GitHub Actions is the planned CI/CD platform.

The initial CI workflow should run checks that are useful for every pull request or portfolio review:

- Install PHP dependencies
- Install Node dependencies
- Run backend tests
- Build frontend assets
- Run formatting or static checks where practical

The CD part should be prepared later once a deployment target is chosen. Until then, deployment automation should stay documented but not overengineered.

## Suggested Folder Structure

```text
app/
  Actions/
  Http/
    Controllers/
    Requests/
  Models/
  Policies/
  Services/
    RankService.php
    VirusTotalService.php
database/
  factories/
  migrations/
  seeders/
resources/
  js/
    Components/
    Layouts/
    Pages/
      Admin/
      Auth/
      Mods/
      Profile/
  css/
routes/
  web.php
tests/
  Feature/
  Unit/
```

The exact structure may evolve, but changes should be documented when they affect project conventions.

## Business Services

Use services for domain logic that should not live directly inside controllers.

Initial candidates:

- `RankService`: determines a user's rank based on approved mod count
- `VirusTotalService`: validates or fetches security information from VirusTotal later
- `VersionNormalizer`: validates and normalizes Composer-style semantic versions for mod releases

Services should stay focused and should not become generic utility containers.

`RankService` calculates a user's current normal rank from retroactive points. Points come from admin-managed `rank_point_rules` such as visible comments, approved mods, approved new versions, download thresholds, received ratings, and high average rating bonuses. If `users.rank_id` points to a rank marked `is_special`, the service returns that special rank and does not overwrite it with automatic points-based ranks.

Rank definitions are managed separately from point rules: `/admin/ranks` handles rank thresholds and special-rank flags, while `/admin/rank-point-rules` handles activity point values and thresholds.

## Localization

The application uses Laravel translation files and a custom locale resolution flow:

1. `SetLocale` middleware runs on every web request.
2. It checks the authenticated user's `locale` field first.
3. If the user has no preference, it checks the guest session locale.
4. If neither is set, it reads the `default_locale` from the `settings` table.
5. Falls back to `en` if nothing else is configured.

Translation source strings are managed in `resources/lang-source/translations.csv`. The generated Laravel files are stored in `lang/{locale}/messages.php` and shared with the React frontend via Inertia shared props. The `LanguageSwitcher` component allows guests and authenticated users to change their language preference.

## Settings, Legal Pages, and Tracking

Global settings are stored as key-value rows in the `settings` table. This keeps low-volume site configuration such as the default locale, legal page content, and tracking container ID out of source code.

Uploaded branding assets such as the site logo are stored on Laravel's `public` filesystem disk and referenced from settings by their local storage path. Public URLs are generated server-side and shared to React through Inertia shared props. External logo URLs are not accepted.

The `debug_mode` setting is intended for local development support only. When enabled, registration flashes a signed email verification URL to the verification prompt so developers can verify accounts without reading mail logs. It must remain disabled outside trusted development environments.

The legal pages are served by Laravel controllers through Inertia pages:

- `/impressum` for imprint information
- `/datenschutz` for privacy policy information

Google Tag Manager is configured as a container ID only. The ID is shared to React through Inertia shared props, but the GTM script is inserted client-side only after the visitor explicitly accepts analytics cookies in the consent banner. The Blade/root HTML must not include GTM scripts or noscript iframes, because that would load tracking before consent.

## Authorization

Use Laravel Policies and Gates for permissions.

Examples:

- `ModPolicy` for creating, updating, deleting, approving, and rejecting mods
- `CategoryPolicy` for category management
- `RankPolicy` for rank management
- `CommentPolicy` for moderation actions
- `ReportPolicy` for report handling

Frontend checks may improve the user experience, but server-side authorization is required for every privileged action.

## Registration Security

User registration uses Laravel's built-in email verification through `MustVerifyEmail`. New accounts are created as unverified and receive a verification email after the `Registered` event. Community write actions are protected with the `verified` middleware so unverified accounts cannot submit mods, rate, comment, or report content.

Registration also uses layered bot protection:

- Laravel web CSRF protection for the registration POST request
- Hidden honeypot field that must remain empty
- Minimum form completion time to reject instant automated submissions
- Registration rate limiting by IP address
- Cloudflare Turnstile after repeated failed registration attempts, configured through `TURNSTILE_SITE_KEY` and `TURNSTILE_SECRET_KEY`

Turnstile is only requested after the failed-attempt threshold is reached. This keeps the normal registration flow low-friction while adding a stronger challenge for suspicious repeated failures.

When admin-configured debug mode is enabled, newly registered users are redirected to the verification prompt with a flashed signed verification URL. The normal registration redirect remains unchanged when debug mode is disabled.

## Validation

Use Form Requests for validation and authorization when handling forms such as:

- Mod submission
- Mod review
- Category management
- Rank management
- Rating submission
- Comment submission
- Report submission

## Queues and Jobs

Queues are used for automated VirusTotal checks when `VIRUSTOTAL_ENABLED=true` and `VIRUSTOTAL_API_KEY` is configured. Submitted mod download URLs are sent to VirusTotal in `SubmitUrlToVirusTotalJob`, then `PollVirusTotalResultJob` fetches the analysis result after the configured delay.

VirusTotal automation stores moderation context only. It never auto-approves mods; administrators and users with review permissions still make the publication decision.

## Mod Releases

Mods use `mod_versions` for release-specific download links, changelogs, VirusTotal checks, and download counters. New mod submissions create an initial pending version. New versions for approved mods can be submitted by the owner and must be approved by an admin or a user with review permissions before they become public.

Approving a version clears `is_current` from other versions of the same mod and marks the approved version as current. The public mod download route resolves to the current approved version; version-specific download routes count clicks per version.

## Media Previews

Screenshots and MP3 files are treated as moderated media assets, not arbitrary file hosting. MP3 uploads are intended for soundmods where audio is either preview content or the actual sound-only mod download. Larger archives should continue to use external download links.

YouTube previews must be stored as validated URLs or extracted video IDs. The frontend should render a local placeholder first and load the YouTube embed only after the visitor explicitly clicks. User-provided iframe or script HTML must never be rendered.

Version-specific media belongs to `mod_versions` so pending media is reviewed before it becomes public. MP3 files can be used as preview audio and as the download target for sound-only mods when no external download URL is provided. Permanent mod deletion must delete related database rows and uploaded media files.

## Category Ordering

Categories should support an admin-managed `sort_order`. Admin users can reorder categories with drag-and-drop in the admin area. Public category filters and mod submission forms should use the configured order instead of alphabetical ordering.
