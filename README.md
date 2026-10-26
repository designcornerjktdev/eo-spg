# EO Campaign

Aplikasi manajemen campaign untuk Event Organizer (EO). Tim EO membuat campaign beserta lokasinya dan menyusun form-nya sendiri. Konsumen mengisi form lewat **link/QR publik per lokasi** tanpa login, dan client melihat dashboard serta data entri dari campaign yang diberikan kepadanya.

Ringkasan produk ada di [`docs/PRD Aplikasi Manajemen Campaign EO (MVP).md`](docs/PRD%20Aplikasi%20Manajemen%20Campaign%20EO%20(MVP).md), sedangkan keputusan arsitektur ada di [`docs/adr/`](docs/adr/).

## Fitur utama

- **Campaign dan lokasi.** Lokasi dipilih di peta (Leaflet/OpenStreetMap). Setiap lokasi otomatis mendapat link publik `/f/{token}` beserta QR code.
- **Form builder** dengan 9 tipe field, termasuk *Image upload*. Form punya versi dan otomatis terkunci setelah ada entri pertama.
- **Form publik** untuk konsumen, tanpa login, dan selalu tampil dalam tema terang:
  - **Geofence**: posisi pengisi dicek saat halaman dibuka. Kalau berada di luar radius lokasi, form tidak ditampilkan.
  - Foto otomatis diperkecil dan dikonversi ke WebP, di browser maupun di server.
  - Proteksi spam berupa rate limit dan honeypot.
- **Dashboard interaktif** berisi tren harian/kumulatif, gauge kualifikasi, jam ramai, donut per lokasi, dan grafik jawaban.
- **Entries.** Daftar entri bisa difilter, dan entri yang salah bisa ditandai *Invalid* dengan alasan. **Export Excel** menyertakan thumbnail foto, dan entri Invalid tidak ikut ter-export.
- **Branding.** Logo dan nama perusahaan dipakai di sidebar, halaman login, judul tab, favicon, dan form publik.
- **Audit log** untuk perubahan penting.

## Role

| Role | Login | Lupa password | Akses |
| --- | --- | --- | --- |
| Super admin | email | reset sendiri | Semua, termasuk Users, Audit log, dan hapus campaign |
| Admin (operasional) | email atau username | reset sendiri | Semua campaign: campaign, form, entries, dan Company settings |
| Client | username | direset Super admin | Dashboard dan Entries (termasuk export) dari campaign yang diberikan |

Super admin mengatur akun dan akses campaign untuk client di menu **Users**.

## Stack

Laravel 12 (PHP ≥ 8.2), Inertia v2, React 19, TypeScript, Tailwind, shadcn/ui, Recharts, Leaflet, dan PhpSpreadsheet.

- **Development:** SQLite.
- **Production:** PostgreSQL diutamakan, atau MySQL kalau shared hosting mensyaratkannya.
- **Ekstensi PHP yang dibutuhkan:** `gd` (dengan WebP), `zip`, `xml`, `mbstring`, `fileinfo`. Ekstensi `exif` disarankan.
- `composer.json` dikunci ke platform PHP 8.2, supaya `composer.lock` bisa dipasang di hosting yang masih memakai PHP 8.2.

## Menjalankan di lokal

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
npm run dev
php artisan serve
```

Menjalankan tes dan pengecekan:

```bash
php artisan test
npx tsc --noEmit
npm run build
```

Hasil build frontend (`public/build`) ikut di-commit, sehingga hosting tanpa Node cukup menjalankan `git pull`. **Jalankan `npm run build` sebelum commit** setiap kali ada perubahan frontend.

## Akun demo (`php artisan db:seed`)

| Role | Login | Password |
| --- | --- | --- |
| Super admin | `admin@mail.com` | `password` |
| Admin | `ops@mail.com` atau `ops` | `password` |
| Client Mayora | `mayora` | `password` |
| Client IMOBY | `imoby` | `password` |

Seeder membuat dua campaign aktif dengan sekitar 340 entri, sekitar 70% di antaranya disertai foto contoh.

> **Peringatan:** seeder menghapus folder foto entri (`storage/app/private/submissions`). Jangan jalankan seeder di server yang sudah berisi data asli.

## Dokumentasi

- [`docs/PRD Aplikasi Manajemen Campaign EO (MVP).md`](docs/PRD%20Aplikasi%20Manajemen%20Campaign%20EO%20(MVP).md): kebutuhan produk.
- [`docs/adr/`](docs/adr/): catatan keputusan arsitektur.
- [`docs/production-checklist.md`](docs/production-checklist.md): checklist sebelum go-live dan langkah deploy.
- [`docs/mobile-qa.md`](docs/mobile-qa.md): uji manual form publik di ponsel.
