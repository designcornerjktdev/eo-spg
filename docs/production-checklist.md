# Checklist production MVP

Gunakan sebelum go-live. Perubahan skema production hanya lewat migrasi.

## Hosting dan environment

- [ ] Versi PHP production dikonfirmasi (>= 8.2)
- [ ] Database production dikonfirmasi (PostgreSQL diutamakan, MySQL jika shared hosting mensyaratkan)
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` memakai HTTPS
- [ ] `APP_KEY` unik, jangan copy dari development
- [ ] Session `secure` / HTTPS, CSRF aktif
- [ ] Rate limit login tetap aktif
- [ ] Seeder production **tidak** dijalankan dengan akun dummy Mayora

## Backup dan log

- [ ] Backup database terjadwal (harian minimal)
- [ ] Uji restore backup berhasil sebelum go-live
- [ ] `LOG_CHANNEL` aktif dan bisa dibaca saat insiden
- [ ] Rotasi log tidak menimpa bukti audit

## Keamanan data

- [ ] Tes IDOR lintas client dan SPG lulus (`php artisan test`)
- [ ] Field `visible_to_client` dan masking identitas ditinjau per campaign
- [ ] Kebijakan retensi data konsumen setelah campaign ditutup sudah disetujui legal
- [ ] Konfirmasi kepatuhan UU PDP No. 27 Tahun 2022

## Mobile SPG

- [ ] Form diuji di lebar ~360px dan ~390px
- [ ] Tombol Ya/Tidak bisa ditekan satu tangan
- [ ] Error simpan terlihat jelas jika jaringan putus

## Cutover

- [ ] `php artisan migrate --force`
- [ ] `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- [ ] `npm run build` dan aset production di-upload
- [ ] Akun EO pertama dibuat manual, bukan dari seeder demo
