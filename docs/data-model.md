# Data Model

This document proposes the initial database model. Field names may change during implementation, but changes should be reflected here.

Implementation note: Phase 2 has implemented `categories`, `mods`, and `mod_images`. Phase 4 has implemented `ratings`, `comments`, and `reports`. Phase 5 has implemented `security_checks` for optional VirusTotal automation and `mod_versions` for moderated releases.

## `users`

Purpose: stores authenticated users.

Important fields:

- `id`
- `name`, unique alphanumeric with underscores
- `email`
- `password`
- `email_verified_at`
- `blocked_at`, nullable timestamp for admin account blocks
- `blocked_until`, nullable timestamp for temporary blocks (null means permanent)
- `blocked_by`, nullable user reference to the admin who blocked the account
- `block_reason`, nullable text with the admin-provided block reason
- `locale`, nullable string for user language preference (null means system default)
- `rank_id`, nullable special rank reference. When set to a rank marked as special, automatic rank calculation does not override it.
- `created_at`
- `updated_at`

Relationships:

- Has many `mods`

Implementation note: categories are ordered by `sort_order`, then name and id for stable display. Admin-managed drag-and-drop updates the stored order.
- Has many `ratings`
- Has many `comments`
- Has many `reports`
- Has many `warnings`
- Has many `sanctions` (UserSanction)
- Belongs to many `roles`, if a pivot-based role model is used

## `roles` and `role_user`

Purpose: stores roles if no permission package is used.

Important fields for `roles`:

- `id`
- `name`
- `slug`
- `created_at`
- `updated_at`

Important fields for `role_user`:

- `user_id`
- `role_id`

Relationships:

- A user can have one or more roles
- A role can belong to many users

Implementation decision: the foundation uses `roles` with a `role_user` pivot table instead of a single `role` column on `users`. This keeps the MVP simple while allowing a user to receive multiple roles later if needed.

## `mods`

Purpose: stores submitted and approved mod entries.

Important fields:

- `id`
- `user_id`
- `category_id`
- `title`
- `slug`
- `description`
- `external_download_url`
- `virus_total_url`
- `download_clicks_count`, aggregate external download link clicks deduplicated per session and mod
- `status`, such as `pending`, `approved`, `rejected`
- `rejection_reason`, nullable
- `approved_at`, nullable
- `reviewed_by`, nullable user reference
- `created_at`
- `updated_at`

Relationships:

- Belongs to `user`
- Belongs to `category`
- Has many `mod_images`
- Has many `ratings`
- Has many `comments`
- Has many `reports`
- Has many `security_checks` or `virus_total_scans`
- Has many `mod_versions`
- Has one current approved `mod_version`

Implementation note: `external_download_url`, `virus_total_url`, and `download_clicks_count` remain on `mods` for backwards compatibility and aggregate sorting. New release-specific behavior uses `mod_versions`.

## `mod_versions`

Purpose: stores moderated releases for a mod.

Important fields:

- `id`
- `mod_id`
- `submitted_by`, nullable user reference
- `version`, display version such as `v1.2.0-beta1`
- `normalized_version`, Composer-normalized version for uniqueness and comparison
- `changelog`
- `external_download_url`
- `audio_file_path`, nullable local MP3 path for soundmod preview/download media
- `audio_original_name`, nullable original uploaded audio filename
- `audio_mime`, nullable uploaded audio MIME type
- `audio_size`, nullable uploaded audio size in bytes
- `youtube_preview_url`, nullable validated YouTube preview URL
- `youtube_video_id`, nullable extracted YouTube video identifier for safe embeds
- `virus_total_url`, nullable
- `download_clicks_count`, aggregate external download clicks deduplicated per session and version
- `status`, such as `pending`, `approved`, `rejected`
- `rejection_reason`, nullable
- `approved_at`, nullable
- `reviewed_by`, nullable user reference
- `is_current`, marks the currently offered public download version
- `created_at`
- `updated_at`

Relationships:

- Belongs to `mod`
- Belongs to submitting `user`
- May belong to reviewing `user`
- Has many `security_checks`

Implementation note: Existing mods are backfilled into `1.0.0` versions. The migration is additive and does not delete or recreate users, mods, comments, ratings, reports, or roles.

Media note: MP3 and YouTube preview fields belong to `mod_versions` so previews and soundmod downloads follow the same moderation workflow as downloadable releases. YouTube embeds are derived from stored IDs instead of user-provided iframe HTML.

## `categories`

Purpose: stores admin-managed categories or topics for mods.

Important fields:

- `id`
- `name`
- `slug`
- `description`, nullable
- `is_active`
- `sort_order`, integer for admin-managed display order
- `created_at`
- `updated_at`

Relationships:

- Has many `mods`

## `mod_images`

