# OpenModHub

OpenModHub is a moderated mod portal built as a modern Laravel portfolio project. Users can submit mods with screenshots, metadata, external download links, and security information. Submissions are reviewed by editors or administrators before they become publicly visible.

The goal is to demonstrate a clean, maintainable full-stack architecture without relying on WordPress or a generic CMS.

## Tech Stack

- Laravel
- Inertia.js
- React with TypeScript
- Tailwind CSS
- MariaDB / MySQL
- Docker / Docker Compose
- Vite
- GitHub Actions CI/CD, CI configured and CD planned

## Feature Overview

- Public mod listing with search and filters
- Mod detail pages
- User-submitted mods with moderation workflow
- Admin and editorial review process
- Category and topic management
- User roles: Admin, Editor, User
- Email verification for new accounts with honeypot and adaptive captcha protection
- Ratings with one rating per user and mod
- Comments with moderation support
- Report system for mods with admin/editor management
- Localization with English and German, language switcher, and admin default language setting
- Admin-configurable legal pages and consent-gated Google Tag Manager integration
- Rank system based on published mod count
- VirusTotal link and optional queued VirusTotal API checks for submitted download URLs

## Planned MVP

- Laravel, Inertia, React, Tailwind setup
- Docker-based local development environment
- Authentication
- Role model with Admin, Editor, and User
- Mod submission form
- Pending, approved, and rejected mod workflow
- Public approved mod index
- Public mod detail page
- Basic admin/editor moderation queue
- Category management
- Initial rank display
- VirusTotal link field

## Setup

OpenModHub uses a Docker-first local development workflow.

```bash
cp .env.example .env
docker compose build
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

The application runs at `http://localhost:8000`.

The Vite development server runs through the `node` service at `http://localhost:5173`.

Useful commands:

```bash
docker compose exec app php artisan test
docker compose run --rm node npm run build
docker compose run --rm app php artisan queue:work --tries=3
```

For a local Unraid deployment with an Unraid Docker template, see `docs/deployment-unraid.md`.

Seeded development accounts use the password `password`:

- `admin@example.com`
- `editor@example.com`
- `test@example.com`

## Screenshots

Screenshots will be added after the first UI milestone.

Planned screenshots:

- Public mod overview
- Mod detail page
- Submit mod form
- Admin moderation queue
- Category and rank management

## Roadmap Summary

- Phase 1: Foundation, Docker, auth, roles, base layout
- Phase 2: Mod core, submission, listing, detail pages, moderation states
- Phase 3: Admin/editorial area, categories, ranks
- Phase 4: Community features, ratings, comments, reports, profiles
- Phase 5: Security automation, VirusTotal API checks, jobs, CI, tests

## Portfolio Notice

OpenModHub is a public portfolio and learning project. It is designed to show practical full-stack engineering, clean architecture, documentation discipline, and maintainable Laravel/React development practices.
