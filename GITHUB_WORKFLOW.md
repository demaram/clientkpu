# GitHub Workflow — Client KPU

**Repository:** `demaram/clientkpu`

## Branch Structure

| Branch | Purpose | Deployed To |
|---|---|---|
| `main` | Production-ready code | `/var/www/html/clientkpu` → client-app.kpusahatama.id |
| `development` | Integration branch for dev | `/var/www/html/clientkpudev` → client-app-dev.kpusahatama.id |
| `feature/*` | Individual feature work | Local / PR to development |
| `fix/*` | Bug fixes | Local / PR to development |
| `hotfix/*` | Emergency prod fixes | PR directly to main + backport to development |

## Day-to-Day Development Flow

```
feature/your-feature
        |
        | Pull Request → review → merge
        ↓
   development  ←── always test here first
        |
        | Pull Request → review → merge (after QA on dev)
        ↓
      main  ←── triggers production deploy
```

### 1. Start a Feature

```bash
git checkout development
git pull origin development
git checkout -b feature/short-description
```

### 2. Develop & Commit

```bash
git add <files>
git commit -m "feat: describe what changed"
git push origin feature/short-description
```

### 3. Open a Pull Request → development

- Go to `github.com/demaram/clientkpu`
- Open PR: `feature/short-description` → `development`
- Test at http://client-app-dev.kpusahatama.id after merge

### 4. Deploy to Dev Server After Merge

```bash
# On the server, in /var/www/html/clientkpudev
git pull origin development
docker exec php83dev php /var/www/html/clientkpudev/artisan migrate
docker exec php83dev php /var/www/html/clientkpudev/artisan cache:clear
```

### 5. Promote to Production

After QA passes on dev:

```bash
# Open PR on GitHub: development → main
# After PR is merged:

# On the server, in /var/www/html/clientkpu — CONFIRM WITH USER FIRST
git pull origin main
docker exec php83dev composer install --no-dev -d /var/www/html/clientkpu
docker exec php83dev php /var/www/html/clientkpu/artisan migrate --force
docker exec php83dev php /var/www/html/clientkpu/artisan config:cache
docker exec php83dev php /var/www/html/clientkpu/artisan route:cache
docker exec php83dev php /var/www/html/clientkpu/artisan view:cache
docker exec php83dev php /var/www/html/clientkpu/artisan queue:restart
```

## Hotfix Flow (Emergency Production Fix)

```bash
git checkout main && git pull origin main
git checkout -b hotfix/short-description
git commit -m "fix: emergency fix description"
git push origin hotfix/short-description
# PR → main, then also PR → development to keep in sync
```

## Commit Message Convention

```
feat: add new feature
fix: fix a bug
refactor: refactor code without changing behavior
chore: update dependencies, configs
docs: update documentation
test: add or update tests
```

## Environment Files

- `.env` is **not committed** to git
- Use `.env.example` as a template
- Each environment (dev/prod) has its own `.env` on the server
- Max file upload: 15MB (enforced by nginx)
