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


### Rules for generating issue.md:
1. **Use Bahasa Indonesia** — write in Indonesian, as if explaining to a junior developer or a basic AI model.
2. **Use structured bullet points or numbered lists** — avoid long paragraphs.
3. **Keep it concise** — include only what is necessary to understand and implement the task. Do not over-explain.
4. **Include a logical problem-solving flow** — describe the steps to solve the problem in order, not the full code.
5. **For each problem found, always include a solution** — do not just describe the problem without a fix.
6. **Do NOT write full code** inside issue.md — describe what needs to be done, not how to write every line.

### After generating issue.md:
- Ask the user: *"Does this issue.md match your requirements? Is there anything missing or unclear?"*
- If the user confirms it is correct, prompt them to push the `issue.md` file to the repository.
