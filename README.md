# EO Campaign

Aplikasi manajemen campaign EO: EO membuat form per campaign, konsumen mengisinya lewat link/QR publik per lokasi (tanpa login), dan akun client hanya bisa membaca dashboard serta entri dari campaign yang diberikan EO. Lihat `docs/adr/004-public-location-forms.md` dan `docs/adr/005-client-accounts-with-campaign-access.md`.

## Stack

Laravel 12, Inertia v2, React, TypeScript, Tailwind, shadcn/ui.

Development memakai SQLite. PostgreSQL diutamakan untuk production; MySQL jika shared hosting mensyaratkan. Migrasi dibuat adapter-friendly.

## Akun demo (`php artisan db:seed`)

EO (superadmin) login dengan email dan bisa reset password sendiri. Client login dengan username dan hanya melihat campaign yang dicentang EO di menu **Users**; jika lupa password, EO meresetnya dari menu yang sama. Link form publik ada di halaman detail campaign (bagian **Public form links**).

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
