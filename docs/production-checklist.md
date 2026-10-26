# Checklist production

Gunakan checklist ini sebelum go-live dan setiap kali deploy. Perubahan skema database di production hanya boleh lewat migrasi.

## Hosting dan environment

- [ ] PHP ≥ 8.2 dengan ekstensi `gd` (dukungan WebP), `zip`, `xml`, `mbstring`, `fileinfo`. Ekstensi `exif` disarankan. Cek dengan `php -m`.
- [ ] Database production sudah dikonfirmasi: PostgreSQL diutamakan, atau MySQL kalau shared hosting mensyaratkannya.
- [ ] **Document root diarahkan ke folder `public/`**, misalnya lewat subdomain. Root project tidak boleh bisa diakses dari web, karena `.env` berisi kredensial.
- [ ] HTTPS aktif. Geolocation di form publik hanya jalan lewat HTTPS.
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, dan `APP_URL` memakai HTTPS.
- [ ] `APP_KEY` unik. Jangan menyalin dari development.
- [ ] `TRUSTED_PROXIES` diisi IP/CIDR proxy atau load balancer yang benar-benar dipakai, atau dikosongkan kalau tanpa proxy.
  - Jangan isi `*`, kecuali server hanya bisa diakses lewat proxy tersebut.
  - Kalau diisi sembarangan, IP pengunjung (dipakai untuk rate limit form publik) dan host (dipakai di link reset password) bisa dipalsukan.
- [ ] `MAIL_*` sudah dikonfigurasi dengan SMTP asli, supaya email reset password untuk Super admin dan Admin benar-benar terkirim.
- [ ] Sesi `secure` di HTTPS dan CSRF aktif.
- [ ] Rate limit login dan form publik tetap aktif.
- [ ] `upload_max_filesize` dan `post_max_size` di PHP minimal 12 MB. Foto dibatasi 10 MB per field.

## Penyimpanan file

- [ ] Folder `storage/` bisa ditulis dan **tidak ikut terhapus saat deploy**. Foto entri ada di `storage/app/private/submissions`, logo perusahaan di `storage/app/public`, dan keduanya tidak tersimpan di Git.
- [ ] `php artisan storage:link` sudah dijalankan.
- [ ] Folder `storage/` ikut di-backup bersama database.

## Backup dan log

- [ ] Backup database dan `storage/` terjadwal, minimal harian.
- [ ] Restore dari backup sudah diuji dan berhasil sebelum go-live.
- [ ] `LOG_CHANNEL` aktif dan log bisa dibaca saat ada insiden.
- [ ] Rotasi log tidak menimpa bukti audit.

## Keamanan dan data

- [ ] Semua tes lulus (`php artisan test`), termasuk tes isolasi akses antar client.
- [ ] Pengaturan `visible_to_client` per field dan masking identitas per campaign sudah ditinjau.
- [ ] Radius geofence setiap lokasi sudah diuji langsung di venue. Default-nya 300 m.
- [ ] Kebijakan retensi data konsumen (termasuk foto) setelah campaign ditutup sudah disetujui legal.
- [ ] Kepatuhan UU PDP No. 27 Tahun 2022 sudah dikonfirmasi.
- [ ] **Seeder demo tidak dijalankan di production.** Seeder membuat akun dengan password `password` dan menghapus folder foto entri.
- [ ] Akun Super admin pertama dibuat manual, misalnya lewat `php artisan tinker`, dengan password yang kuat.

## Form publik di ponsel

- [ ] Checklist di [`mobile-qa.md`](mobile-qa.md) sudah dijalankan, minimal di satu Android dan satu iPhone.

## Langkah deploy (shared hosting, tanpa Node)

1. Di lokal: jalankan `npm run build`, lalu commit (termasuk `public/build`) dan push.
2. Di server:
   ```bash
   git pull
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan optimize
   ```
3. Buka aplikasi, lalu coba login, buka satu link form publik, dan lakukan export Excel.
