# Roles and Permissions

OpenModHub uses a permission-based authorization system with one primary role: Admin.

## Admin Role

The Admin role is the only role in the system. Admin users automatically have all permissions.

Allowed actions (via automatic all-permissions grant):

- All system operations
- User management
- Settings management
- Content management
- Moderation tasks

## Permissions

All other users (non-admin) receive explicit permissions assigned individually. Every user automatically has basic community rights (create mods, rate, comment, report) without needing explicit permissions.

### Permission Groups

#### Moderation
- `review_mods` - Approve or reject mod submissions
- `moderate_comments` - Delete or hide comments
- `handle_reports` - Review and resolve reports
- `moderate_users` - Issue and remove warnings, create and remove sanctions, view user moderation data

#### Content Management
- `edit_any_mod` - Edit any mod (not just own)
- `delete_any_mod` - Permanently delete any mod
- `manage_categories` - Create, edit, delete, and reorder categories
- `manage_faqs` - Manage FAQ entries

#### Community
- `manage_ranks` - Create, edit, delete ranks and point rules
- `manage_users` - Edit users, assign roles, block/unblock, permanently delete users

#### System
- `manage_settings` - Change global settings, legal pages, tracking configuration

## Standard User Rights

All authenticated users with verified email automatically have:

- Create mods
- Edit own pending/rejected mods
- Rate approved mods
- Comment on approved mods
- Report content

Blocked users cannot perform any authenticated actions.

## Implementation

- Use `hasPermission()` method on User model
- Admin users automatically pass all permission checks
- Policies check permissions, not roles
- Frontend permission checks are UX helpers only
