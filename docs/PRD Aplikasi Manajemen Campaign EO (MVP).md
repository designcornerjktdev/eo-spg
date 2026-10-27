# PRD: Aplikasi Manajemen Campaign EO (MVP)

Dibuat 20 Okt 2026, diperbarui 27 Okt 2026 · @Rahmat Tomy

> **Versi 2 (sesuai yang sudah dibangun).** Draf pertama memakai SPG yang login, penugasan, data perusahaan client, dan produk. Sejak 23 Okt 2026 alurnya berubah menjadi model "Google Form": konsumen mengisi sendiri lewat link/QR publik per lokasi. Alasan setiap perubahan tercatat di [`docs/adr/`](adr/), yaitu ADR 004 sampai 008.

## Ringkasan

EO membuat campaign dan lokasinya, lalu menyusun form sendiri untuk campaign tersebut. Setiap lokasi punya link dan QR code yang dibuka konsumen di ponselnya masing-masing. Konsumen mengisi form tanpa login, hanya bisa mengirim dari area event, dan bisa melampirkan foto. Client melihat dashboard dan data entri dari campaign yang diberikan kepadanya, serta bisa mengunduhnya sebagai Excel.

| Item | Keputusan |
| --- | --- |
| Role login | Super admin, Admin (operasional EO), Client |
| Pengisi form | Konsumen, tanpa akun, lewat link/QR per lokasi |
| Inti produk | Form builder oleh EO, form publik dengan geofence, dashboard dan export untuk client |
| Platform | Web responsif. Form publik dirancang mobile-first |
| Stack | Laravel 12, Inertia v2, React 19, TypeScript, Tailwind, shadcn/ui |
| Hosting | Shared hosting (Hostinger), PHP 8.2, MySQL/PostgreSQL |

## Latar belakang dan masalah

Selama ini data lapangan dicatat di kertas, direkap ke Excel, lalu disusun manual menjadi laporan PPTX harian. Prosesnya lambat, rawan salah ketik, dan sulit diaudit.

Contoh nyata:

- **Form sampling Kopi Lepas Haus (Mayora)** berupa tabel kertas berisi Nama, Usia, No HP, Pekerjaan, Domisili, Akun Instagram, dan tiga pertanyaan Ya/Tidak.
- **Laporan IMOBY 2026 Bandung** berupa rekap PPTX dengan tabel Excel yang ditempel sebagai gambar. Client tidak bisa memfilter data sendiri.

Masalah utama:

- Struktur form berbeda di setiap campaign.
- Tidak ada timestamp yang tepercaya.
- Client harus menunggu laporan manual.
- Data personal konsumen tersebar tanpa kontrol akses.

## Tujuan, non-tujuan, dan metrik

**Tujuan**

- EO membuat dan mengubah form campaign tanpa bantuan developer.
- Konsumen mengisi sendiri dalam beberapa menit, dari lokasi event.
- Setiap entri tercatat dengan waktu server yang tidak bisa diubah.
- Client memantau hasil secara langsung, lengkap dengan foto dokumentasi, tanpa menunggu laporan manual.
- Akses data dibatasi ketat per role dan per campaign.

**Non-tujuan (versi ini)**

- Pencatatan penjualan, SKU, merchandise, dan stok.
- Mode offline, notifikasi, dan PPTX otomatis.
- Logika kondisional di dalam form.

**Metrik keberhasilan** (target angka menunggu stakeholder)

| Metrik | Cara ukur | Target |
| --- | --- | --- |
| Waktu EO menyiapkan campaign dan form baru | Uji coba dengan form Mayora | Belum ditetapkan |
| Waktu konsumen mengisi form | Uji lapangan | Belum ditetapkan |
| Entri dari luar area event | Ditolak geofence | 0 yang lolos tanpa manipulasi GPS |
| Kebocoran data antar client | Tes otomatis lintas role | 0 kasus |

## Pengguna dan role

| Role | Siapa | Login | Kebutuhan utama |
| --- | --- | --- | --- |
| Super admin | Pemilik atau kepala EO | email | Semua fitur, termasuk akun, audit log, dan hapus campaign |
| Admin | Tim operasional EO | email atau username | Menjalankan campaign, form, entries, dan branding untuk semua campaign |
| Client | Brand atau pemilik produk | username | Melihat dashboard dan entries dari campaign yang diberikan, lalu export |
| Konsumen | Pengunjung booth | tidak login | Mengisi form lewat link/QR di lokasi |

Super admin dan Admin bisa mereset password sendiri lewat email. Password Client direset oleh Super admin.