Purpose: stores screenshots or image references for mods.

Important fields:

- `id`
- `mod_id`
- `mod_version_id`, nullable version reference
- `file_path`, nullable path for uploaded screenshots stored on the public disk
- `url`, nullable external/image URL fallback
- `alt_text`, nullable
- `sort_order`
- `created_at`
- `updated_at`

Relationships:

- Belongs to `mod`
- May belong to `mod_version`

## `ratings`

Purpose: stores user ratings for mods.

Important fields:

- `id`
- `user_id`
- `mod_id`
- `score`
- `created_at`
- `updated_at`

Relationships:

- Belongs to `user`
- Belongs to `mod`

Constraints:

- Unique combination of `user_id` and `mod_id`
- `score` should be constrained to the selected rating scale, for example 1 to 5

Implementation note: Phase 4 implements `ratings` with a unique `user_id` and `mod_id` pair. Submitting another rating updates the existing row.

## `comments`

Purpose: stores user comments on mods.

Important fields:

- `id`
- `user_id`
- `mod_id`
- `body`
- `status`, such as `visible`, `hidden`, `deleted`
- `moderated_by`, nullable user reference
- `moderated_at`, nullable
- `created_at`
- `updated_at`

Relationships:

- Belongs to `user`
- Belongs to `mod`
- May belong to a moderator user through `moderated_by`

Implementation note: Phase 4 implements visible and hidden comments. Admins and users with `moderate_comments` can hide or delete comments.

## `reports`

Purpose: stores user-submitted reports for mods.

Important fields:

- `id`
- `user_id`
- `mod_id`
- `reason`, such as `broken_link`, `malware`, `spam`, `other`
- `message`, nullable text
- `status`, such as `pending`, `resolved`, `dismissed`
- `reviewed_by`, nullable user reference
- `reviewed_at`, nullable
- `created_at`
- `updated_at`

Relationships:

- Belongs to reporting `user`
- Belongs to `mod`
- May belong to a reviewer user through `reviewed_by`

Implementation note: Phase 4 implements reports for approved mods only. Admins and users with `handle_reports` can resolve or dismiss reports. Each report tracks the reviewer and review timestamp.

## `ranks`

Purpose: stores backend-managed user ranks based on the number of published mods.

Important fields:

- `id`
- `name`
- `required_published_mods`, legacy threshold retained for compatibility
- `required_points`
- `color`
- `icon`, nullable
- `is_special`, boolean
- `created_at`
- `updated_at`

Relationships:

- Normal ranks are calculated through `RankService` based on points
- Special ranks may be assigned directly to users through `users.rank_id`

Implementation note: Phase 3 has implemented the `ranks` table and admin-managed rank definitions. User-facing normal rank calculation/display is derived through `RankService` and shown on public user profiles, mod cards, and mod detail pages. `users.rank_id` is reserved for manually assigned special ranks and is not used for automatic normal ranks.

## `rank_point_rules`

Purpose: stores admin-managed point rules for automatic rank calculation.

Important fields:

- `id`
- `key`, unique identifier such as `comment_created`, `approved_mod`, `approved_version`, `download_threshold`, `rating_received`, or `rating_average_bonus`
- `label`
- `points`
- `threshold`, nullable integer used by threshold-based rules such as downloads or minimum rating count
- `is_enabled`
- `created_at`
- `updated_at`

Implementation note: points are calculated retroactively from current user activity. Changing a rule immediately affects automatic rank calculation without writing historical point ledger rows. The high-average rating bonus currently uses an average rating of at least 4.5 and `threshold` as the minimum number of ratings.

## `settings`

Purpose: stores global application settings as key-value pairs.

Important fields:

- `id`
- `key`, unique string
- `value`, nullable text
- `created_at`
- `updated_at`

Current settings:

- `default_locale`: the global default language, such as `en` or `de`
- `google_tag_manager_id`: optional GTM container ID such as `GTM-XXXXXXX`
- `site_logo_path`: optional local public disk path for the uploaded site logo
- `site_logo_text`: optional text displayed next to the logo
- `site_logo_show_text`: boolean-like string (`1` or `0`) controlling whether logo text is shown
- `legal_site_name`: optional site/operator display name for legal pages
- `legal_operator_name`: optional operator name for legal pages
- `legal_operator_address`: optional operator address for legal pages
- `legal_operator_email`: optional contact email for legal pages
- `legal_operator_phone`: optional contact phone number for legal pages
- `legal_responsible_person`: optional responsible person for legal pages
- `legal_vat_id`: optional VAT ID for legal pages
- `legal_dispute_resolution`: optional dispute resolution text for legal pages
- `legal_privacy_contact`: optional privacy contact details
- `legal_privacy_additional_info`: optional additional privacy policy text
- `mod_submissions_blocked`: boolean-like string (`1` or `0`) that blocks regular user mod submissions when enabled
- `mod_pending_submission_limit`: integer string for the number of pending mods a regular user may have; `0` means unlimited

