# ADR 006: Geofenced public forms

**Status:** Accepted
**Date:** 2026-10-23 (pre-check added 2026-10-26)
**Builds on:** ADR 004 (public location forms)

## Context

A public link works from anywhere, so a shared link or a photo of the QR code would let people submit entries without visiting the booth. Entries are the basis of the client report, so off-site entries must be rejected.

## Decision

- Each campaign location has a map pin (`latitude`, `longitude`) and a `radius_meters`, which defaults to 300. A location without a pin is not geofenced.
- **The server is the authority.** `App\Domain\Submissions\Services\Geofence` computes the haversine distance between the device position sent with the entry and the pin. It rejects the entry when `distance − min(accuracy, 100 m) > radius`. Capping the accuracy credit at 100 m stops a vague GPS fix from covering a whole city. The distance and accuracy are stored on the entry.
- **The browser checks first.** The public form reads the position when the page opens, using the same formula (`resources/js/lib/geo.ts`). Off-site visitors see "Outside the event area" instead of the form, so nobody fills in a form only to be rejected at the end. The position is read again right before submitting.
- The location's coordinates are sent to the public page for this check. Venues are public places, so this is acceptable.
- Geolocation needs HTTPS. Behind a reverse proxy or tunnel, `TRUSTED_PROXIES` must be set so Laravel sees the request as HTTPS (see the production checklist).

## Consequences

- Visitors who deny location access cannot submit. The page explains how to allow access.
- Indoor GPS can be off by tens of metres, so the radius should be checked on site before go-live.
- A determined user can fake a browser position. The geofence stops casual sharing of links, not deliberate fraud. Suspicious entries are marked invalid (ADR 002).
