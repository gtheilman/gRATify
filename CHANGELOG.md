# Changelog

## v0.3.53-beta
- Cleaning up "noise" from 401 client calls

## v0.3.5-beta
- Put "operational warnings" panel behind a url variable
- Accept ?showOperationalSignals=1 (also true or yes)
- Trying to eliminate client calls to /auth/me (401 noise)

## v0.3.4-beta
- Debounced some client requests to reduce api noise

## v0.3.3-beta
- Fixing more eslint errors
- Trying to override local "dark mode" browser settings
- Fixing 429 too many requests

## v0.3.2-beta
- Removed remaining Sanctum package/config references and kept authentication on `auth:web`.
- Completed ESLint 9 migration follow-up and enabled additional promise safety rules (`promise/always-return`, `promise/catch-or-return`).
- Applied mechanical frontend lint cleanup required by the stricter ruleset.
- Re-ran security checks (`composer audit --locked`, `npm audit`) and full test/build verification.

## v0.3.1-beta
- Tried to tighten up database requests to minimize memory use

## v0.3.0-beta
- Added "Appeals" process

## v0.2.1-beta
- Changed picture on login page to avoid potential copyright questions
- Adjusted timing on when warning to not close tab comes up.
- Fixed some remaining migration problems.


## v0.2.0-beta
- Configured optimizations to database.  Involved changing the database so I'm upping the version number
- Put in a check to warn people that they may need to run php artisan migrate again.

## v0.1.0-beta.3
- Added asynchronous buffer where students can continue to answer questions when Internet goes down.  If their browser does not support IndexedDB, application will fall back to "wait for the server to confirm it received each click" behavior.
- Updated the browser tab title to “gRATify - TBL Group Assessments”.
- Served privacy/terms pages as HTML via /privacy and /terms routes with a shared legal view.
- Got rid of unused Sanctum settings and made sure everything used auth:web


## v0.1.0-beta.2

- Linked the privacy note from the login page footer.
- Added a short public TERMS.md and linked it from the login page.
- Clarified that users are responsible for avoiding student-identifiable data in TERMS.md.
- Added a liability disclaimer for the author in TERMS.md.
- Set LARAVEL_BYPASS_ENV_CHECK for vitest runs to avoid laravel-vite-plugin CI checks.

- Added PHPStan (Larastan) to CI and release workflows.
- Fixed linear decay scoring
- Added client and pest tests.
- Standardize API response resources for all non‑trivial endpoints (e.g., AssessmentProgressResource), avoiding ad‑hoc fields on models.  
- Standardized user/question/attempt API payloads with explicit resources, ensured question create returns refreshed sequence, and added coverage for auth/me and scoring response shapes.
- Making code more manageable 
- Consolidated question/answer ordering in model relationships and removed duplicate sorting in controllers.
- Added regression tests for ordering and scoring behavior around presentations/assessments.
- Routed score-by-credentials through the shared scoring pipeline.
- Refactored completed presentations list to use eager loading and query filters.
- Removed unused legacy controller state and dead methods.
- Standardized auth handling via controller middleware and added enforcement tests.
- Extracted shortlink generation into a service with unit tests for provider fallback behavior.
- Normalized API error responses to a consistent error envelope for unauthenticated/forbidden/not-found/locked cases.
- Updated client error parsing to recognize the error envelope and added API error utility tests.
- Hardened API client error handling to auto-parse error envelopes with test coverage.
- Extended error parsing to surface validation errors and updated direct-fetch flows to use shared parsing utilities.
- Extracted bulk assessment upsert logic into a reusable service with unit coverage.
- Added client test execution to CI and release workflows.
- Added validation for scoring scheme query param with clearer 422 errors.
- Extracted presentation scoring into a dedicated service with unit coverage.
- Normalized UI error messaging to avoid [object Object] displays and added utility tests.
- Decrypted assessment progress group labels with fallback for legacy plaintext values.
- Added a Presentation list resource for the completed presentations endpoint.
- Centralized API error codes/messages in a shared Errors helper.
- Extracted assessment progress and presentation assembly into dedicated services with unit tests.
- Split assessment responsibilities into dedicated CRUD/Bulk/Progress controllers.
- Added a bulk update DTO to make validation mapping explicit and testable.
- NPM audit fix for security vulnerabilities in lodash and tar  
- Adde README instructions for clearing caches in Docker Compose. Using PWD to simplify copy/paste of commands.  
- CHANGELOG  
- Added CHANGELOG  
- Optimized page load performance on home and login pages.
- Standardized assessment page font sizing for consistency.
- Replaced several large image assets with WebP versions to reduce payload size.
- Corrected scoring page "hanging" by altering logic in `PresentationController`.
- Updated app branding/logo assets in UI templates.

## v0.1.0-beta.1

- Baseline  
