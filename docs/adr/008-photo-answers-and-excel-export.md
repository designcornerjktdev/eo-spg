# ADR 008: Photo answers and Excel export

**Status:** Accepted
**Date:** 2026-10-26
**Supersedes:** the CSV export (PRD, ADR 005)

## Context

Booths want documentation photos (for example a receipt, or a visitor with the product) attached to entries. Clients also want photos in the report itself, and a CSV can only hold text.

## Decision

**Photo field**
- New field type `image` ("Image upload"). The answer stores the path of a private file. It never appears on the dashboard.
- Uploads accept JPG, PNG, WebP and HEIC up to 10 MB (`DynamicFormValidator::MAX_IMAGE_KILOBYTES`).

**Optimisation in two layers**
- The browser (`resources/js/lib/image.ts`) resizes the photo to at most 1600 px on its longest side and encodes it as WebP at 80% quality before upload. It falls back to JPEG where WebP encoding is not supported, and to the original file where the browser cannot decode it.
- The server (`ImageOptimizer`) re-encodes with GD to WebP at 1600 px. It keeps a file as uploaded when GD cannot read it (for example HEIC).
- The submit button is locked while a photo is still being prepared.

**Storage and access**
- Files are stored on the `local` disk (`storage/app/private/submissions/{campaign}`) and are never public.
- `GET /submissions/{id}/files/{field}` serves a file only to users who can view the entry. Clients are also blocked when the field is not `visible_to_client`.
- If saving the entry fails, the stored files are deleted.

**Excel export replaces CSV**
- `/submissions/export` downloads an `.xlsx` file (PhpSpreadsheet). It uses the same columns and field visibility as the screen (`SubmissionTable`) and follows the active filters.
- Photos appear as JPEG thumbnails generated during the export, because Excel cannot show WebP. Each photo column is followed by a "<label> (link)" column holding the full-size photo URL (`GET /submissions/{id}/files/{field}`) as a clickable link; the thumbnail links there too. The link only opens for a signed-in user who may view that entry.
- Entries marked invalid are left out of the export.

**Multipart booleans**
- Forms with a file are posted as multipart, which sends booleans as "1"/"0". The recorder turns Yes/No answers back into real booleans, so qualification stays correct.

## Consequences

- `storage/` must be persistent and backed up. Photos are not in Git or the database.
- Exporting thousands of photos takes noticeably more time and memory. Hundreds are fine.
- Photos uploaded before the optimiser existed stay in their original format.
