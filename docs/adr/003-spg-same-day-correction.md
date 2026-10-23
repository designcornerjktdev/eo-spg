# ADR 003: Koreksi mandiri SPG di hari yang sama

**Status:** Digantikan oleh ADR 004 (role SPG dihapus)  
**Tanggal:** 2026-10-21  
**Mengubah:** ADR 002 (poin "Submission tidak bisa diedit")

## Konteks

Salah input di lapangan (nomor HP salah ketik, data dobel) sering terjadi. Jika semua koreksi harus lewat EO, prosesnya lambat dan membebani EO. Di sisi lain, data SPG adalah dasar laporan ke client, sehingga tidak boleh bisa diubah atau dihapus diam-diam.

## Keputusan

- SPG boleh **memperbaiki** atau **membatalkan** submission miliknya sendiri, hanya jika `submitted_at` jatuh di **hari yang sama** (zona waktu aplikasi) dan statusnya belum `void`.
- Tidak ada edit di tempat dan tidak ada hapus permanen:
  - **Perbaiki** membuat submission baru (`corrects_submission_id` menunjuk baris lama). Baris lama di-void dengan alasan `Dikoreksi SPG, diganti data #<id baru>`. Keduanya terjadi dalam satu transaksi.
  - **Batalkan** men-void baris dengan alasan wajib, disimpan sebagai `Dibatalkan SPG: <alasan>`.
- Setiap aksi tercatat di audit log (`submission.voided`, `submission.corrected`).
- Lewat hari itu, koreksi tetap hanya lewat void oleh EO (ADR 002).
- Logika ada di `App\Domain\Submissions\Services\SubmissionCorrector`; batas waktu di `FormSubmission::isCorrectableBySubmitter()`.

## Konsekuensi

- Riwayat lengkap tetap terbaca oleh EO dan client; angka laporan hanya menghitung baris non-void.
- Perbaikan tetap melewati validasi form dan cek jadwal assignment yang sama seperti input baru.
