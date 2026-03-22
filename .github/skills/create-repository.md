# Skill: Create Repository

## Purpose
Use Repository to contain all database query logic.
Controller and Service MUST NOT query the database directly.

## Location & Naming
- Path: `app/Repositories/`
- Class name MUST end with `Repository`

## Rules
1. ONLY place where Eloquent/Query Builder is used
2. NEVER place business logic here — only data retrieval and persistence
3. NEVER access `Request` object — accept data as plain `array` or scalar parameters
4. ALL methods MUST have return type declarations
5. ALWAYS select only needed columns — never use `SELECT *`
6. ALWAYS use `with()` for relationships — never lazy load inside loops
7. Use `whereIn()` instead of querying inside loops
8. Use `chunk()` or `cursor()` for processing large datasets
9. Aggregate operations (SUM, COUNT, AVG) MUST be done in database, not in PHP
10. External HTTP calls (cURL, API) that fetch data belong here, not in Service

## Error Handling
- Do NOT add try-catch in Repository — let exceptions bubble up
- Do NOT add logging in Repository
