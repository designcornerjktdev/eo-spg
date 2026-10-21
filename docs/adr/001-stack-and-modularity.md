# ADR 001: Stack, database, dan konvensi modul

**Status:** Diterima  
**Tanggal:** 2026-10-20

## Keputusan

- Satu aplikasi Laravel 12 (modular monolith) dengan Inertia v2 + React + TypeScript + Tailwind + shadcn/ui.
- Tidak ada REST API terpisah di MVP. Aturan bisnis hidup di service Laravel (`app/Domain`) agar bisa dipakai ulang nanti.
- Eloquent models tetap di `app/Models` supaya factory, policy discovery, dan artisan tetap konvensional. Service domain di `app/Domain/{Modul}/Services`.
- Database development/test memakai SQLite (adapter-friendly). PostgreSQL adalah target production yang diutamakan; MySQL dipakai jika shared hosting mensyaratkan. Hindari fitur khusus satu engine (JSONB operator, array Postgres, dsb).
- Bahasa UI: Indonesia.
