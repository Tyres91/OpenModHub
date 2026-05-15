# AI Agent Instructions

This file defines working rules for future AI-assisted sessions in OpenModHub.

## Required Context First

- Always read the existing documentation before changing code.
- Start with `README.md`, then the relevant files in `docs/`.
- Respect documented architecture decisions and project boundaries.
- Do not introduce new features or change behavior without updating the matching documentation.
- For larger architectural or product decisions, update `docs/decisions.md`.

## Engineering Principles

- Keep changes small, reviewable, and easy to understand.
- Prefer Laravel conventions over custom abstractions.
- Avoid overengineering unless there is a clear product or maintainability reason.
- Keep business rules explicit and testable.
- Do not hardcode secrets, API keys, credentials, or environment-specific values.
- Use `.env` for configuration such as VirusTotal API keys.

## Laravel Guidelines

- Use controllers for HTTP flow, not business-heavy logic.
- Use Form Requests for validation and authorization where appropriate.
- Use Policies and Gates for permissions.
- Keep database changes in migrations.
- Add seeders or factories when useful for development and testing.
- Keep Eloquent relationships explicit and named clearly.

## Inertia and React Guidelines

- Keep Inertia pages in clear feature-oriented folders.
- Extract React components when they are reused or when a page becomes hard to read.
- Keep props passed from Laravel intentional and minimal.
- Use Tailwind CSS consistently and preserve the project design language.
- Avoid duplicating authorization logic in the frontend; frontend checks are only for UX.

## Security and Permissions

- Protect all privileged actions with server-side authorization.
- Admin/editor/user capabilities must be enforced through Policies, Gates, and/or Form Requests.
- Never rely only on hidden UI elements for access control.
- Validate external URLs such as download links and VirusTotal links.
- Treat file uploads and external links as untrusted input.

## Documentation Discipline

- Update `docs/features.md` when feature scope changes.
- Update `docs/architecture.md` when structure, services, or major technical patterns change.
- Update `docs/data-model.md` when tables, fields, or relationships change.
- Update `docs/roles-and-permissions.md` when permissions change.
- Update `docs/roadmap.md` when phases or priorities change.
- Update `docs/development-workflow.md` when workflow expectations change.
- Update `docs/decisions.md` for significant product or technical decisions.

## Review Expectations

- Explain what changed and why.
- Mention tests or checks that were run.
- If tests were not run, state why.
- Keep implementation notes concise and factual.
