# EO Campaign

Aplikasi manajemen campaign EO: EO membuat form per campaign, konsumen mengisinya lewat link/QR publik per lokasi (tanpa login), dan client melihat hasil campaign-nya sendiri. Lihat `docs/adr/004-public-location-forms.md`.

## Stack

Laravel 12, Inertia v2, React, TypeScript, Tailwind, shadcn/ui.

Development memakai SQLite. PostgreSQL diutamakan untuk production; MySQL jika shared hosting mensyaratkan. Migrasi dibuat adapter-friendly.

## Akun demo (`php artisan db:seed`)

EO (superadmin) login dengan email dan bisa reset password sendiri. Client login dengan username; jika lupa password, EO meresetnya dari menu Users. Link form publik ada di halaman detail campaign (bagian **Public form links**).

| Role | Login | Password |
| --- | --- | --- |
| Superadmin EO | eo@eo.test (email) | password |
| Client Mayora | mayora (username) | password |
| Client IMOBY | imoby (username) | password |

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
