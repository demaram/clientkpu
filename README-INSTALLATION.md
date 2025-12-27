# Portal Admin Dashboard - KP Usahatama

Portal admin dashboard menggunakan Laravel 12 dan AdminLTE 3.

## Teknologi yang Digunakan

- **Laravel 12** - PHP Framework
- **AdminLTE 3** - Admin Dashboard Template
- **PHP 8.3** - Via Docker container (php83dev)
- **Node 22** - Via Docker container (nodedev)
- **Bootstrap 5** - Frontend Framework
- **Vite** - Frontend Build Tool

## Instalasi

Semua dependencies telah diinstall menggunakan Docker containers:

```bash
# Laravel dan PHP dependencies
docker exec php83dev composer install --working-dir=/var/www/html/kpusahatama/client

# Node dependencies
docker exec nodedev npm install --prefix /var/www/html/kpusahatama/client

# Build assets
docker exec nodedev npm run build --prefix /var/www/html/kpusahatama/client
```

## Menjalankan Aplikasi

### Development Server

Laravel development server sudah berjalan di background:

```bash
docker exec -d php83dev php /var/www/html/kpusahatama/client/artisan serve --host=0.0.0.0 --port=8000
```

Akses aplikasi di: **http://localhost:8000**

### Routes yang Tersedia

- `/` - Halaman welcome Laravel
- `/login` - Halaman login
- `/register` - Halaman registrasi
- `/home` - Dashboard user (setelah login)
- `/admin/dashboard` - Dashboard admin dengan AdminLTE (setelah login)

## Fitur Dashboard Admin

Dashboard admin menggunakan AdminLTE dengan fitur:

- ✅ Authentication (Login/Register)
- ✅ Dashboard dengan statistik cards
- ✅ Responsive design
- ✅ Sidebar navigation dengan menu:
  - Dashboard
  - Users Management
  - Settings
  - Profile
  - Logout

## Database

Aplikasi menggunakan SQLite database yang sudah dibuat otomatis di:
```
database/database.sqlite
```

Migrasi sudah dijalankan dengan tabel:
- users
- cache
- jobs

## Konfigurasi

File konfigurasi utama:
- `.env` - Environment configuration
- `config/adminlte.php` - AdminLTE configuration
- `config/app.php` - Laravel app configuration

## Struktur Project

```
client/
├── app/
│   └── Http/
│       └── Controllers/
│           └── Admin/
│               └── DashboardController.php
├── config/
│   └── adminlte.php
├── resources/
│   └── views/
│       └── admin/
│           └── dashboard.blade.php
├── routes/
│   └── web.php
└── public/
    └── build/
```

## Perintah Berguna

### Artisan Commands

```bash
# Membuat controller baru
docker exec php83dev php /var/www/html/kpusahatama/client/artisan make:controller NamaController

# Membuat model
docker exec php83dev php /var/www/html/kpusahatama/client/artisan make:model NamaModel

# Membuat migration
docker exec php83dev php /var/www/html/kpusahatama/client/artisan make:migration nama_migration

# Menjalankan migration
docker exec php83dev php /var/www/html/kpusahatama/client/artisan migrate

# Clear cache
docker exec php83dev php /var/www/html/kpusahatama/client/artisan cache:clear
docker exec php83dev php /var/www/html/kpusahatama/client/artisan config:clear
docker exec php83dev php /var/www/html/kpusahatama/client/artisan route:clear
```

### NPM Commands

```bash
# Install dependencies
docker exec nodedev npm install --prefix /var/www/html/kpusahatama/client

# Build assets untuk production
docker exec nodedev npm run build --prefix /var/www/html/kpusahatama/client

# Development mode dengan hot reload
docker exec nodedev npm run dev --prefix /var/www/html/kpusahatama/client
```

## Kredensial Default

Gunakan salah satu kredensial berikut untuk login:

### Admin User
- **Email:** admin@kpusahatama.com
- **Password:** admin123

### Demo User
- **Email:** demo@kpusahatama.com
- **Password:** demo123

## Catatan

- Docker containers yang digunakan: `php83dev` (PHP 8.3) dan `nodedev` (Node 22)
- Server berjalan di port 8000
- Database menggunakan SQLite (sudah dikonfigurasi di .env)
- Assets sudah dibuild dan siap digunakan

## Troubleshooting

### Jika server tidak berjalan:

```bash
# Cek apakah server sudah berjalan
docker exec php83dev ps aux | grep artisan

# Restart server
docker exec -d php83dev php /var/www/html/kpusahatama/client/artisan serve --host=0.0.0.0 --port=8000
```

### Jika ada error permission:

```bash
docker exec php83dev chmod -R 775 /var/www/html/kpusahatama/client/storage
docker exec php83dev chmod -R 775 /var/www/html/kpusahatama/client/bootstrap/cache
```

### Jika assets tidak muncul:

```bash
# Rebuild assets
docker exec nodedev npm run build --prefix /var/www/html/kpusahatama/client

# Clear cache Laravel
docker exec php83dev php /var/www/html/kpusahatama/client/artisan cache:clear
```

---

**Dibuat pada:** 27 Desember 2025  
**Laravel Version:** 12.44.0  
**AdminLTE Version:** 3.15.3
