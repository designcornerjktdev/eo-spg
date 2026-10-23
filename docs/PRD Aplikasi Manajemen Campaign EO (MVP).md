# PRD: Aplikasi Manajemen Campaign EO (MVP)

Oct 20, 2026 · @Rahmat Tomy

> **Update 2026-10-23:** alur bisnis berubah. Role SPG dan penugasan dihapus; konsumen mengisi form sendiri lewat link/QR publik per lokasi campaign. Bagian dokumen ini yang menyebut SPG sudah tidak berlaku — lihat ADR 004 (`docs/adr/004-public-location-forms.md`).

## Ringkasan

MVP ini adalah aplikasi web tempat EO membuat form sendiri untuk setiap campaign, SPG mengisinya dari ponsel dengan timestamp otomatis dari server, dan client melihat data campaign serta produknya sendiri. Penjualan, stok, dan merchandise sengaja ditunda ke versi berikutnya.

| Item | Keputusan |
| --- | --- |
| Role | Superadmin (EO), Client, SPG |
| Inti produk | Form builder oleh EO, input form oleh SPG, tampilan data oleh Client |
| Platform | Web responsif, mobile-first untuk SPG |
| Stack | Laravel, Inertia, React, TypeScript, Tailwind, shadcn/ui |
| Hosting awal | Shared hosting (MySQL atau PostgreSQL, belum dikonfirmasi) |
| Status dokumen | Draf untuk ditinjau stakeholder |

## Latar belakang dan masalah

Saat ini data lapangan dicatat di kertas, direkap ke Excel, lalu disusun manual menjadi laporan PPTX per hari. Proses ini lambat, rawan salah ketik, dan sulit diaudit.

Dua contoh nyata dari EO menunjukkan masalahnya:

- **Form sampling Kopi Lepas Haus (Mayora)** berupa tabel kertas dengan kolom Nama, Usia, No HP, Pekerjaan, Domisili, Akun Instagram, dan tiga pertanyaan Ya/Tidak. Header Tanggal, Area, Lokasi, dan Nama SPG ditulis tangan.
- **Laporan IMOBY 2026 Bandung** berisi rekap harian dalam PPTX dan tabel Excel yang ditempel sebagai gambar. Client tidak bisa memfilter atau menelusuri data sendiri.

Masalah utama:

- Struktur form berbeda untuk setiap campaign, sehingga aplikasi dengan kolom tetap tidak akan cukup.
- Tidak ada timestamp yang tepercaya untuk setiap data yang dicatat.
- Client harus menunggu laporan manual dari EO.
- Data personal konsumen (nama, HP, Instagram) tersebar di kertas dan file tanpa kontrol akses.

## Tujuan, non-tujuan, dan metrik

Tujuan MVP adalah mengganti form kertas dan rekap Excel dengan input digital yang bisa dipantau EO dan client secara langsung.

**Tujuan**

- EO membuat dan mengubah form campaign tanpa bantuan developer.
- SPG mengisi data dari ponsel dengan cepat, baris demi baris.
- Setiap data tercatat dengan timestamp server yang tidak bisa diubah.
- Client melihat data campaign dan produknya sendiri tanpa menunggu laporan manual.
- Akses data dibatasi ketat per role dan per client.

**Non-tujuan (versi ini)**

- Pencatatan penjualan, SKU, harga promo, merchandise, dan stok.
- Event multi-brand, role Team Leader, Crew, dan Logistic dengan login.
- Check-in, GPS, mode offline, notifikasi, dan PPTX otomatis.

**Metrik keberhasilan** (target angka perlu dikonfirmasi stakeholder)

| Metrik | Cara ukur | Target |
| --- | --- | --- |
| Waktu EO membuat form baru | Uji coba dengan form Mayora | Belum ditetapkan |
| Waktu SPG mengisi satu baris | Uji coba lapangan | Belum ditetapkan |
| Data yang masuk lewat aplikasi, bukan kertas | Jumlah submission dibanding perkiraan | Belum ditetapkan |
| Kebocoran data antar client atau antar SPG | Tes otomatis lintas role | 0 kasus |

## Pengguna dan role

Ada tiga role login. Satu akun hanya punya satu role, dan akun Client terikat ke satu perusahaan client.

