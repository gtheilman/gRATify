# Architecture

This document is a high-level guide to how the application is organized and why key pieces are structured the way they are. It is intended for new maintainers who have not seen the codebase before.

## Overview

The application is a Laravel 12 API + SPA (admin/editor UI) with a separate student client. Key areas:

- **Laravel API**: Core domain logic, persistence, and authorization.
- **Admin/Editor SPA**: Vue-based UI for managing assessments, questions, answers, and viewing progress/scores.
- **Student Client**: Lightweight Vue app mounted at `/client` for participating in assessments and submitting attempts.

## Key Domain Concepts

- **Assessment**: A collection of questions tied to an instructor/owner. Includes a password used to access the student client.
- **Question**: Belongs to an assessment, ordered by `sequence`.
- **Answer**: Belongs to a question, ordered by `sequence`, with correctness flags.
- **Presentation**: A participant instance tied to a user_id and an assessment.
- **Attempt**: A response to an answer for a presentation.

## Back-End Structure (Laravel)

### Controllers (app/Http/Controllers)

Controllers are intentionally thin and delegate multi-step logic to services when complexity grows. Highlights:

- **AssessmentCrudController**: CRUD for assessments and editor-specific payload shaping.
- **AssessmentBulkController**: Bulk edit endpoint used by the editor (fast save-all behavior).
- **AssessmentProgressController**: Fetches progress views via a service that decrypts group labels.
- **PresentationController**: Public endpoints for student assessments and private scoring views.
- **AuthController**: Authentication endpoints and seeded admin reset detection.
- **AdminBackupController**: On-demand database backup download with explicit error handling.

### Services (app/Services)

- **AssessmentBulkUpdater**: Applies assessment/question/answer edits in a single transaction.
- **AssessmentProgressService**: Builds progress payloads and decrypts group labels when possible.
- **PresentationAssembler**: Builds presentation payloads for the student client.
- **Scoring**: Scoring logic is managed via a scheme registry and strategy pattern (see below).

### Resources (app/Http/Resources)

API responses use Resource classes to keep response shapes stable for the front-end.
These are particularly important for:

- Edit payloads (AssessmentEditResource)
- Progress payloads (AssessmentProgressResource)
- Scores (ScoredPresentationResource + nested score resources)

### Error Handling

The API uses a standardized error envelope:

```
{ "error": { "code": "...", "message": "..." } }
```

The envelope is generated in the base controller and in the exception handler to ensure consistent API behavior.

## Front-End Structure (Vue)

### Admin/Editor UI

Located under `resources/js/pages` and managed via Pinia stores:

- `stores/assessments.js`: assessment CRUD, bulk update, question/answer helpers
- `stores/auth.js`: session state and force-password-reset flag
- `stores/users.js`: admin user management

### Student Client

Located under `resources/js/gratclient`:

- Lightweight Vue app mounted at `/client` using its own router and axios configuration.
- Caches presentations and identifiers for continuity.
- Provides retry on attempt submission for poor network conditions.

## Adding a New Grading (Scoring) Scheme

Scoring schemes are defined with a strategy pattern and are resolved at runtime from config. The scoring pipeline builds an assessment snapshot that includes each question’s ordered attempts, then delegates to the selected strategy.

1. **Create a new strategy class** in `app/Services/Scoring/` implementing `ScoringStrategy`.
   - Implement `scoreQuestions(Collection $questions): array`.
   - Return `['questionScores' => [...], 'total' => ...]`.
   - Each question in the collection is expected to have an `attempts` relation already attached (ordered by `created_at`).
   - Use question IDs as keys in `questionScores` so the UI can join scores back to questions.

2. **Register the strategy** in the scoring config:

```
// config/scoring.php
return [
  'default' => 'geometric-decay',
  'schemes' => [
    'geometric-decay' => \App\Services\Scoring\GeometricDecayScoring::class,
    'linear-decay' => \App\Services\Scoring\LinearDecayScoring::class,
    'your-new-scheme' => \App\Services\Scoring\YourNewScheme::class,
  ],
];
```

3. **Expose it via the API**
   - The `/presentations/score-by-assessment-id` endpoint supports a `scheme` query param.
   - Invalid scheme values are rejected with a 422 error (see the centralized error envelope).
   - The public `score-by-credentials` endpoint uses the configured default scheme unless overridden.
   - If you want the UI to surface the new scheme, add it to the scoring scheme selector in the admin UI (search for scoring scheme selection logic in `resources/js/pages/assessments/scores.vue`).
   - Example scheme: `linear-decay-with-zeros` behaves like linear decay but returns 0 if the correct choice was the last possible answer.

4. **Add tests**
   - Add unit coverage for the new strategy.
   - Consider tests that validate attempt ordering effects (e.g., first-correct vs later-correct).
   - Update or add feature tests for score endpoints to ensure the scheme works with real attempts.
   - Existing scoring tests live in `tests/Feature/ScoreByAssessmentIdTest.php` and `tests/Unit/Scoring/*`.

5. **Implementation notes**
   - Scoring is routed through `App\Services\Scoring\PresentationScorer`, which attaches attempts to questions and assigns `score` fields on questions.
   - When scoring a list of presentations, the controller clones the assessment/questions per presentation to avoid cross-contamination of per-question state.

## Extending Shortlink Providers

Shortlinks are centralized in `app/Services/Shortlinks/ShortlinkService.php`. To add a new provider:

1. **Add provider credentials/config** in your `.env` and corresponding config file (e.g., `config/services.php`).
2. **Implement the provider call** inside `ShortlinkService::generateShortUrl()`:
   - Follow the existing Bitly/TinyURL patterns (API call, success check, error capture).
   - Return `[$shortUrl, $errorStringOrNull]` just like the current providers.
3. **Update provider selection order**:
   - Add the provider to the `providerOrder` array logic.
   - Respect the `preferredProvider` parameter so the editor can opt-in.
4. **Expose availability to the UI** (optional):
   - If the UI should toggle the provider, update the `/api/shortlink-providers` endpoint (routes/api.php) to include the new provider’s configured status.
5. **Add tests**:
   - Unit test the new provider fallback behavior.
   - Ensure failures fall back to the original URL and capture error strings.

## Testing Strategy

- **PHP Feature Tests**: Validate API contracts and behaviors for key flows.
- **Contract Tests**: Located in `tests/Contract` to lock down response shapes.
- **Client Tests**: Run with `npm run test:client`.

## Deployment Notes

- Ensure `.env` values are configured for mail, storage, and database.
- For production backups, verify that the database driver’s CLI tools are available.
- Seeded admin user exists by default; change credentials in production.
