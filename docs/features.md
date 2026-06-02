# Features

## Core Concept

OpenModHub is a moderated mod portal where users can submit mods. Submitted mods are not published immediately. They enter a review workflow and become public only after approval by an administrator or a user with moderation permissions.

## Mod Fields

Each mod should contain:

- Title
- Description
- Images or screenshots
- Optional MP3 audio preview for soundmods
- Optional privacy-aware YouTube preview
- External download link
- VirusTotal link or VirusTotal result
- Category or topic
- Status
- Creator/user
- Created date
- Updated date

Mods also support moderated release versions. Each version has a semantic Composer-style version number, changelog, external download link, optional MP3 audio file, optional privacy-aware YouTube preview, optional VirusTotal link, per-version download counter, and moderation status.

## Moderation Workflow

Mods should support at least these states:

- `pending`: submitted and waiting for review
- `approved`: reviewed and publicly visible
- `rejected`: reviewed and not published

Only approved mods should be visible in the public listing and public detail pages.

Current implementation:

- Users can submit mods into the `pending` state.
- Regular users can have only a limited number of pending mod submissions at the same time. Admins can configure the pending limit in settings; `0` means unlimited.
- Admins can temporarily block regular user mod submissions from settings. Admins and users with review permissions remain exempt from this global submission block.
- Public mod overview and detail pages only expose `approved` mods.
- Owners, admins, and users with review permissions may view non-public submitted mods.
- Admins and users with review permissions can review submitted mods in a moderation queue.
- Admins and users with review permissions can approve mods for publication.
- Admins and users with review permissions can reject mods with a rejection reason.
- New versions for approved mods enter the same review workflow before they become the current public download.

## Category Management

Categories and topics should be manageable in the admin area. This allows the portal structure to evolve without code changes.

Current implementation: initial active categories are seeded for development. Admins can create, edit, deactivate, delete unused categories, and reorder categories through drag-and-drop in the backend. Public category filters and submit forms use the configured category order.

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

### Direct Permissions

Non-admin users can receive direct permissions for limited privileged actions, such as reviewing mods, moderating comments, handling reports, or managing selected content areas.

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

Users can comment on mods. Comments can be moderated or deleted by administrators or users with comment moderation permission.

Current implementation: authenticated users can comment on approved mods. Visible comments are shown on mod detail pages. Admins and users with `moderate_comments` can hide, show, or delete comments from the detail page.

## Reports

Users can report mods. Reports should be visible in the admin moderation area. Admins and users with report handling permission can review, process, and close reports.

Current implementation: authenticated users can report approved mods with a reason (broken_link, malware, spam, other) and optional message. Reports appear in the admin reports management page. Admins and users with `handle_reports` can resolve or dismiss reports. Resolved and dismissed reports track the reviewer and review timestamp.

## Dashboard Metrics

Admins and users with moderation permissions should have a quick overview of moderation and community activity.

Current implementation:

- Admins and users with moderation permissions see moderation dashboard metrics for pending mods, pending mod versions, pending reports, visible comments, approved mods, and mods approved in the last 7 days.
- Admins and users with moderation permissions see a global open-tasks icon in the authenticated navigation. A badge is shown when mods, mod versions, or reports are waiting for review.
- Admins additionally see total users and new users in the last 7 days.
- Regular users see a simple authenticated dashboard without moderation metrics.

## User Management

Admins can manage users from the admin area.

Current implementation:

- Admins can view a list of all users at `/admin/users`.
- Admins can edit user name, email, language preference, and password.
- Admins can assign or remove roles for any user.
- Admins can block and unblock users with a mandatory reason and optional expiry date.
- Admins can permanently delete users with a confirmation dialog requiring username input.
- Admins cannot remove their own admin role.
- Admins cannot delete their own account.
- The system prevents removing the last admin role from the system.
- The system prevents blocking yourself and prevents blocking the last unblocked admin.
- Blocked users cannot log in or continue using authenticated community actions.
- Temporary blocks automatically expire after the configured date.

## Warning and Sanction System

Admins and moderators can issue warnings to users for rule violations. Warnings carry points and can trigger automatic sanctions.

Current implementation:

