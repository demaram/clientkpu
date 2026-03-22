# Skill: Create Controller

## Purpose
Handle HTTP request and return API response.
Validation stays in Controller — business logic stays in Service.

## Location & Naming
- Path: `app/Http/Controllers/`
- Class name MUST end with `Controller`

## Rules
- ALWAYS inject Repository and Service via constructor
- `use` import order: Repository first, then Service
- ALL methods MUST have return type declaration — e.g. `: JsonResponse`
- Validate input in Controller — NEVER in Service or Repository
- ALWAYS use Reqeuest class for validation 
- NEVER write business logic or database queries in Controller

## Flow
Request → Validate → Call Service/Repository → Return `successResponse`

## Route Registration
Register endpoint in the appropriate route file:
- `routes/api.php`
- `routes/apiAdms.php`