## Cakupan

| Modul | Status |
| --- | --- |
| Autentikasi dan 3 role login | Selesai |
| Akun dan akses campaign untuk client | Selesai |
| Campaign dan lokasi (map picker, radius geofence) | Selesai |
| Link dan QR publik per lokasi | Selesai |
| Form builder (9 tipe field, versi, kunci) | Selesai |
| Form publik: geofence, upload foto dengan WebP, anti-spam, selalu tema terang | Selesai |
| Daftar entri, filter, dan tandai Invalid | Selesai |
| Dashboard interaktif | Selesai |
| Export Excel dengan thumbnail foto | Selesai |
| Branding perusahaan (logo, nama, favicon) | Selesai |
| Audit log | Selesai |
| Penjualan, SKU, merchandise, stok | Ditunda |
| Offline, notifikasi, PPTX otomatis | Ditunda |
| Rotasi link publik (ganti token) | Usulan |

## Alur kerja

1. Super admin atau Admin membuat **campaign** dan **lokasinya**. Setiap lokasi diberi pin di peta dan radius, dengan default 300 m.
2. Super admin atau Admin menyusun **form**, mempratinjaunya, lalu mem-**publish**. Setelah form menerima entri pertama, form terkunci. Perubahan berikutnya dibuat sebagai versi baru.
3. Campaign diaktifkan. Link/QR setiap lokasi dibagikan di booth.
4. Konsumen membuka link, dan posisinya dicek. Kalau berada di luar area, form tidak ditampilkan. Kalau di dalam area, konsumen mengisi form, mencentang consent, lalu mengirim.
5. Entri langsung muncul di dashboard dan Entries. Entri yang salah, misalnya ganda atau data uji coba, **ditandai Invalid** beserta alasannya.
6. Client memantau dashboard dan mengunduh Excel.
7. Campaign ditutup. Setelah itu link menolak entri baru.

**Status**

| Objek | Status |
| --- | --- |
| Campaign | `draft` → `active` → `closed` |
| Form | `draft` → `published` → `locked` (otomatis setelah entri pertama) |
| Entri | `Valid` atau `Invalid` (dengan alasan wajib, dan tidak bisa dibatalkan) |

## User stories dan kriteria penerimaan

| ID | Sebagai | Saya ingin | Kriteria penerimaan |
| --- | --- | --- | --- |
| U1 | Super admin | membuat akun Admin dan Client | Admin wajib punya email. Client tanpa email dan dicentang akses campaign-nya. Password di-hash |
| U1b | Admin | menghubungkan campaign ke client | Hanya akun client yang terlihat. Admin hanya bisa mengubah akses campaign, bukan data akun atau password |
| U2 | Super admin / Admin | membuat campaign dan lokasi di peta | Tanggal selesai tidak boleh sebelum tanggal mulai. Setiap lokasi otomatis punya link publik unik |
| U3 | Super admin / Admin | menyusun form | Field bisa diurutkan dan dipratinjau. Form terkunci setelah entri pertama, dan perubahan membuat versi baru |
| U4 | Konsumen | mengisi form dari link di booth | Tanpa login. Validasi dijalankan di server. Waktu diisi server. Hanya bisa dikirim dari dalam radius lokasi |
| U5 | Konsumen | melampirkan foto | Bisa dari kamera atau galeri. Foto diperkecil otomatis. Tombol kirim menunggu sampai foto siap |
| U6 | Super admin / Admin | memfilter entri dan menandai Invalid | Filter campaign, lokasi, tanggal, dan teks. Tandai Invalid wajib beralasan dan tercatat di audit log |
| U7 | Client | melihat entri campaign saya | Hanya campaign yang diberikan, hanya field yang ditandai terlihat untuk client, tanpa masking |
| U8 | Client | melihat ringkasan dan grafik | Angka dihitung dari entri Valid, sama seperti daftar entri |
| U9 | Client / EO | mengunduh Excel | Mengikuti filter dan aturan visibilitas. Foto tampil sebagai thumbnail. Entri Invalid tidak ikut |
| U10 | Super admin / Admin | mengganti logo dan nama perusahaan | Tampil di sidebar, halaman login, favicon, dan form publik |

## Form builder dan form publik

Struktur form disimpan sebagai data, dan jawaban disimpan sebagai JSON per entri. Membuat form tidak membuat tabel database baru.

**Tipe field**

