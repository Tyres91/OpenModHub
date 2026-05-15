# Project Overview

## Project Goal

OpenModHub is a moderated portal for game or application mods. Users can submit mods, editors can review them, and only approved mods are published publicly.

The project should be clean enough for a public GitHub portfolio while remaining practical and understandable. The target is not to build the largest possible platform, but a well-structured full-stack application with professional engineering habits.

## Target Audience

- Mod creators who want to submit and present their work
- Users who want to discover approved mods in a curated portal
- Editors who review submitted content before publication
- Administrators who manage users, categories, ranks, reports, and moderation
- Recruiters or developers reviewing the repository as a portfolio project

## Why Not WordPress

OpenModHub should not be a WordPress system because the project goal is to demonstrate custom application architecture, modern full-stack development, and explicit business rules.

WordPress can be useful for content-heavy websites, but this project needs a domain-specific workflow around moderation, ranks, reports, ratings, permissions, and future security checks. A custom Laravel application makes these rules easier to model, test, and evolve.

## Why Laravel, Inertia, and React

Laravel provides a mature backend framework with routing, Eloquent ORM, migrations, queues, policies, validation, authentication, and testing support.

Inertia.js allows the project to keep Laravel as the server-side application framework while using React for modern frontend pages. This avoids the complexity of maintaining a separate REST API for the MVP while still enabling a rich UI.

React is used for interactive pages and reusable UI components. Tailwind CSS keeps styling fast, consistent, and maintainable.

## Portfolio Goal

The repository should show:

- Clear documentation
- Intentional architecture decisions
- Clean Laravel conventions
- Practical React/Inertia usage
- A real moderation workflow
- Secure role-based authorization
- A realistic database model
- A roadmap that can be implemented in small, reviewable steps

## Project Boundary

OpenModHub is not planned as a file-hosting platform. Mods will use external download links instead of storing mod archives directly in the application.

The application may store screenshots or image references, depending on the final implementation. Download files themselves should remain externally hosted. This keeps the MVP focused on moderation, discovery, metadata, and community features instead of storage, bandwidth, malware liability, and file distribution complexity.
