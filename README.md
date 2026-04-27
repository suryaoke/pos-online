# POS Online — Laravel 13

Aplikasi Point of Sale (POS) berbasis web dengan Laravel 12 dan Docker. M

---

## Tech Stack

- **PHP** 8.4 (FPM)
- **Laravel** 13
- **MySQL** 8.0
- **Redis** (cache, session, queue)
- **Nginx** (web server)
- **Docker** & Docker Compose

---

## Prasyarat

Pastikan sudah terinstall di komputer kamu:

- [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- Git

Tidak perlu install PHP, Composer, atau MySQL secara lokal — semua berjalan di dalam Docker.

---

## Cara Menjalankan Project

### 1. Clone repository

```bash
git clone https://github.com/username/pos-online.git
cd pos-online
```

### 2. Salin file environment

```bash
cp .env.example .env
```

### 3. Build dan jalankan container

```bash
docker compose build
docker compose up -d
```

### 4. Install dependensi Laravel

```bash
docker compose exec app composer install
```

### 5. Generate app key

```bash
docker compose exec app php artisan key:generate
```

### 6. Jalankan migrasi database

```bash
docker compose exec app php artisan migrate
```

### 7. Buka di browser

```
http://localhost:8080
```

---

## Struktur Docker

| Container | Image | Port | Fungsi |
|---|---|---|---|
| `pos-online_app` | php:8.4-fpm (custom) | 9000 | PHP-FPM · Laravel |
| `pos-online_nginx` | nginx:alpine | 8080 | Web server |
| `pos-online_mysql` | mysql:8.0 | 3306 | Database |
| `pos-online_redis` | redis:alpine | 6379 | Cache & queue |

---

## Perintah Harian

```bash
# Jalankan semua container
docker compose up -d

# Hentikan semua container
docker compose down

# Masuk ke container PHP
docker compose exec app bash

# Jalankan artisan command
docker compose exec app php artisan <command>

# Lihat log semua container
docker compose logs -f

# Lihat log container tertentu
docker compose logs -f app
docker compose logs -f nginx
```

---

## Perintah Laravel yang Sering Dipakai

```bash
# Migrasi database
docker compose exec app php artisan migrate

# Rollback migrasi
docker compose exec app php artisan migrate:rollback

# Jalankan seeder
docker compose exec app php artisan db:seed

# Clear semua cache
docker compose exec app php artisan optimize:clear

# Lihat semua route
docker compose exec app php artisan route:list

# Jalankan queue worker
docker compose exec app php artisan queue:work
```

---

## Struktur Folder

```
pos-online/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Middleware/
│   └── Models/
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── docker/
│   └── nginx/
│       └── default.conf
├── public/
├── resources/
│   └── views/
├── routes/
│   ├── api.php
│   └── web.php
├── storage/
├── .dockerignore
├── .env.example
├── Dockerfile
├── docker-compose.yml
└── README.md
```

---

## Environment Variables

Salin `.env.example` ke `.env` dan sesuaikan:

| Variable | Nilai | Keterangan |
|---|---|---|
| `DB_HOST` | `mysql` | Nama service Docker — jangan pakai 127.0.0.1 |
| `DB_DATABASE` | `pos_online` | Nama database |
| `DB_USERNAME` | `laravel` | Username MySQL |
| `DB_PASSWORD` | `password` | Password MySQL |
| `REDIS_HOST` | `redis` | Nama service Docker — jangan pakai 127.0.0.1 |
| `CACHE_STORE` | `redis` | Driver cache |
| `SESSION_DRIVER` | `redis` | Driver session |
| `QUEUE_CONNECTION` | `redis` | Driver queue |

---

## Troubleshooting

**Error: permission denied pada storage/**
```bash
docker compose exec app bash -c "chown -R www-data:www-data storage bootstrap/cache && chmod -R 775 storage bootstrap/cache"
```

**Error: 502 Bad Gateway**
```bash
# Cek status container
docker compose ps

# Lihat log PHP-FPM
docker compose logs app
```

**Error: database tidak ditemukan**
```bash
# Pastikan DB_HOST=mysql (bukan 127.0.0.1) di .env
docker compose exec app php artisan migrate
```

**Rebuild image setelah ubah Dockerfile**
```bash
docker compose build --no-cache
docker compose up -d
```

**Nginx config bermasalah**
```bash
# Test config
docker compose exec nginx nginx -t

# Reload config
docker compose exec nginx nginx -s reload
```

---

## Catatan untuk Production

Sebelum deploy ke server production, ubah nilai berikut di `.env`:

```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error
SESSION_ENCRYPT=true
FILESYSTEM_DISK=s3
MAIL_MAILER=smtp
```

---

## Lisensi

MIT License