| Role | Siapa | Kebutuhan utama | Perangkat |
| --- | --- | --- | --- |
| Superadmin (EO) | Tim EO | Membuat client, produk, campaign, form, dan assignment; memantau semua data | Desktop |
| Client | Perusahaan pemilik produk | Melihat produk, campaign, dan data yang boleh dilihat | Desktop dan ponsel |
| SPG | Petugas lapangan | Melihat tugas dan mengisi form dengan cepat | Ponsel |

## Cakupan MVP

MVP mencakup satu alur lengkap: EO menyiapkan campaign dan form, SPG mengisi, client melihat hasilnya.

| Modul | Status |
| --- | --- |
| Autentikasi dan 3 role | Masuk |
| Manajemen client dan akun client | Masuk |
| Manajemen produk | Masuk |
| Campaign dan lokasi | Masuk |
| Assignment SPG ke campaign, lokasi, dan jadwal | Masuk |
| Form builder (EO) | Masuk |
| Input form (SPG, mobile) dengan timestamp server | Masuk |
| Daftar data, filter, dan pencarian | Masuk |
| Dashboard EO dan client | Masuk |
| Audit log perubahan penting | Masuk |
| Export CSV | Usulan masuk, perlu persetujuan |
| Upload foto dan video dokumentasi | Perlu keputusan |
| Penjualan, SKU, merchandise, stok | Ditunda |
| Event multi-brand, role TL, Crew, Logistic | Ditunda |
| Check-in, GPS, offline, notifikasi, PPTX otomatis | Ditunda |

## Alur kerja dan siklus status

Alur utama: EO membuat client, produk, campaign, dan form; EO menugaskan SPG; SPG mengirim data baris demi baris; EO memantau dan mengoreksi; client melihat data yang diizinkan; EO menutup campaign.

&#91;embedded content: siklus status · 4 objek\]

Submission yang sudah dikirim tidak bisa ditimpa. Di hari yang sama SPG bisa memperbaiki (baris lama di-void, baris baru dibuat) atau membatalkan dengan alasan; setelah itu koreksi dilakukan EO dengan void dan alasan (ADR 003).

## User stories dan kriteria penerimaan

Setiap story dianggap selesai hanya jika kriteria fungsional dan tes akses-nya lulus.

| ID | Sebagai | Saya ingin | Kriteria penerimaan |
| --- | --- | --- | --- |
| U1 | EO | membuat client dan akun client | Client tersimpan; akun client hanya terikat ke client itu; password di-hash |
| U2 | EO | menambah produk milik client | Produk hanya terlihat oleh EO dan client pemiliknya |
| U3 | EO | membuat campaign dengan tanggal, lokasi, dan produk | Campaign berstatus draft sampai diaktifkan; tanggal selesai tidak boleh sebelum tanggal mulai |
| U4 | EO | membuat form dengan tipe field dasar | Field dapat diurutkan; form bisa dipratinjau; form terkunci setelah submission pertama |
| U5 | EO | menugaskan SPG ke campaign, lokasi, dan jadwal | Sistem menolak jadwal SPG yang bentrok |
| U6 | SPG | melihat tugas yang ditugaskan kepada saya | Hanya assignment milik saya yang tampil; akses ke ID lain ditolak |
| U7 | SPG | mengisi form dan langsung menambah baris berikutnya | Field wajib divalidasi di server; timestamp diisi server; baris muncul di daftar hari ini |
| U8 | SPG | melihat data yang sudah saya kirim | Hanya submission saya yang tampil; tidak bisa ditimpa; perbaiki/batalkan hanya di hari yang sama |
| U9 | EO | melihat, memfilter, dan mengoreksi data | Filter tanggal, lokasi, SPG; pembatalan (void) wajib beralasan dan tercatat di audit log |
| U10 | Client | melihat data campaign saya | Hanya campaign client saya; hanya field yang ditandai terlihat untuk client |
| U11 | Client | melihat ringkasan dan grafik | Angka dihitung dari data yang sama dengan daftar data |
| U12 | EO | mengekspor data ke CSV (jika disetujui) | File memuat kolom form dan timestamp; mematuhi aturan visibilitas |

## Form builder dan input SPG

Struktur form disimpan sebagai data, dan jawaban SPG disimpan sebagai JSON per baris. Tidak ada tabel database baru yang dibuat saat EO membuat form.

