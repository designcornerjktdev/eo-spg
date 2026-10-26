# QA form publik (ponsel)

Konsumen membuka form dari link atau QR code lokasi campaign (`/f/{token}`), lalu mengisinya di ponsel sendiri tanpa login.

Uji manual sebelum go-live di HTTPS, di ponsel asli, dan dari lokasi venue atau dalam radius geofence. Lakukan minimal di:

- satu Android dengan Chrome (layar sekitar 360px)
- satu iPhone dengan Safari (layar sekitar 390px)

## Checklist

**Tampilan**
- [ ] Logo dan nama perusahaan, nama campaign, dan nama lokasi tampil di atas form.
- [ ] Form tetap terang walaupun ponsel memakai dark mode.
- [ ] Tidak ada scroll horizontal, dan semua field bisa diisi dengan satu tangan.
- [ ] Tombol Yes/No mudah diketuk.

**Lokasi (geofence)**
- [ ] Browser meminta izin lokasi saat halaman dibuka.
- [ ] Di dalam radius, form tampil dengan keterangan hijau "You're at …".
- [ ] Di luar radius, form tidak tampil. Yang muncul pesan "Outside the event area" beserta jaraknya dan tombol Try again.
- [ ] Kalau izin lokasi ditolak atau GPS mati, muncul pesan "Location needed" beserta cara mengaktifkannya.

**Foto**
- [ ] Field foto menawarkan pilihan Kamera atau Galeri.
- [ ] Saat foto diproses muncul "Preparing photo…", dan tombol Submit terkunci sampai prosesnya selesai.
- [ ] Pratinjau foto tampil dan bisa dihapus.
- [ ] Foto dari kamera iPhone (HEIC) berhasil terkirim.

**Kirim**
- [ ] Field wajib yang kosong menampilkan error di bawah field tersebut.
- [ ] Setelah submit muncul "Thank you!", dan tombol "Submit another response" mengosongkan form.
- [ ] Entri baru muncul di menu Entries dengan lokasi, waktu server, dan fotonya.
- [ ] Kalau campaign ditutup, link menampilkan "This form is not accepting responses right now."
