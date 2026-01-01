# Setup Login Authentication Between PayrollV2Dev and ClientKPUDev

## Overview
Sistem ini menggunakan API authentication dari payrollv2dev untuk login di clientkpudev. Autentikasi menggunakan Laravel Sanctum dengan validasi role 'client' menggunakan Entrust.

## Architecture

```
┌─────────────────────┐           API Request          ┌──────────────────────┐
│                     │ ────────────────────────────>  │                      │
│   ClientKPUDev      │                                │   PayrollV2Dev       │
│  (Client Dashboard) │ <────────────────────────────  │  (Admin Dashboard)   │
│                     │           API Response         │                      │
└─────────────────────┘                                └──────────────────────┘
        │                                                        │
        │ Session Storage                                       │ Database + Entrust
        │ - auth_token                                          │ - User with Roles
        │ - user data                                           │ - Token Management
        │ - token_type                                          │
```

## Setup Instructions

### 1. PayrollV2Dev (API Server) Setup

#### Update .env file:
```env
# Add client domain to SANCTUM_STATEFUL_DOMAINS
SANCTUM_STATEFUL_DOMAINS=client-app-dev.kpusahatama.id
```

#### Files Created/Modified:
- **app/Http/Controllers/Api/AuthController.php** - API Login, Logout, Me, Refresh endpoints
- **routes/api.php** - Auth API routes
- **config/sanctum.php** - Added client domain to stateful domains
- **config/cors.php** - Configured CORS for client domain

#### API Endpoints:
```
POST   /api/auth/login          - Login (public)
POST   /api/auth/logout         - Logout (authenticated)
GET    /api/auth/me             - Get user info (authenticated)
POST   /api/auth/refresh        - Refresh token (authenticated)
```

#### Login API Request:
```json
POST https://sip-dev.kpusahatama.id/api/auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password123"
}
```

#### Login API Response (Success):
```json
{
    "success": true,
    "message": "Login berhasil",
    "data": {
        "user": {
            "id": 1,
            "name": "Client User",
            "email": "user@example.com",
            "company": "PT Example",
            "phone": "08123456789",
            "occupation": "Manager",
            "description": null,
            "last_login": "2025-12-27 10:00:00",
            "client": {...},
            "pic": {...},
            "roles": ["client"]
        },
        "token": "1|abc123...",
        "token_type": "Bearer"
    }
}
```

#### Login API Response (Error):
```json
{
    "success": false,
    "message": "Email tidak terdaftar"
}
```

### 2. ClientKPUDev (Client Dashboard) Setup

#### Update .env file:
```env
APP_NAME="Client Dashboard"
APP_URL=https://client-app-dev.kpusahatama.id

# Payroll API Configuration
PAYROLL_API_URL=https://sip-dev.kpusahatama.id/api
```

#### Files Created/Modified:
- **app/Services/PayrollApiService.php** - Service untuk komunikasi dengan API
- **app/Http/Controllers/Auth/LoginController.php** - Handle login/logout
- **app/Http/Middleware/ClientAuth.php** - Middleware untuk cek autentikasi
- **app/Http/Middleware/RedirectIfAuthenticated.php** - Middleware untuk guest
- **bootstrap/app.php** - Register middleware
- **routes/web.php** - Auth dan admin routes
- **resources/views/auth/login.blade.php** - Login page dengan AdminLTE
- **resources/views/admin/dashboard.blade.php** - Dashboard page
- **config/adminlte.php** - AdminLTE configuration

#### Routes Structure:
```php
GET  /                          -> redirect to login
GET  /login                     -> Show login form (guest only)
POST /login                     -> Process login (guest only)
POST /logout                    -> Process logout

GET  /admin/dashboard           -> Dashboard (authenticated)
```

## How It Works

### Login Flow:
1. User mengakses `client-app-dev.kpusahatama.id`
2. Redirect ke `/login` (AdminLTE login page)
3. User input email dan password
4. System call API `POST /api/auth/login` ke payrollv2dev
5. API validate credentials dan check role 'client' using Entrust
6. Jika sukses, API return token dan user data
7. ClientKPUDev simpan token dan user data ke session
8. Redirect ke `/admin/dashboard`