**Tipe field MVP**

| Tipe | Contoh | Catatan |
| --- | --- | --- |
| Teks pendek | Nama, Domisili, Akun Instagram | Bisa ditandai identitas (data personal) |
| Teks panjang | Catatan | Tidak bisa dipakai di dashboard |
| Angka | Usia | Mendukung nilai minimum dan maksimum |
| Nomor telepon | No Handphone | Validasi format |
| Pilihan tunggal | Pekerjaan | Opsi ditentukan EO |
| Pilihan ganda | Varian yang dicoba | Opsi ditentukan EO |
| Ya/Tidak | Mengonsumsi CSD minimal 2x sebulan | Tampil sebagai tombol cepat |
| Tanggal | Tanggal lahir |  |

**Pengaturan per field:** label, kunci unik, wajib atau tidak, urutan, terlihat untuk client, ditandai identitas, dipakai di dashboard.

**Pengaturan per form:** nama, versi, status (draft, published, locked), consent wajib atau tidak beserta teksnya, dan aturan kualifikasi sederhana (misalnya konsumen lolos jika semua pertanyaan Ya/Tidak terjawab Ya).

**Aturan penting**

1. Tanggal, area, lokasi, dan nama SPG tidak diisi manual. Semuanya diambil dari assignment dan akun SPG.
2. `submitted_at` diisi oleh server saat data disimpan dan tidak bisa diubah, bukan dari jam ponsel SPG.
3. Form yang sudah punya submission tidak boleh diubah. Perubahan membuat versi baru, dan data lama tetap terbaca dengan versi lamanya.
4. Validasi dibangun dari definisi field dan dijalankan di backend. Validasi di frontend hanya untuk kenyamanan.
5. SPG bisa mengirim banyak baris per assignment. Setelah simpan, tombol "Simpan & tambah berikutnya" membuka form kosong.
6. SPG tidak bisa menimpa data setelah dikirim. Di hari yang sama SPG bisa memperbaiki atau membatalkan (void + baris pengganti, tercatat di audit log); setelah itu koreksi dilakukan EO dengan void dan alasan.
7. Jika consent diaktifkan, SPG wajib mencentang sebelum menyimpan, dan versi teks consent ikut tersimpan.

**Contoh: form Sampling Kopi Lepas Haus**

| Urutan | Label | Tipe | Wajib | Identitas |
| --- | --- | --- | --- | --- |
| 1 | Nama | Teks pendek | Ya | Ya |
| 2 | Usia | Angka | Ya | Tidak |
| 3 | No Handphone | Nomor telepon | Ya | Ya |
| 4 | Pekerjaan | Teks pendek | Ya | Tidak |
| 5 | Domisili | Teks pendek | Ya | Tidak |
| 6 | Akun Instagram | Teks pendek | Tidak | Ya |
| 7 | Mengunjungi POI minimal 2x seminggu | Ya/Tidak | Ya | Tidak |
| 8 | Biasa bekerja, belajar, atau berlibur di area tersebut | Ya/Tidak | Ya | Tidak |
| 9 | Mengonsumsi CSD minimal 2x sebulan | Ya/Tidak | Ya | Tidak |

## Dashboard dan pelaporan

Metrik inti dihitung dari kolom tetap, sedangkan grafik dari field custom hanya muncul untuk field yang ditandai khusus oleh EO.

**Metrik inti (tanpa konfigurasi)**

- Total submission per campaign, per hari, per lokasi, dan per SPG.
- Jumlah konsumen yang lolos kualifikasi (jika aturan kualifikasi aktif).
- Status assignment (terjadwal, aktif, selesai, batal).
- Submission yang dibatalkan (void).

**Metrik dari field custom**

- Hanya field bertipe pilihan, Ya/Tidak, atau angka yang bisa dipakai, dengan centang "tampilkan di dashboard".
- Pilihan dan Ya/Tidak ditampilkan sebagai hitungan per opsi; angka ditampilkan sebagai jumlah atau rata-rata.
- Nilai field dashboard disalin ke tabel indeks `submission_field_values` saat submit, sehingga agregasi cepat di MySQL maupun PostgreSQL.

**Filter:** rentang tanggal, lokasi, dan SPG (khusus EO). Client memakai filter tanggal dan lokasi.

