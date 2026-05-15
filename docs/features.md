# Features

## Core Concept

OpenModHub is a moderated mod portal where users can submit mods. Submitted mods are not published immediately. They enter a review workflow and become public only after approval by an editor or administrator.

## Mod Fields

Each mod should contain:

- Title
- Description
- Images or screenshots
- External download link
- VirusTotal link or VirusTotal result
- Category or topic
- Status
- Creator/user
- Created date
- Updated date

Mods also support moderated release versions. Each version has a semantic Composer-style version number, changelog, external download link, optional VirusTotal link, per-version download counter, and moderation status.

## Moderation Workflow

Mods should support at least these states:

- `pending`: submitted and waiting for review
- `approved`: reviewed and publicly visible
- `rejected`: reviewed and not published

Only approved mods should be visible in the public listing and public detail pages.

Current implementation:

- Users can submit mods into the `pending` state.
- Regular users can have only a limited number of pending mod submissions at the same time. Admins can configure the pending limit in settings; `0` means unlimited.
- Admins can temporarily block regular user mod submissions from settings. Admins and editors remain exempt from this global submission block.
- Public mod overview and detail pages only expose `approved` mods.
- Owners, admins, and editors may view non-public submitted mods.
- Admins and editors can review submitted mods in a moderation queue.
- Admins and editors can approve mods for publication.
- Admins and editors can reject mods with a rejection reason.
- New versions for approved mods enter the same review workflow before they become the current public download.

## Category Management

Categories and topics should be manageable in the admin area. This allows the portal structure to evolve without code changes.

Current implementation: initial active categories are seeded for development. Admins can create, edit, deactivate, and delete unused categories in the backend.

## User Roles

New registrations require email verification before community actions are available. Registration is protected with Laravel CSRF protection, a hidden honeypot field, a minimum form completion time, rate limiting, and optional Cloudflare Turnstile after repeated failed attempts.

### Admin

Admins can:

- Access all areas
- View and manage users
- Delete mods
- Edit mods
- Approve or reject mods
- Manage categories
- Manage ranks
- Manage system settings, legal page content, and tracking configuration
- Manage comments and reports

### Editor

Editors can:

- Review submitted mods
- Approve mods
- Reject mods
- Edit existing mod entries
- Review comments and reports

### User

Users can:

- Submit mods
- View their own submitted mods
- Rate mods
- Comment on mods
- Report mods

Current implementation: standard user community actions require a verified email address. Unverified users can log in and access the verification prompt, but cannot submit mods, rate, comment, or report mods until verification is complete.

## Public Mod Browsing

The public area should include:

- Overview of all approved mods
- Search by title or description
- Filters by category, rating, or other useful criteria
- Detail page for each approved mod

Current implementation also tracks external download link clicks through an internal redirect route. A mod's total `download_clicks_count` is incremented at most once per Laravel session and mod, then the user is redirected to the external download URL. The counter is aggregate-only; no IP addresses, user agents, or per-user click records are stored for this feature.

Download clicks are tracked per mod version. The legacy mod-level counter is kept as an aggregate for sorting and backwards compatibility.

The public mod overview supports search, category filtering, and sorting by publication date, title, average rating, or download clicks. Each sortable field can be ordered ascending or descending.

## Rank System

Users can earn ranks based on the number of their published mods.

Current ranking is points-based. Admins can configure how many points comments, approved mods, approved new versions, download milestones, received ratings, and high average rating bonuses grant. Rule changes apply retroactively because points are calculated from current activity instead of stored as a ledger.

Example ranks:

- 10 published mods: Mod Collector
- 25 published mods: Trusted
- 50 published mods: Elite Modder
- 75 published mods: Mod Master
- 100 published mods: Modding Legend

Ranks should be manageable in the backend.

Rank fields:

- Name
- Required points
- Color
- Icon
- Special rank flag

The user's rank should be displayed in places such as the user profile and mod entries.

Current implementation: admins can manage rank definitions in `/admin/ranks` and point rules in `/admin/rank-point-rules`. Public user profiles are available at `/users/{user}` and show the user's current rank, points, published mod count, and approved mods. Mod cards and mod detail pages show the author's calculated rank when one applies. Admins can assign a special rank to a user from user management; special ranks are never overwritten by automatic points-based rank calculation. Built-in point rules cover visible comments, approved mod uploads, approved new versions, download thresholds, received ratings, and high average rating bonuses.

## Ratings

Users can rate mods. A user may rate a specific mod only once. The average rating should be shown on mod listings and detail pages.

Current implementation: authenticated users can rate approved mods from 1 to 5. Re-rating the same mod updates the existing rating instead of creating a duplicate. Average rating and rating count are shown in mod cards and detail pages.

