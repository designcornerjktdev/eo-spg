# ADR 004: Public form links per campaign location (SPG role removed)

**Status:** Accepted
**Date:** 2026-10-23
**Supersedes:** ADR 003 (SPG same-day correction) and the SPG/assignment parts of the PRD and ADR 002

## Context

The original flow had SPG accounts logging in, receiving time-boxed assignments per location, and entering consumer data on their behalf. The business now wants a Google Forms-style flow: consumers fill in the campaign form themselves through a link, without any account.

## Decision

- **One public link per campaign location**: `/f/{token}`, where `token` is a random 32-character `campaign_locations.public_token`. The EO shares the link or its QR code (shown on the campaign page) at the location. Per-location reporting keeps working without asking consumers where they are.
- **Consumers fill the form themselves, no login.** Entries are anonymous: `form_submissions` has `location_id` and no submitter.
- **A link accepts entries only while the campaign is Active and has a published (or locked) form.** The newest such form version is used. Otherwise the page says the form is not accepting responses.
- **Spam protection**: 10 submissions per minute per IP per link (`throttle:public-form`) and a hidden honeypot field; honeypot hits are silently dropped.
- **SPG role removed entirely**: SPG accounts, assignments (`assignments` table), the SPG task pages, SPG self-correction (`corrects_submission_id`), and the per-SPG dashboard chart. Only the EO superadmin and client accounts remain.
- Voiding by the EO (ADR 002) is unchanged and is the only way to correct an entry.

## Migration

`2026_10_23_100000_switch_to_public_location_forms` backfills `public_token` for every location, re-links existing entries to their location through their former assignment, then drops assignments, SPG authorship and SPG accounts. It is not reversible; restore a backup to go back.

## Consequences

- Entry data no longer tells who collected it. If per-SPG performance is needed again, add an optional question to the form (e.g. "Promoter name") rather than reintroducing accounts.
- Anyone with a link can submit while the campaign is active, so links should only be shared at the location; rotating a token (new link) is a possible follow-up.
- Entries cannot be self-corrected by the person who submitted them; the EO voids wrong entries.