Implementation note: the `Setting` model provides `Setting::get($key, $default)` and `Setting::set($key, $value)` helpers. Only admins can modify settings. Legal and tracking configuration uses the existing key-value table rather than adding dedicated tables.

## `security_checks`

Purpose: stores security check metadata for submitted mods.

Important fields:

- `id`
- `mod_id`
- `provider`, such as `virustotal`
- `status`, such as `not_submitted`, `pending`, `clean`, `suspicious`, `failed`
- `external_url`, nullable
- `analysis_id`, nullable VirusTotal analysis identifier
- `result_summary`, nullable
- `raw_response`, nullable JSON
- `checked_at`, nullable timestamp for completed or failed checks
- `created_at`
- `updated_at`

Relationships:

- Belongs to `mod`

Implementation note: automated VirusTotal checks create security check rows for submitted mod download URLs. If VirusTotal is disabled or missing an API key, a `not_submitted` row is stored. Completed results are stored as `clean` or `suspicious`; failed API calls are stored as `failed`. These records support moderation context only and do not change mod approval state automatically.

## `faqs`

Purpose: stores frequently asked questions with multilingual content.

Important fields:

- `id`
- `question_en`, English question text
- `question_de`, German question text
- `answer_en`, English answer text
- `answer_de`, German answer text
- `sort_order`, integer for display ordering
- `is_active`, boolean to show or hide the FAQ
- `created_at`
- `updated_at`

Relationships:

- No direct relationships; FAQs are standalone content entries

Implementation note: The `Faq` model provides `getQuestion()` and `getAnswer()` helper methods that return the localized content based on the current application locale. Only active FAQs are shown on the public page. Admins can manage FAQs in `/admin/faqs` with full CRUD operations.

## `email_templates`

Purpose: stores configurable email notification templates with multilingual content.

Important fields:

- `id`
- `key`, unique identifier such as `verify_email`, `mod_approved`, `mod_rejected`, `version_approved`, `version_rejected`
- `subject_en`, English email subject
- `subject_de`, German email subject
- `body_en`, English email body (Blade-compatible with placeholders)
- `body_de`, German email body (Blade-compatible with placeholders)
- `is_active`, boolean to enable or disable the template
- `created_at`
- `updated_at`

Relationships:

- No direct relationships; templates are looked up by key

Implementation note: The `EmailTemplate` model provides `getSubject($locale)`, `getBody($locale)`, and `renderBody($data, $locale)` helpers. Inactive templates fall back to hardcoded defaults in `EmailTemplateService`. Templates support dynamic placeholders such as `{user_name}`, `{mod_title}`, `{rejection_reason}`, and `{cta_url}`. The `EmailTemplate::PLACEHOLDERS` constant defines which placeholders are available per template key. Admins manage templates in `/admin/email-templates`.

## `warnings`

Purpose: stores user warnings issued by admins or moderators for rule violations.

Important fields:

- `id`
- `user_id`, foreign key to the warned user
- `points`, unsigned integer warning points
- `reason`, text explanation
- `issued_by`, foreign key to the admin/moderator who issued the warning
- `status`, one of `active`, `expired`, `removed`
- `removed_by`, nullable foreign key to the admin/moderator who removed the warning
- `removed_at`, nullable timestamp
- `expires_at`, nullable timestamp for automatic expiry
- `created_at`
- `updated_at`

Relationships:

- Belongs to `user`
- Belongs to `issuer` (User)
- May belong to `remover` (User)

Implementation note: Active warnings contribute to the user's active warning points. When active points reach configurable thresholds, automatic sanctions (upload ban, account lock) are applied via `WarningService`.

## `user_sanctions`

Purpose: stores upload bans and account locks applied to users, either automatically through warning thresholds or manually by admins/moderators.

Important fields:

- `id`
- `user_id`, foreign key to the sanctioned user
- `type`, one of `upload_ban`, `account_lock`
- `reason`, text explanation
- `issued_by`, foreign key to the admin/moderator who created the sanction
- `expires_at`, nullable timestamp for automatic expiry
- `removed_by`, nullable foreign key to the admin/moderator who removed the sanction
- `removed_at`, nullable timestamp
- `created_at`
- `updated_at`

Relationships:

- Belongs to `user`
- Belongs to `issuer` (User)
- May belong to `remover` (User)

Implementation note: Active upload bans prevent mod submissions. Active account locks prevent login. The `EnsureUserIsNotBlocked` middleware checks both the legacy `blocked_at` field and active `account_lock` sanctions.
