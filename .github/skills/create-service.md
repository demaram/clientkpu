# Skill: Create Service

## Purpose
Use Service to contain business logic that:
- Involves more than one Repository, or
- Is too complex for a Controller.

## Location & Naming
- Path: `app/Services/`
- Class name MUST end with `Service`

## Rules
1. NEVER query database directly — delegate all data operations to Repository
2. NEVER access `Request` object — accept data as plain `array` or DTO parameter
3. ALL methods MUST have return type declarations
4. ALWAYS inject repositories via constructor
5. ALWAYS wrap multiple write operations in `DB::transaction()`
6. External service calls (e.g. cURL/HTTP) MUST go through Repository, not Service directly

## Error Handling
- Do NOT add try-catch in Service — let exceptions bubble up to Controller
- Do NOT add logging in Service

## Complex Logic
- Keep it in ONE Service — do not split into multiple Services
- Break into `private` methods instead
- Declare private methods below the main public method