| Tipe | Contoh | Catatan |
| --- | --- | --- |
| Short text | Nama, Domisili, Instagram | Bisa ditandai identitas |
| Long text | Catatan | |
| Number | Usia | Ada batas minimum dan maksimum. Bisa tampil di dashboard (histogram) |
| Phone number | No HP | Validasi format |
| Single choice | Tahu acara dari mana | Bisa tampil di dashboard (donut) |
| Multiple choice | Varian yang ingin dicoba | Bisa tampil di dashboard (bar) |
| Yes/No | Konsumsi CSD minimal 2x sebulan | Dipakai untuk aturan kualifikasi, tidak tampil sebagai grafik sendiri |
| Date | Tanggal lahir | |
| Image upload | Foto dokumentasi | Maksimal 10 MB. Disimpan privat dalam format WebP |

**Pengaturan per field:** label, kunci unik, wajib atau tidak, urutan, terlihat oleh client, ditandai identitas, dan tampil di dashboard.

**Pengaturan per form:** nama, versi, status, consent beserta teksnya, dan aturan kualifikasi. Aturan kualifikasi: konsumen dianggap *qualified* kalau semua pertanyaan Yes/No dijawab Yes.

**Aturan penting**

1. Waktu entri (`submitted_at`) diisi oleh server, bukan diambil dari jam ponsel.
2. Lokasi entri diambil dari link yang dibuka. Konsumen tidak memilih lokasi sendiri.
3. Geofence dicek di browser saat halaman dibuka, lalu dicek ulang di server saat dikirim (ADR 006).
4. Link hanya menerima entri selama campaign berstatus Active dan memiliki form yang sudah di-publish.
5. Anti-spam: maksimal 10 kiriman per menit per IP per link, ditambah honeypot.
6. Entri tidak bisa diedit atau dihapus. Koreksi dilakukan dengan menandai entri Invalid.
7. Kalau consent diaktifkan, konsumen wajib mencentangnya, dan versi teks consent ikut tersimpan.

## Dashboard dan pelaporan

- **Kotak angka:** jumlah entri, jumlah entri Invalid, dan jumlah campaign.
- **Grafik:**
  - tren entri harian atau kumulatif, dipisah qualified dan tidak
  - gauge persentase qualified
  - jam ramai
  - donut per lokasi, yang bisa diklik untuk memfilter
  - grafik untuk setiap pertanyaan yang ditandai dashboard
- **Filter:** campaign, lokasi, dan rentang tanggal. Filter langsung diterapkan saat dipilih.
- **Entries:** tabel dengan pencarian dan pagination, lalu halaman detail yang menampilkan foto. Client hanya melihat field `visible_to_client`. Data identitas tidak dimasking.
- **Export:** file Excel (.xlsx) berisi daftar yang sedang difilter, dengan thumbnail foto. Entri Invalid tidak ikut ter-export.

## Model data

Tabel utama:

| Tabel | Isi |
| --- | --- |
| `users` | Akun dengan `role` (superadmin, admin, client), `username`, `email` (opsional untuk client), dan `is_active` |
| `campaigns` | Nama, tanggal, dan status |
| `campaign_user` | Akses campaign per akun client |
| `campaign_locations` | Lokasi dengan alamat, `latitude`, `longitude`, `radius_meters`, dan `public_token` |
| `form_templates` / `form_fields` | Definisi form per campaign beserta versinya |
| `form_submissions` | Entri: campaign, lokasi, versi form, `answers` (JSON), `submitted_at`, posisi dan jarak, status, alasan invalid |
| `submission_field_values` | Salinan jawaban yang ditandai dashboard, untuk agregasi cepat |
| `audit_logs` | Siapa, aksi apa, nilai lama dan baru, serta kapan |
| `settings` | Nama perusahaan dan path logo |

File foto disimpan di `storage/app/private/submissions`, tidak di database.

## Matriks hak akses

Setiap aksi dicek di server dengan Policy dan query scope. Menyembunyikan menu di frontend tidak dianggap sebagai pembatasan akses.

| Aksi | Super admin | Admin | Client |
| --- | --- | --- | --- |
| Kelola akun (buat, ubah, hapus, reset password) | Ya | Tidak | Tidak |
| Hubungkan campaign ke client | Ya | Ya | Tidak |
| Campaign dan lokasi: buat, ubah, aktifkan, tutup | Ya | Ya | Tidak |
| Hapus campaign | Ya | Tidak | Tidak |
| Form builder | Ya | Ya | Tidak |
| Lihat entri | Semua | Semua | Campaign yang diberikan, field yang diizinkan |
| Tandai entri Invalid | Ya | Ya | Tidak |
| Dashboard dan export Excel | Semua | Semua | Campaign yang diberikan |
| Company settings | Ya | Ya | Tidak |
| Audit log | Ya | Tidak | Tidak |

