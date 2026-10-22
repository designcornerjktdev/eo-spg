# EO Campaign

Aplikasi manajemen campaign EO: form builder oleh EO, input mobile oleh SPG, dan tampilan data oleh client.

## Stack

Laravel 12, Inertia v2, React, TypeScript, Tailwind, shadcn/ui.

Development memakai SQLite. PostgreSQL diutamakan untuk production; MySQL jika shared hosting mensyaratkan. Migrasi dibuat adapter-friendly.

## Akun demo (`php artisan db:seed`)

EO (superadmin) login dengan email dan bisa reset password sendiri. Client dan SPG login dengan username; jika lupa password, EO meresetnya dari menu Pengguna.

| Role | Login | Password |
| --- | --- | --- |
| Superadmin EO | eo@eo.test (email) | password |
| Client Mayora | mayora (username) | password |
| Client IMOBY | imoby (username) | password |
| SPG | spg1 (username) | password |

## Perintah lokal

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm run dev
php artisan serve
```

Jalankan tes:

```bash
php artisan test
```

Dokumentasi keputusan ada di `docs/adr/`. Checklist production di `docs/production-checklist.md`.
