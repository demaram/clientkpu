# Client KPU — Development Environment

> See also: `/var/www/html/CLAUDE.md` for full server instructions.

## Project Identity

| Key | Value |
|---|---|
| **Project** | Client KPU |
| **Environment** | DEVELOPMENT — safe to experiment |
| **Local Folder** | `/var/www/html/clientkpudev` |
| **GitHub Repo** | `demaram/clientkpu` |
| **Git Branch** | `development` |
| **URL** | http://client-app-dev.kpusahatama.id (HTTP only) |
| **Database** | as set in .env |
| **Max Upload** | 15MB (nginx config) |

## Artisan Commands

```bash
docker exec php83dev php /var/www/html/clientkpudev/artisan <command>
```

Common commands:
```bash
docker exec php83dev php /var/www/html/clientkpudev/artisan migrate
docker exec php83dev php /var/www/html/clientkpudev/artisan migrate:rollback
docker exec php83dev php /var/www/html/clientkpudev/artisan cache:clear
docker exec php83dev php /var/www/html/clientkpudev/artisan config:clear
docker exec php83dev php /var/www/html/clientkpudev/artisan queue:restart
docker exec php83dev php /var/www/html/clientkpudev/artisan tinker
```

## Composer

```bash
docker exec php83dev composer install -d /var/www/html/clientkpudev
docker exec php83dev composer require <package> -d /var/www/html/clientkpudev
```

## Git Workflow

```bash
git pull origin development
git checkout -b feature/<name>
git push origin feature/<name>
# Open PR on GitHub to merge into development
```

## Logs

```bash
docker exec php83dev tail -f /var/www/html/clientkpudev/storage/logs/laravel.log
```

## Fix Permissions

```bash
docker exec php83dev chown -R www-data:www-data /var/www/html/clientkpudev/storage
docker exec php83dev chmod -R 775 /var/www/html/clientkpudev/storage
docker exec php83dev chmod -R 775 /var/www/html/clientkpudev/bootstrap/cache
```