- Admins and users with the `moderate_users` permission can issue warnings with points, reason, and optional expiry date.
- Warnings expire after a configurable number of days (default: 90 days, configurable in `/admin/settings`).
- Removed warnings no longer count toward active points.
- Expired warnings no longer count toward active points.
- The admin user management page shows active warning points, warning history, and active sanctions per user.
- Warnings can be removed by admins and moderators.

### Automatic Sanctions

When a user's active warning points reach configurable thresholds, automatic sanctions are applied:

- **Upload ban**: Applied when active points reach the upload ban threshold (default: 5 points). The user can still log in but cannot submit mods or new versions. Duration is configurable (default: 7 days).
- **Account lock**: Applied when active points reach the account lock threshold (default: 10 points). The user cannot log in. Duration is configurable (default: 14 days).
- Sanction thresholds and durations are configurable in `/admin/settings`.
- When a user attempts to log in with an active account lock, they see a message with the expiry date and reason.
- When a user with an active upload ban attempts to create a mod, they see a message with the expiry date and reason.

### Manual Sanctions

Admins and moderators can also manually create sanctions (upload bans or account locks) with a reason and optional expiry date. Manual sanctions are managed from the user moderation panel.

## Login with Email or Username

Users can log in using either their email address or their username.

Current implementation:

- The login form accepts a single field labeled "Email or username".
- The backend detects whether the input is an email address or a username and authenticates accordingly.
- Usernames must be unique and consist of alphanumeric characters and underscores (3-30 characters).
- Error messages are neutral and do not reveal whether the email/username or password was incorrect.
- Password reset continues to work via email only.

## Moderation and Sanction Settings

Admins can configure warning and sanction parameters in `/admin/settings`:

- Warning expiry days (default: 90)
- Upload ban threshold in points (default: 5)
- Upload ban duration in days (default: 7)
- Account lock threshold in points (default: 10)
- Account lock duration in days (default: 14)

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
- Submitted versions are pending until approved by an admin or a user with review permissions.
- Approving a version automatically makes it the current download version, including pre-release versions.
- The mod detail page uses the current approved version for the primary download button and shows approved version history with changelogs and download links.

Current implementation: version media supports MP3 uploads for soundmods. Uploaded MP3 files may be displayed as preview audio and, for sound-only mods, may also serve as the moderated download asset when no external download URL is provided. Uploaded audio remains subject to the same moderation workflow as the version.

Current implementation: mods may include a YouTube preview URL on submitted versions. The application stores a validated canonical YouTube URL and extracted video identifier, then renders the preview with an explicit click-to-load step so YouTube is not contacted before visitor interaction.

## Permanent Mod Deletion

Admins and users with the `delete_any_mod` permission should be able to permanently delete mods when needed.

Planned update:

- The moderation UI should expose permanent deletion only, not a separate trash workflow.
- Permanent deletion must require a strong confirmation, such as typing the mod title.
- The backend must enforce the confirmation, not only the frontend.
- Related comments, ratings, reports, versions, security checks, media records, and uploaded files must be cleaned up.
- Only users with `delete_any_mod` may perform the action.

## FAQs

The application provides a public FAQ page with frequently asked questions. FAQs are managed in the admin area and support both English and German content.

Current implementation:

- Admins can create, edit, and delete FAQs in `/admin/faqs`.
- Each FAQ has separate question and answer fields for English and German.
- FAQs support a sort order for custom display ordering.
- FAQs can be activated or deactivated; only active FAQs appear on the public page.
- The public FAQ page at `/faqs` displays questions in an accordion-style layout.
- The displayed language automatically matches the current application locale.
- The FAQ link is visible in the public header navigation and footer.

## Email Notifications

The application sends styled email notifications to users for moderation events and email verification. All emails share a consistent layout with logo header, configurable body content, and legal footer.

Current implementation:

- Email templates are stored in the `email_templates` database table and managed in `/admin/email-templates`.
- Each template has separate subject and body fields for English and German.
- Templates support dynamic placeholders that are replaced when the email is sent.
- Blocked users (`blocked_at` set) do not receive moderation notifications.
- Notifications are queued via Laravel's queue system (`ShouldQueue`).

### Template Types

