# Product Requirements Document (PRD)

**Project:** Treyworks Search – Enhanced Logging Module  
**Date:** 2025-07-08  
**Author:** Cascade AI

---

## 1. Purpose
Upgrade the current file-based logging system in the `treyworks-search` WordPress plugin to a database-backed solution with an administrative interface for viewing and clearing logs. This will improve reliability, scalability, and maintainability of operational logs while giving administrators first-class tools to monitor and manage them.

## 2. Background / Problem Statement
The plugin currently writes operational logs to a flat text file. This approach has several drawbacks:
1. **Data Integrity & Scalability** – Large files become unwieldy and risk corruption.
2. **Limited Querying** – Text files cannot be filtered or searched efficiently from the WP admin area.
3. **Lack of Lifecycle Management** – No easy way for admins to purge or rotate logs.
4. **Security & Access** – File permissions can inadvertently expose logs containing sensitive data.

A database-level solution addresses these gaps and aligns with typical WordPress development best practices.

## 3. Goals & Objectives
1. **Migrate Logging Storage** – Persist all plugin log events in a dedicated MySQL table.
2. **Admin UI** – Provide an admin-only page under the Treyworks plugin menu to:
   - List logs with pagination, search, and filters (level, date range).
   - Clear individual or all log entries.
3. **Lifecycle Automation** – Create log table on plugin activation; remove it (with data) on uninstall.
4. **Backward Compatibility** – Retain the same logging API for calling code; only storage backend changes.

## 4. Functional Requirements
FR-1  The plugin **MUST** create table `wp_treyworks_search_logs` (prefix respected) on activation.

FR-2  Table schema **MUST** include at minimum:
- `id` BIGINT UNSIGNED PK AUTO_INCREMENT
- `log_level` VARCHAR(20) (e.g., info, warning, error)
- `message` TEXT
- `context` LONGTEXT NULL (JSON string)
- `created_at` DATETIME (default current timestamp)

FR-3  Logging helper function `treyworks_log( $level, $message, $context = [] )` **MUST** insert a row into the table.

FR-4  On uninstall, the plugin **MUST** drop the log table *after* confirmation from the user.

FR-5  Add **Admin Screen** `Treyworks Search ▸ Logs` accessible only to users with `manage_options` capability.

FR-6  Admin screen **MUST** provide:
- Tabular view with sortable columns (Date, Level, Message excerpt).
- Search box (full-text on Message & Context).
- Filters (Level dropdown, Date range picker).
- Bulk actions: *Delete selected*, *Delete all*.

FR-7  Actions **MUST** use nonces to prevent CSRF.

FR-8  All DB queries **MUST** use `$wpdb->prepare` to avoid SQL injection.

## 5. Non-Functional Requirements
NFR-1  Solution **MUST** maintain performance—UI loads ≤ 500 ms for 10 k log rows.

NFR-2  **Internationalization** – All UI strings loaded via `__()` for translation.

NFR-3  **Accessibility** – Admin table follows WP a11y guidelines (proper ARIA attributes, keyboard nav).

NFR-4  **Security** – Only privileged users can read or clear logs.

## 6. Assumptions / Constraints
- WordPress ≥ 6.0, PHP ≥ 7.4.
- MySQL database available (default WP stack).

## 7. Out of Scope
- Remote log shipping (e.g., to external services).
- Log rotation/archiving beyond manual clear-all.

## 8. Acceptance Criteria
AC-1  Activating plugin creates the log table with correct schema.

AC-2  Calling `treyworks_log()` writes entry that is visible in DB.

AC-3  Admin screen lists logs with pagination & filters.

AC-4  "Delete selected/all" removes rows and shows success notice.

AC-5  Deactivating then uninstalling plugin drops table after confirmation.

## 9. Success Metrics
- 0 critical errors writing to DB in first 30 days post-deploy.
- ≤ 50 ms average insert time for log write.
- Admin log page TTFB ≤ 500 ms for 10 k rows.

## 10. Open Questions
1. Should log retention policy (e.g., auto-purge after X days) be added now or future?
2. Do we need role-based access finer than `manage_options`?

---
End of document.