**Aturan pengujian wajib** (semuanya dicakup tes otomatis)

- Client A tidak bisa membuka campaign, entri, foto, atau export milik campaign lain lewat ID mana pun.
- Client tidak pernah menerima field yang tidak ditandai terlihat, termasuk lewat foto dan export.
- Admin hanya melihat akun client di Users dan hanya bisa mengubah akses campaign-nya. Admin tidak bisa membuka Audit log atau menghapus campaign.
- `/register` tidak tersedia. Akun hanya dibuat oleh Super admin.

## Kebutuhan non-fungsional

| Area | Kebutuhan |
| --- | --- |
| Keamanan | Otorisasi di server, hashing password, HTTPS, CSRF, rate limit login dan form publik, `TRUSTED_PROXIES` yang spesifik |
| Integritas data | Waktu dari server. Entri tidak bisa diedit, hanya ditandai Invalid. Audit log untuk perubahan penting |
| Privasi | Field bisa disembunyikan dari client lewat `visible_to_client`, consent tercatat bersama versi teksnya, foto disimpan privat. Kepatuhan UU PDP No. 27/2022 dikonfirmasi ke pihak legal |
| Mobile | Form publik nyaman dipakai satu tangan dan diuji di Android dan iPhone ([`mobile-qa.md`](mobile-qa.md)) |
| Performa | Pagination, tabel indeks dashboard, dan foto diperkecil sebelum upload |
| Ketersediaan | Backup database dan `storage/` terjadwal, restore diuji sebelum go-live |
| Deployment | Migrasi untuk setiap perubahan skema. Aset frontend di-commit, jadi hosting tidak butuh Node ([`production-checklist.md`](production-checklist.md)) |

## Arsitektur

Aplikasi berbentuk modular monolith: satu aplikasi Laravel dengan antarmuka React lewat Inertia, tanpa REST API terpisah. Aturan bisnis berada di `app/Domain/*/Services`:

- **Forms:** validasi form dinamis dan versi form.
- **Submissions:** pencatatan entri, geofence, optimasi foto, dan penandaan Invalid.
- **Reporting:** dashboard dan export Excel.
- **Audit:** audit log.

Keputusan penting dicatat di [`docs/adr/`](adr/).

## Risiko dan mitigasi

| Risiko | Dampak | Mitigasi |
| --- | --- | --- |
| Sinyal buruk di venue | Konsumen gagal mengirim | Foto diperkecil sebelum upload. Mode offline ditunda dan dicatat sebagai risiko |
| GPS di dalam mal tidak akurat | Konsumen yang ada di lokasi ikut tertolak | Toleransi akurasi sampai 100 m. Radius diatur per lokasi dan diuji di venue |
| Lokasi dipalsukan | Ada entri dari luar area | Geofence hanya mencegah link disebar. Entri mencurigakan ditandai Invalid |
| Link disebar di luar lokasi | Spam | Geofence, rate limit, honeypot. Rotasi token sebagai usulan |
| Data personal bocor ke client yang salah | Pelanggaran UU PDP | Policy dan scope, visibilitas field, tes isolasi otomatis |
| Folder `storage/` hilang saat deploy | Foto entri hilang | Folder `storage/` di-backup dan tidak ditimpa saat deploy |

## Pertanyaan terbuka

1. ~~Apakah upload foto masuk MVP?~~ Ya, sudah dibangun sebagai field Image upload (ADR 008).
2. ~~Apakah client boleh melihat data personal?~~ Boleh. Data identitas tidak dimasking; field yang tidak boleh dilihat client disembunyikan lewat visibilitas per field.
3. ~~Apakah export wajib ada?~~ Ya, dalam bentuk Excel dengan foto (ADR 008).
4. ~~Hosting apa yang dipakai?~~ Hostinger dengan PHP 8.2. Database production masih perlu dikonfirmasi.
5. Berapa perkiraan jumlah entri per hari dan berapa foto per campaign? Ini menentukan kebutuhan storage dan waktu export.
6. Bagaimana kebijakan retensi data dan foto konsumen setelah campaign ditutup?
7. Apakah link publik perlu bisa dirotasi (token diganti) kalau link sudah tersebar?
8. Apakah perlu field "Nama promotor" untuk mengukur kinerja per petugas booth, sebagai pengganti peran SPG?