## Comments

Users can comment on mods. Comments can be moderated or deleted by authorized editors or administrators.

Current implementation: authenticated users can comment on approved mods. Visible comments are shown on mod detail pages. Admins and editors can hide, show, or delete comments from the detail page.

## Reports

Users can report mods. Reports should be visible in the admin or editorial area. Admins and editors can review, process, and close reports.

Current implementation: authenticated users can report approved mods with a reason (broken_link, malware, spam, other) and optional message. Reports appear in the admin reports management page. Admins and editors can resolve or dismiss reports. Resolved and dismissed reports track the reviewer and review timestamp.

## Dashboard Metrics

Admins and editors should have a quick overview of moderation and community activity.

Current implementation:

- Admins and editors see editorial dashboard metrics for pending mods, pending mod versions, pending reports, visible comments, approved mods, and mods approved in the last 7 days.
- Admins and editors see a global open-tasks icon in the authenticated navigation. A badge is shown when mods, mod versions, or reports are waiting for review.
- Admins additionally see total users and new users in the last 7 days.
- Regular users see a simple authenticated dashboard without editorial metrics.

## User Management

Admins can manage users from the admin area.

Current implementation:

- Admins can view a list of all users at `/admin/users`.
- Admins can edit user name, email, language preference, and password.
- Admins can assign or remove roles for any user.
- Admins can block and unblock users.
- Admins cannot remove their own admin role.
- The system prevents removing the last admin role from the system.
- The system prevents blocking yourself and prevents blocking the last unblocked admin.
- Blocked users cannot log in or continue using authenticated community actions.
- User deletion is not yet implemented to avoid breaking mod/comment/report ownership.

## Localization and Language Switcher

The application supports multiple languages. The default language is English and can be changed to German (or other supported languages) in the admin settings.

Current implementation:

- Admins can set the global default language in `/admin/settings`.
- Authenticated users can choose their own language in their profile. Setting it to "System default" uses the admin-configured default.
- Guests can switch language via the language switcher, which stores the choice in the session.
- Locale priority: user profile locale > guest session locale > backend default locale > fallback `en`.
- Translation files are stored in `lang/en/messages.php` and `lang/de/messages.php`.
- All UI labels, navigation items, and flash messages use translation keys.
- The `LanguageSwitcher` component is visible in the authenticated layout and on public pages.

## Legal Pages and Tracking Consent

The application must provide legal information pages and optional analytics/tracking configuration without hardcoding operator-specific legal data in source code.

Current implementation:

- Admins can configure the Google Tag Manager container ID in `/admin/settings`.
- Admins can upload a local site logo in `/admin/settings`, configure optional logo text, and remove the uploaded logo again.
- Admins can configure imprint and privacy policy fields in `/admin/settings`.
- Admins can enable a local debug mode in `/admin/settings`; when enabled, newly registered users see their signed email verification URL on the verification prompt.
- Public legal pages are available at `/impressum` and `/datenschutz`.
- Google Tag Manager is loaded only on the client after explicit analytics consent.
- No Google Tag Manager script or noscript iframe is rendered in the initial server HTML.
- Users can reopen cookie settings from the footer.
- Consent is stored in the browser's `localStorage` under `openmodhub_cookie_consent`.

## VirusTotal

The application supports both a user-provided VirusTotal link and optional automated VirusTotal checks for submitted external download URLs.

Current implementation:

- API keys are configured through `.env` using `VIRUSTOTAL_ENABLED`, `VIRUSTOTAL_API_KEY`, and `VIRUSTOTAL_POLL_DELAY_SECONDS`.
- `VirusTotalService` isolates API submission and polling logic.
- `SubmitUrlToVirusTotalJob` submits download URLs to VirusTotal.
- `PollVirusTotalResultJob` stores the completed analysis result after the configured delay.
- `security_checks` stores the latest and historical status for submitted mods.
- Public mod details, user mod cards, and moderation cards show the latest security status.
- VirusTotal results are moderation context only and never auto-approve mods.

## Mod Versions and Changelog

Approved mods can receive new submitted versions. Version strings are validated with Composer-compatible semantic version parsing, including alpha, beta, RC, and dev pre-release tags such as `1.2.0-beta1` or `v2.0.0-RC1`.

Current implementation:

- New mod submissions create an initial version with version number and changelog.
- Existing mods are backfilled into initial `1.0.0` versions by migration without deleting users or mods.
- New versions for approved mods can be submitted by the mod owner.
- Submitted versions are pending until approved by an editor or admin.
- Approving a version automatically makes it the current download version, including pre-release versions.
- The mod detail page uses the current approved version for the primary download button and shows approved version history with changelogs and download links.
