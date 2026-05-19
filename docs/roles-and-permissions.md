# Roles and Permissions

OpenModHub uses three primary roles: Admin, Editor, and User. Server-side authorization is required for every privileged action.

## Admin

Admins have full access to the system.

Allowed actions:

- Create mods
- Edit any mod
- Delete any mod
- Approve mods
- Reject mods
- Manage categories
- Manage FAQs
- Manage users
- Block and unblock users
- Manage ranks
- Delete or moderate comments
- Review and handle reports
- Change global settings, including default language, legal page content, and Google Tag Manager container ID

## Editor

Editors manage submitted content and community moderation, but they should not have full system administration rights.

Allowed actions:

- Create mods
- Edit existing mod entries
- Approve mods
- Reject mods
- Delete or moderate comments
- Review and handle reports

Not allowed by default:

- Manage users
- Manage ranks
- Manage system-level settings
- Manage legal page content or tracking configuration

Category management may be limited to Admin in the MVP. If editors should manage categories later, the decision must be documented.

Current implementation: category and rank management are limited to Admin. Editors can access the moderation queue, approve or reject mods, moderate comments, and manage reports (resolve/dismiss), but cannot manage categories or ranks. User management is limited to Admin. Admins can edit user details, assign roles, change passwords, and block or unblock users. Admins cannot remove their own admin role, cannot block themselves, and the system prevents removing or blocking the last admin from the system.

Admins and editors see a global open-tasks indicator in the authenticated navigation for pending mods, pending mod versions, and pending reports.

## User

Users are standard authenticated community members.

Community write permissions require a verified email address. Blocked users cannot log in or continue using authenticated community actions.

Admins can define normal points-based ranks and special manually assigned ranks in `/admin/ranks`, and rank point rules in `/admin/rank-point-rules`. Admins can assign or remove special ranks in user management. Editors and regular users cannot manage ranks or point rules.

Allowed actions:

- Create mods
- Edit their own pending or rejected mods, depending on workflow rules
- View their own submitted mods
- Rate approved mods
- Comment on approved mods
- Report mods

Not allowed:

- Edit other users' mods
- Delete other users' mods
- Approve mods
- Reject mods
- Manage categories
- Manage users
- Manage ranks
- Moderate comments
- Handle reports

Unverified users may log in and request a new verification email, but cannot create mods, rate, comment, or report content.

## Permission Matrix

| Action | Admin | Editor | User |
| --- | --- | --- | --- |
| Create mods | Yes | Yes | Yes |
| Edit own mods | Yes | Yes | Limited |
| Edit any mod | Yes | Yes | No |
| Delete mods | Yes | No by default | No |
| Approve mods | Yes | Yes | No |
| Reject mods | Yes | Yes | No |
| Manage categories | Yes | No by default | No |
| Manage FAQs | Yes | No | No |
| Manage users | Yes | No | No |
| Manage ranks | Yes | No | No |
| Manage settings/legal/tracking | Yes | No | No |
| Delete/moderate comments | Yes | Yes | No |
| Handle reports | Yes | Yes | No |

## Implementation Notes

- Use Laravel Policies and Gates for authorization.
- Use Form Requests for request-level validation and authorization.
- Keep frontend permission checks as UX helpers only.
- Do not expose admin or editor actions without server-side checks.
