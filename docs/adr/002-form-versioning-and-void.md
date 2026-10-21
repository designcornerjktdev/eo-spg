# ADR 002: Versioning form dan void submission

**Status:** Diterima  
**Tanggal:** 2026-10-20

## Keputusan

- Jawaban SPG disimpan sebagai JSON di `form_submissions.answers`. Tidak ada tabel baru per form.
- `submitted_at` diisi server saat insert dan tidak bisa diubah.
- Submission tidak bisa diedit. Koreksi EO memakai status `void` + alasan wajib + audit log.
- Form yang sudah punya submission berstatus `locked`. Perubahan skema membuat versi baru (`form_templates.version` bertambah); data lama tetap merujuk versi lama.
- Field yang ditandai dashboard disalin ke `submission_field_values` saat submit untuk agregasi cepat.
