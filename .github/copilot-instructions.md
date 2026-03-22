# Project Instruction

## Overview
Repository untuk handle Payroll 

## Stack
- PHP 8.2, Laravel 8.75
- Database: SQL Server
- Auth: JWT

## Runing php or artisan command
- Cek dulu docker yang ada
- Misal ada container php83dev yang running, jalankan dengan docker exec -it php83dev bash
- Setelah masuk ke container, change directory dan jalankan perintah php atau artisan seperti biasa,

## Folder Structure
| Path | Purpose |
|---|---|
| `app/Repositories` | Handles database queries and external microservice API calls |
| `app/Datatables` | Server-side datatable processing |
| `app/Http/Controllers` | Handles incoming API requests |
| `app/Models` | Represents database tables |
| `app/Services` | Contains business logic and coordinates repositories |
| `app/Jobs` | Background job processing |
| `app/Traits` | Reusable code components |

## Coding Standards (PSR-1 & PSR-12)
- Descriptive variable names — no `$h`, `$d`, `$tmp`
- `use` order: Repositories → Services → Traits
- All methods **must** have return type declarations
- Type hints **required** on private/protected method parameters
- 4+ parameters → use multi-line declaration

## Performance Rules
- Use eager loading (`with()`) — avoid N+1 queries
- No queries inside loops — use `whereIn` instead
- Always paginate large datasets
- Never `SELECT *` — select only needed columns
- Use `chunk()` or `cursor()` for bulk data processing
- Aggregate (SUM, COUNT, AVG) in **database**, not PHP
- Cache rarely-changing master data
- Short-circuit conditionals to skip unnecessary computation

## Skills
Before creating or modifying any class, always read the corresponding skill file first.

| Task | Skill File |
|---|---|
| Create / modify Controller | `.github/skills/create-controller.md` |
| Create / modify Service | `.github/skills/create-service.md` |
| Create / modify Repository | `.github/skills/create-repository.md` |
