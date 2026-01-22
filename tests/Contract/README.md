# API Contract Tests

These tests lock down API response shapes that the frontend depends on.
If a response changes, update both the backend and the corresponding frontend usage,
then adjust the contract test to match the new intentional shape.

Guidelines:
- Prefer asserting keys/structure and critical fields.
- Avoid asserting transient fields unless required by the UI.
- Keep the setup minimal so failures are easy to understand.