**Daftar data:** tabel dengan pencarian dan pagination. Client hanya melihat kolom dari field `visible_to_client`. Field identitas bisa dimasking untuk client (keputusan terbuka).

**Export:** CSV dari daftar yang sedang difilter, mengikuti aturan visibilitas yang sama dengan layar.

## Model data dan ERD

&#91;embedded content: ERD · 11 tabel utama\]

Panah menunjukkan relasi satu ke banyak dari atas ke bawah, kecuali campaigns dan products yang N:N lewat tabel campaign\_products. Skema final harus divalidasi ulang setelah pertanyaan terbuka dijawab.

**Aturan data**

- `users.client_id` terisi hanya untuk akun client; EO dan SPG bernilai null.
- `form_submissions` menyimpan `campaign_id`, `assignment_id`, `template_id`, `submitted_by`, `answers` (JSON), `submitted_at` (server), dan `status`.
- `submission_field_values` hanya diisi untuk field yang ditandai dashboard, sebagai salinan untuk laporan.
- Client, produk, dan campaign memakai soft delete.
- `audit_logs` bersifat polimorfik: pengguna, aksi, nilai lama, nilai baru, dan waktu.

## Matriks hak akses

Setiap aksi dicek di server dengan Policy dan query scope. Menyembunyikan menu di frontend tidak dianggap sebagai pembatasan akses.

| Aksi | EO | Client | SPG |
| --- | --- | --- | --- |
| Kelola user, client, dan akun client | Penuh | Tidak | Tidak |
| Produk | Penuh | Lihat milik sendiri | Lihat produk campaign yang ditugaskan |
| Campaign dan lokasi | Penuh | Lihat milik sendiri | Lihat yang ditugaskan |
| Buat dan ubah form | Penuh | Tidak | Tidak |
| Assignment SPG | Penuh | Tidak | Lihat milik sendiri |
| Isi form | Penuh | Tidak | Hanya assignment aktif miliknya |
| Lihat data submission | Semua | Campaign sendiri, field yang diizinkan | Submission sendiri |
| Ubah atau batalkan submission | Void dengan alasan dan audit log | Tidak | Tidak |
| Dashboard | Semua | Campaign sendiri | Tidak |
| Export CSV | Penuh | Usulan: campaign sendiri, jika disetujui | Tidak |
| Audit log | Penuh | Tidak | Tidak |

**Aturan pengujian wajib**

- Client A tidak bisa membuka campaign, produk, atau submission Client B lewat ID mana pun (uji IDOR).
- SPG tidak bisa membuka assignment atau submission SPG lain.
- Client tidak pernah menerima field yang tidak ditandai terlihat, termasuk lewat export.

## Kebutuhan non-fungsional

| Area | Kebutuhan |
| --- | --- |
| Keamanan | Otorisasi di server untuk setiap aksi; hashing password; sesi aman; HTTPS; proteksi CSRF; rate limiting login |
| Integritas data | Timestamp server; submission tidak bisa diedit, hanya di-void; audit log untuk perubahan form, assignment, dan void |
| Privasi | Hanya kumpulkan data yang diperlukan; field identitas dapat dimasking; consent dicatat dengan versi teksnya; konfirmasi kepatuhan UU PDP No. 27 Tahun 2022 ke pihak legal |
| Mobile | Form SPG dapat dipakai satu tangan di ponsel; target besar untuk tombol Ya/Tidak; diuji di minimal dua ukuran layar |
| Performa | Daftar data memakai pagination; dashboard memakai tabel indeks; target waktu respons belum ditetapkan |
| Ketersediaan | Backup database terjadwal dan log error aktif sejak hari pertama; uji pemulihan sebelum go-live |
| Pengujian | Tes otomatis untuk aturan bisnis kritis dan akses lintas role dan lintas client |
| Deployment | Shared hosting dengan migrasi untuk setiap perubahan skema; tidak ada perubahan skema manual di production |

Angka volume (jumlah SPG, submission per hari) dan target performa menunggu jawaban stakeholder.

## Arsitektur dan tech stack

Aplikasi dibangun sebagai modular monolith: satu aplikasi Laravel dengan antarmuka React lewat Inertia, tanpa microservice dan tanpa REST API terpisah di MVP.

