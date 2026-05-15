# Decision Log

This file records significant product and technical decisions. New entries should be added when a decision affects architecture, scope, data model, security, or future development.

## Template

```text
## YYYY-MM-DD: Decision Title

Status: Proposed | Accepted | Superseded

Context:
Short description of the problem or tradeoff.

Decision:
The chosen direction.

Consequences:
Expected benefits, tradeoffs, and follow-up work.
```

## 2026-05-12: Use Laravel Instead of WordPress

Status: Accepted

Context:
The project needs custom moderation workflows, roles, ranks, reports, ratings, and future security checks. It is also intended as a full-stack portfolio project.

Decision:
OpenModHub will be implemented as a Laravel application instead of a WordPress-based system.

Consequences:
The project can model domain-specific workflows directly and demonstrate custom application architecture. More implementation work is required compared to configuring an existing CMS.

## 2026-05-12: Use Inertia.js Instead of a Classic REST API for the MVP

Status: Accepted

Context:
The MVP needs a modern React frontend, but a separate API application would add complexity before it is needed.

Decision:
Use Inertia.js to connect Laravel controllers with React pages for the MVP.

Consequences:
The project keeps a simpler monolithic architecture while still using React. A dedicated API can be introduced later if external clients or mobile apps become real requirements.

## 2026-05-12: Use External Download Links Instead of File Hosting

Status: Accepted

Context:
Hosting mod files introduces storage, bandwidth, abuse, and malware liability concerns.

Decision:
OpenModHub will store external download links instead of hosting mod files directly.

Consequences:
The MVP can focus on moderation, discovery, metadata, and community workflows. External links must be validated and treated as untrusted input.

## 2026-05-12: Use Admin, Editor, and User Roles

Status: Accepted

Context:
The platform requires different permission levels for administration, editorial review, and normal user participation.

Decision:
Use three initial roles: Admin, Editor, and User.

Consequences:
Permissions remain understandable for the MVP. Policies and Gates must enforce these roles server-side. More granular permissions can be introduced later if needed.

## 2026-05-12: Start VirusTotal as a Link Field and Prepare API Integration Later

Status: Accepted

Context:
Security information is useful for a mod portal, but full VirusTotal API automation adds complexity around API keys, queues, polling, and result storage.

Decision:
The MVP will include a VirusTotal link field. A future implementation may add `VirusTotalService`, background jobs, and scan result storage.

Consequences:
The MVP remains simpler while leaving a clear path for automation. API keys must be stored in `.env` when integration is implemented.

## 2026-05-12: Use Docker-First Development and GitHub Actions CI/CD

Status: Accepted

Context:
The project should be easy to run locally and suitable for a public GitHub portfolio. A reproducible setup and automated checks make the project easier to review and maintain.

Decision:
OpenModHub will use Docker and Docker Compose as the primary local development environment. GitHub Actions will be used for CI and later CD once a deployment target is selected.

Consequences:
Local development should avoid host-specific assumptions where practical. CI should verify backend tests, frontend builds, and formatting or static checks when added. CD should remain prepared but not overbuilt before the deployment target is known.

## 2026-05-12: Use React with TypeScript

Status: Accepted

Context:
The project is intended as a public portfolio project and should have maintainable frontend code as the React surface grows.

Decision:
Use React with TypeScript for Inertia pages and reusable components.

Consequences:
Frontend code gets stronger type checking and clearer contracts. Developers must keep shared types up to date as backend props evolve.

## 2026-05-12: Use Pivot-Based Roles Without a Permission Package for the MVP

Status: Accepted

Context:
The MVP needs Admin, Editor, and User roles, but a full permission package would add complexity before granular permissions are required.

Decision:
Use a `roles` table and `role_user` pivot table with Laravel Policies and Gates for authorization.

Consequences:
The implementation remains explicit and easy to understand. More granular permissions or a package can be introduced later if the role model outgrows the MVP needs.

## 2026-05-13: Consent-Gate Google Tag Manager and Configure Legal Content in Admin

Status: Accepted

Context:
The application needs optional tracking and public legal pages, but tracking must comply with DSGVO expectations and operator-specific legal details should not be hardcoded into source code.

Decision:
Store the Google Tag Manager container ID and legal page fields in the admin-managed `settings` table. Share the GTM ID to React, but load Google Tag Manager only after explicit analytics consent in the client-side consent banner. Do not render GTM scripts or noscript iframes in the initial server HTML.

Consequences:
Admins can manage legal and tracking configuration without code changes. Tracking stays disabled until consent is given. The implementation intentionally supports a GTM container ID instead of arbitrary scripts to reduce configuration and XSS risk.

## 2026-05-14: Require Email Verification and Layered Registration Bot Protection

Status: Accepted

Context:
Public registration can be abused by bots. The project needs account opt-in through email while keeping normal registration usable and avoiding captcha friction for first-time legitimate users.

Decision:
Use Laravel `MustVerifyEmail` for account opt-in and protect community write actions with the `verified` middleware. Add registration-specific bot protection through CSRF, a honeypot field, minimum form completion time, IP-based registration rate limiting, and Cloudflare Turnstile only after repeated failed registration attempts.

Consequences:
Bots may still create unverified rows if they pass basic validation, but they cannot interact with community features without email verification. Legitimate users usually avoid captcha unless they repeatedly fail registration. Production deployments must configure mail delivery and Turnstile keys if adaptive captcha is expected to activate.

## 2026-05-14: Store VirusTotal Results as Moderation Context Only

Status: Accepted

Context:
VirusTotal automation can help editors assess submitted external download URLs, but third-party scan results may be delayed, inconclusive, rate-limited, or incorrect.

Decision:
Store automated VirusTotal results in `security_checks` through queued submit and poll jobs. Display the latest status in mod and moderation screens, but do not automatically approve or reject mods based on scan results.

Consequences:
Editors keep explicit responsibility for publication decisions. The system gains useful security context without depending on VirusTotal availability for the core submission workflow.

## 2026-05-14: Use Moderated Mod Versions for Releases

Status: Accepted

Context:
Mods need clean release history, changelogs, and version-specific download links. Users also need alpha, beta, RC, and dev tags without allowing arbitrary unclear version labels. Existing users and content must remain intact.

Decision:
Add `mod_versions` as an additive release table. Validate versions with Composer-compatible semantic version parsing through `composer/semver`. New versions for approved mods are moderated before publication. Approving any version, including pre-release versions, makes it the current public download. Existing mods are backfilled into initial `1.0.0` versions without deleting or recreating users.

Consequences:
Release-specific data such as changelog, download link, VirusTotal context, and download count lives on versions. The legacy mod-level download fields remain for compatibility and aggregate sorting. Editors retain control over every new downloadable release.

## 2026-05-14: Calculate Normal Ranks from Retroactive Points and Preserve Special Ranks

Status: Accepted

Context:
Ranks need to be based on configurable community activity points instead of only published mod counts. Admins also need manually assigned special ranks that should not be overwritten by automatic rank calculation.

Decision:
Add `required_points` and `is_special` to ranks, add nullable `users.rank_id` for manually assigned special ranks, and add `rank_point_rules` for configurable activity points. Built-in point rules include visible comments, approved mods, approved new versions, download thresholds, received ratings, and high average rating bonuses. Calculate normal rank points dynamically and retroactively from current activity. If a user has a special rank assigned, `RankService` returns that rank regardless of calculated points.

Consequences:
Admins can change point values and thresholds without backfilling point ledger rows. User ranks update immediately based on the new rules. Special ranks remain stable until an admin removes or changes the manual assignment.
