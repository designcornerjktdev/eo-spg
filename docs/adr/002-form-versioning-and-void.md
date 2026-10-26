# ADR 002: Versioning form dan void submission

**Status:** Diterima  
**Tanggal:** 2026-10-20

> **Pembaruan:** sejak ADR 004 jawaban berasal dari konsumen lewat form publik, bukan dari SPG. Di UI, "void" sekarang disebut **Mark as invalid** dan berstatus **Invalid**. Entri Invalid tidak dihitung di dashboard dan tidak ikut ter-export (ADR 008). Admin juga bisa menandai entri invalid (ADR 007).

## Keputusan

- Jawaban SPG disimpan sebagai JSON di `form_submissions.answers`. Tidak ada tabel baru per form.
- `submitted_at` diisi server saat insert dan tidak bisa diubah.
- Submission tidak bisa diedit. Koreksi EO memakai status `void` + alasan wajib + audit log.
- Form yang sudah punya submission berstatus `locked`. Perubahan skema membuat versi baru (`form_templates.version` bertambah); data lama tetap merujuk versi lama.
- Field yang ditandai dashboard disalin ke `submission_field_values` saat submit untuk agregasi cepat.
