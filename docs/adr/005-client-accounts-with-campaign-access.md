# ADR 005: Client accounts with direct campaign access (no client companies or products)

**Status:** Accepted
**Date:** 2026-10-23
**Builds on:** ADR 004 (public location forms)

## Context

Clients only read results: the dashboard and the entries of their campaigns. Modelling them as companies (`clients`) that own products and campaigns added setup steps for the EO (create company → products → campaign → account) without adding value, since products were only labels on a campaign and never used in forms or reports.

## Decision

- **No client companies.** A client is a read-only user account. When creating or editing the account, the EO ticks the campaigns it may view (`campaign_user` pivot, `Campaign::viewers()` / `User::campaigns()`).
- **No products.** The Products module, `products` and `campaign_products` tables are removed; campaigns no longer have a `client_id`.
- **Client access = Dashboard + Entries (incl. Excel export)** for granted campaigns only. Campaign management, forms, public links, users and audit log are EO-only (the superadmin, and since ADR 007 partly the admin role).
- Access changes take effect immediately because visibility is checked against the pivot on every request (`Campaign::scopeVisibleTo`, `User::canViewCampaign`).

## Migration

The migrations were squashed into one migration per final table before the first deploy, so the schema is created in its final shape and there is no data migration for this change.

## Consequences

- A brand with several people gets several accounts, each granted the same campaigns; there is no company-level grouping.
- Older audit-log entries for client/product actions keep their labels so history stays readable.
