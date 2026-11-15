# Growthtopia Release Notes

Growthtopia is an educational Agriculture Information System. Use test data only; the application is not verified for production use.

**v2.5.1** is the latest published GitHub Release. **v2.5.2** is committed locally, but this checkout has no v2.5.2 tag.

## v2.5.2 — committed locally; no tag in this checkout

### Added
- Rebranded the application as Growthtopia with a new original pixel-inspired lock-and-sprout mark and favicon.
- Added inline, accessible validation feedback for invalid or missing form values, including red field feedback and a brief shake animation.
- Added administrator-managed global add, edit, and delete permissions for non-admin accounts.
- Added print-oriented project documentation covering project objectives, functional requirements, the ERD, normalization, dictionary, relationships, and user instructions.
- Added documented ERD relationships from the supplied normalized schema diagram.

### Updated
- Applied a pixel-inspired agricultural interface palette, block-style controls, and a readable pixel display font to headings and key controls.
- Restyled the existing loading and navigation skeletons with soil, leaf, and crop colors while retaining their existing behavior.
- Standardized administrator user edit and delete controls with the account-management layout and an in-app confirmation modal.
- Updated project documentation and repository links for the GrowthTopia GitHub repository.
- Kept the sign-in background video and application business workflows unchanged.

### Verification
- PHP syntax checks passed for all 27 changed PHP files, and `git diff --check` passed.
- The permission migration and authenticated database workflows have not been tested against Supabase.

## v2.5.1 — published GitHub Release

### Added
- Light and dark themes with a saved preference and a toggle on application, sign-in, and logout screens.
- Page-transition and dashboard loading skeletons.
- A live Philippines-time clock and Asia/Manila display formatting for activity timestamps.

### Updated
- Reduced dashboard loading delays and sidebar/navigation transitions.
- Combined shared-header notification reads into one query.
- Added manual GitHub Release instructions and a release-description template.

### Verification
- All 45 application PHP files passed `php -l`.
- `git diff --check` passed.
- The local dashboard theme toggle and persistence after page reload were checked.
- The local sign-in page rendered with its theme toggle.
- README relative links were checked.
- Full CRUD workflow testing and Render deployment verification were not completed.

## v2.5 — historical app label; no verified tag

### Updated
- Refreshed dashboard colors, card presentation, loading animation, and sidebar interactions.
- Improved harvest sorting and aligned the table and live-search controls.
- Replaced the archive permanent-delete browser prompt with an in-app confirmation and added checks for active child records.
- Updated Docker/Apache deployment configuration and local setup documentation.

### Added
- A browser-tab favicon.

## v2.4 — no verified tag

No v2.4 Git tag or published release was found in the repository history.

## v2.3 — no verified tag

No v2.3 Git tag or published release was found in the repository history.

## v2.2 — tagged release

### Updated
- Changed the application version label from v2.1 to v2.2.
- Updated README version references and versioning/rollback instructions.
- Added an unused bundled template folder to `.gitignore`.

No new application features were added in this release.

## v2.1 — tagged release

Initial tagged version of the Agriculture Information System.

### Added
- PHP and Supabase PostgreSQL application for managing farmers, farms, crops, plantings, and harvests.
- Sign-in, role-controlled administrator pages, dashboard summaries, search, and reports.
- Archive and restore workflows, notifications, and administrator activity history.
- Database reference tables, schema documentation, and setup instructions.
