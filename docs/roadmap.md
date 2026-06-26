# Roadmap

## Phase 1: Foundation

- Laravel, Inertia, React setup
- Tailwind CSS setup
- Vite setup
- Docker-first local development setup
- Docker Compose services for Laravel/PHP, MariaDB/MySQL, and Node/Vite
- Authentication
- Role model
- Base layout
- Initial seeders for roles and sample data

## Phase 2: Mod Core

- Mod model and migration
- Category model and migration
- Mod creation form
- Mod image handling
- Public approved mod overview
- Public mod detail page
- Pending, approved, and rejected workflow
- Basic policies and form requests for mod actions

Current status: the Mod Core foundation is implemented for user submissions, seeded categories, public approved browsing, public approved detail pages, owner visibility for submitted mods, validation, and initial policy coverage. Permission-based review screens are still planned for Phase 3.

## Phase 3: Admin and Moderation

- Admin and moderation area
- Moderation queue
- Approve and reject actions
- Category management
- Rank management
- System settings for localization, legal pages, and tracking configuration
- Admin and moderation navigation
- Basic dashboard metrics

Current status: moderation queue, approve/reject actions, admin-only category management, admin-only rank management, admin-only user management, admin-only settings for localization/legal/tracking, permission-based moderation navigation, and basic dashboard metrics are implemented.

## Phase 4: Community Features

- Ratings
- Average rating display
- Comments
- Comment moderation
- Report mod feature
- Report management screen
- User profiles
- Rank display in profiles and mod entries

Current status: ratings, comments, reports, public user profiles, and user-facing rank display are implemented for approved mods. Users can rate, comment, and report approved mods. Admins and users with moderation permissions can moderate comments and manage reports (resolve/dismiss). Localization with English and German is implemented, including a language switcher, admin default language setting, and user profile language preference.

## Phase 5: Security and Automation

- VirusTotal link field
- VirusTotal API preparation
- `VirusTotalService`
- Background jobs for automated checks
- Security check history table
- GitHub Actions CI workflow
- CI checks for backend tests, frontend build, and formatting where practical
- CD pipeline preparation for future deployment
- Feature and unit tests
- Moderated mod versions with changelog
- Permanent mod deletion with cleanup and strong confirmation
- Admin-managed category ordering
- Privacy-aware YouTube previews
- MP3 audio previews and soundmod downloads

Current status: the VirusTotal link field remains available and optional queued VirusTotal API checks are implemented through `VirusTotalService`, `SubmitUrlToVirusTotalJob`, `PollVirusTotalResultJob`, and the `security_checks` table. Consent-gated Google Tag Manager configuration is implemented for optional tracking, with no tracking script loaded before explicit analytics consent.

Moderated mod versions with changelogs are implemented through `mod_versions`. Existing mods are backfilled into initial versions without deleting users or existing content.

Planned updates: permanent mod deletion should be hardened with backend confirmation and cleanup tests. Categories should become drag-and-drop sortable. Version media should support MP3 audio and click-to-load YouTube previews.

## Phase 6: Managed Hosting Deployment

Current status: a Plesk-managed PHP server deployment path is implemented. OpenModHub can now run on a standard Plesk subscription with PHP 8.4, the Plesk-managed MySQL/MariaDB instance, and the Plesk scheduled-task system.

- Plesk-specific environment template at `.env.plesk.example`
- Document root set to the project's `public/` directory via Plesk hosting settings
- Queue worker replaced by a short-lived `php artisan queue:work` invocation triggered every five minutes by a Plesk scheduled task
- Laravel scheduler triggered every minute by a Plesk scheduled task
- Frontend build (`public/build/`) produced on a build host and uploaded with the release
- Idempotent deploy helper at `scripts/deploy-plesk.sh`
- Full walkthrough in `docs/deployment-plesk.md`
- Decision recorded in `docs/decisions.md` (2026-06-26)

Planned follow-up: investigate whether Plesk's Node.js extension can be used to run the frontend build on the server itself, so deployments become self-contained.