| Key | Trigger | Recipient |
|---|---|---|
| `verify_email` | User registration | New user |
| `mod_approved` | Mod approved by admin or reviewer | Mod owner |
| `mod_rejected` | Mod rejected by admin or reviewer | Mod owner |
| `version_approved` | Mod version approved | Version submitter |
| `version_rejected` | Mod version rejected | Version submitter |

### Placeholders

Placeholders vary by template type. The admin UI shows available placeholders for each template.

| Placeholder | Description |
|---|---|
| `{user_name}` | Recipient's name |
| `{site_name}` | Site name from branding settings |
| `{site_url}` | Application URL |
| `{verification_url}` | Email verification link (verify_email only) |
| `{mod_title}` | Mod title |
| `{mod_url}` | Link to the mod |
| `{mod_slug}` | Mod slug |
| `{version}` | Version number (version templates) |
| `{rejection_reason}` | Rejection reason (rejected templates) |
| `{reviewer_name}` | Name of the reviewing admin or reviewer |
| `{cta_text}` | Call-to-action button text |
| `{cta_url}` | Call-to-action button URL |

### Email Layout

All emails use a consistent table-based layout for email client compatibility:

- **Header**: Uploaded logo (from branding settings) and site name
- **Body**: Configurable template content with placeholder replacement
- **Footer**: Legal information from settings (operator, address, contact), copyright, site URL
- **CTA Button**: Optional call-to-action button rendered when `{cta_url}` and `{cta_text}` are present

### Email Verification

The default Laravel email verification uses the same styled email layout. The `User::sendEmailVerificationNotification()` method is overridden to use `VerifyEmailNotification`.

## Warning and Sanction System

Admins and moderators can issue warnings to users for rule violations. Warnings carry points and can trigger automatic sanctions.

Current implementation:

- Admins and users with the `moderate_users` permission can issue warnings with points, reason, and optional expiry date.
- Warnings expire after a configurable number of days (default: 90 days, configurable in `/admin/settings`).
- Removed warnings no longer count toward active points.
- Expired warnings no longer count toward active points.
- The admin user management page shows active warning points, warning history, and active sanctions per user.
- Warnings can be removed by admins and moderators.

### Automatic Sanctions

When a user's active warning points reach configurable thresholds, automatic sanctions are applied:

- **Upload ban**: Applied when active points reach the upload ban threshold (default: 5 points). The user can still log in but cannot submit mods or new versions. Duration is configurable (default: 7 days).
- **Account lock**: Applied when active points reach the account lock threshold (default: 10 points). The user cannot log in. Duration is configurable (default: 14 days).
- Sanction thresholds and durations are configurable in `/admin/settings`.
- When a user attempts to log in with an active account lock, they see a message with the expiry date and reason.
- When a user with an active upload ban attempts to create a mod, they see a message with the expiry date and reason.

### Manual Sanctions

Admins and moderators can also manually create sanctions (upload bans or account locks) with a reason and optional expiry date. Manual sanctions are managed from the user moderation panel.

## Login with Email or Username

Users can log in using either their email address or their username.

Current implementation:

- The login form accepts a single field labeled "Email or username".
- The backend detects whether the input is an email address or a username and authenticates accordingly.
- Usernames must be unique and consist of alphanumeric characters and underscores (3-30 characters).
- Error messages are neutral and do not reveal whether the email/username or password was incorrect.
- Password reset continues to work via email only.

## User Deletion

Admins can permanently delete user accounts.

Current implementation:

- Only admins can delete users.
- Admins cannot delete their own account.
- The system prevents deleting the last admin.
- Deletion requires explicit confirmation by typing the username.
- All user data (mods, comments, ratings, reports, warnings, sanctions) is permanently removed.

## Temporary Blocks

Admins can temporarily block users with an optional expiry date.

Current implementation:

- Block reason is mandatory.
- Optional expiry date can be set.
- Temporary blocks automatically expire after the configured date.
- The `EnsureUserIsNotBlocked` middleware checks both permanent and temporary blocks.
- Expired temporary blocks are automatically cleared on the next request.

## Moderation and Sanction Settings

Admins can configure warning and sanction parameters in `/admin/settings`:

- Warning expiry days (default: 90)
- Upload ban threshold in points (default: 5)
- Upload ban duration in days (default: 7)
- Account lock threshold in points (default: 10)
- Account lock duration in days (default: 14)