### Protected Route Flow:
1. User akses protected route (e.g., `/admin/dashboard`)
2. Middleware `client.auth` check session untuk token dan user
3. Jika tidak ada, redirect ke login
4. Jika ada, allow request continue

### Logout Flow:
1. User click logout button
2. System call API `POST /api/auth/logout` ke payrollv2dev
3. API delete current token
4. ClientKPUDev clear session
5. Redirect ke login page

## Session Storage

ClientKPUDev menyimpan data berikut di session:
- `auth_token` - Bearer token dari Sanctum
- `user` - User data (id, name, email, company, roles, etc.)
- `token_type` - "Bearer"

## Security Features

1. **Role Validation**: API hanya allow user dengan role 'client'
2. **Token-based Auth**: Menggunakan Laravel Sanctum tokens
3. **CORS Protection**: Hanya allow request dari domain yang terdaftar
4. **Middleware Protection**: Semua admin routes dilindungi middleware
5. **Session Security**: Token disimpan di server-side session
6. **Guest Protection**: User yang sudah login tidak bisa akses login page

## Testing

### Create Test User with Client Role:

Di payrollv2dev, jalankan di terminal atau tinker:

```php
// Create user
$user = User::create([
    'name' => 'Client Test',
    'email' => 'client@test.com',
    'password' => bcrypt('password123'),
    'company' => 'PT Test',
    'phone' => '08123456789',
]);

// Attach client role
$clientRole = Role::where('name', 'client')->first();
if ($clientRole) {
    $user->roles()->attach($clientRole->id);
}
```

### Test Login:

1. Akses `https://client-app-dev.kpusahatama.id`
2. Login dengan:
   - Email: client@test.com
   - Password: password123
3. Should redirect to dashboard with user info

## Troubleshooting

### CORS Error:
- Check `payrollv2dev/config/cors.php`
- Ensure client domain is in `allowed_origins`
- Check `supports_credentials` is `true`

### 401 Unauthorized:
- Check user has 'client' role in database
- Check token is valid
- Check SANCTUM_STATEFUL_DOMAINS includes client domain

### Session Not Persisting:
- Check session driver in .env (recommend: database or redis)
- Check session cookies are being set
- Check domain configuration

### Token Not Found:
- Check API response structure
- Check session storage
- Check middleware is registered

## Future Enhancements

1. **Token Validation**: Uncomment code di ClientAuth middleware untuk validate token setiap request
2. **Token Refresh**: Implement automatic token refresh sebelum expire
3. **Remember Me**: Implement remember me functionality
4. **Multi-Guard**: Support multiple guard types
5. **API Caching**: Cache API responses untuk performance
6. **Error Logging**: Enhanced error logging dan monitoring

## File Structure

### PayrollV2Dev
```
app/
  Http/
    Controllers/
      Api/
        AuthController.php       # API Login/Logout
config/
  sanctum.php                    # Sanctum config
  cors.php                       # CORS config
routes/
  api.php                        # API routes
```

### ClientKPUDev
```
app/
  Http/
    Controllers/
      Auth/
        LoginController.php      # Login/Logout handler
      Admin/
        DashboardController.php  # Dashboard
    Middleware/
      ClientAuth.php             # Auth middleware
      RedirectIfAuthenticated.php  # Guest middleware
  Services/
    PayrollApiService.php        # API communication service
resources/
  views/
    auth/
      login.blade.php            # Login page
    admin/
      dashboard.blade.php        # Dashboard page
routes/
  web.php                        # Web routes
config/
  adminlte.php                   # AdminLTE config
```

## Notes

- Pastikan role 'client' sudah ada di database payrollv2dev
- User harus memiliki role 'client' untuk bisa login
- Token disimpan di session, bukan localStorage atau cookie
- API menggunakan Sanctum token authentication
- Semua admin routes di clientkpudev dilindungi middleware

---

Created: December 27, 2025
Last Updated: December 27, 2025