| Lapisan | Pilihan |
| --- | --- |
| Backend | Laravel, Eloquent, Form Request, Policy, middleware |
| Frontend | React, TypeScript, Inertia.js, Tailwind CSS, shadcn/ui |
| Database | PostgreSQL diutamakan; MySQL/MariaDB jika hosting mensyaratkan |
| Hosting awal | Shared hosting; VPS atau managed hosting di masa depan |

**Prinsip**

- Aturan bisnis (validasi form dinamis, kualifikasi, status) berada di layer service Laravel, bukan di komponen React, agar dapat dipakai ulang oleh API di masa depan.
- Modul dipisah menurut domain: Klien, Produk, Campaign, Form, Assignment, Submission, Pelaporan.
- Perubahan skema selalu lewat migrasi.
- Hindari abstraksi spekulatif dan optimasi dini.
- Dokumentasikan keputusan arsitektur penting dalam catatan singkat.

## Rencana fase dan vertical slice

Pengembangan berjalan per vertical slice. Setiap slice mencakup migrasi, validasi backend, otorisasi, UI, dan tes, dan hanya dianggap selesai jika tes aksesnya lulus. Estimasi waktu belum dibuat karena deadline dan ukuran tim belum diketahui.

| Fase | Isi | Syarat selesai |
| --- | --- | --- |
| 1. Foundation | Setup Laravel, Inertia, React, TS, Tailwind, shadcn/ui; autentikasi; 3 role; Policy; seeder; kerangka tes akses | Login per role dan tes akses dasar lulus |
| 2. Master data | 2.1 Client dan akun client; 2.2 Produk; 2.3 Campaign dan lokasi; 2.4 Assignment SPG dan cek bentrok jadwal | Tes isolasi antar client lulus |
| 3. Form dan input | 3.1 Form builder EO; 3.2 Halaman SPG mobile; 3.3 Validasi dinamis, timestamp server, consent | Form Mayora dapat dibuat dan diisi end-to-end |
| 4. Monitoring | Daftar data dengan filter; tampilan client dengan masking; dashboard; export CSV (jika disetujui) | Client hanya melihat field yang diizinkan |
| 5. QA dan deploy | Tes lintas role, uji mobile, konfigurasi production, backup, log | Checklist production terpenuhi |

Upload foto dan video menjadi slice tambahan di fase 3 jika diputuskan masuk MVP.

## Risiko dan mitigasi

| Risiko | Dampak | Mitigasi |
| --- | --- | --- |
| Sinyal internet buruk di venue event | SPG gagal menyimpan data | Tanyakan kondisi venue sejak awal; offline ditunda tetapi dicatat sebagai risiko utama |
| Form builder terlalu rumit | Jadwal mundur | Batasi ke 8 tipe field tanpa logika kondisional |
| Data personal bocor ke client yang salah | Pelanggaran privasi dan UU PDP | Policy dan query scope, masking field identitas, tes IDOR otomatis |
| Form diubah setelah data masuk | Data lama tidak konsisten | Penguncian form dan versioning |
| Hosting shared membatasi PHP, upload, atau JSON | Fitur tertunda | Konfirmasi hosting sebelum fase 1 |
| Kebutuhan penjualan muncul cepat | Skema perlu diubah | Domain dipisah per modul; penjualan dirancang sebagai modul terpisah |

## Pertanyaan terbuka

1. Apakah upload foto dan video dokumentasi masuk MVP?
2. Apakah client boleh melihat data personal konsumen (nama, HP, Instagram), atau hanya data yang dimasking atau agregat?
3. ~~Apakah SPG boleh mengedit data setelah dikirim?~~ Diputuskan: boleh perbaiki/batalkan di hari yang sama lewat void + baris pengganti (ADR 003).
4. Apakah export CSV wajib di MVP?
5. Hosting dan database apa yang tersedia (MySQL atau PostgreSQL, versi PHP, batas upload)?
6. Berapa jumlah SPG, submission per hari, dan ukuran media yang diperkirakan?
7. Apa deadline MVP dan berapa orang tim pengembang?
8. Apakah satu SPG boleh bekerja di beberapa campaign pada hari yang sama?
9. Apakah satu campaign boleh memiliki beberapa produk dan beberapa template form?
10. Bagaimana kebijakan retensi data konsumen setelah campaign ditutup?
