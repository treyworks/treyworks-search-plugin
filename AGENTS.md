# AGENTS.md

## Project
Treyworks Search is a WordPress plugin that adds AI-powered search summaries and answer generation using Google Gemini.

## Stack
- PHP 8.1+
- WordPress 6.6+
- jQuery on the frontend
- REST API endpoints under `treyworks-search/v1`

## Key Areas
- `treyworks-search.php`: plugin bootstrap and REST handlers
- `includes/`: core classes, settings, logging, integrations
- `assets/js/`: frontend and admin scripts
- `assets/css/`: frontend and admin styles
- `templates/`: rendered PHP templates for UI

## Conventions
- Follow WordPress coding and security practices.
- Sanitize input, escape output, and verify capabilities/nonces.
- Keep CSS in external stylesheets and templates in PHP files.
- Make focused changes and avoid unrelated refactors.

## Notes for Agents
- Search flow: extract terms, query WordPress content, assemble AI response.
- Prefer updating existing hooks, endpoints, and settings patterns over adding new abstractions.
- If changing REST behavior, review frontend usage in `assets/js/treyworks-search.js`.
