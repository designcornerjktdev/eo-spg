# ADR 007: Admin role for EO operations staff

**Status:** Accepted
**Date:** 2026-10-26
**Builds on:** ADR 005 (client accounts)

## Context

Only one EO account (the superadmin) could run campaigns. Operations staff needed to set up campaigns, forms and entries without also being able to manage accounts or view the audit log.

## Decision

- New role `admin` (`UserRole::Admin`). `User::isStaff()` is true for both the superadmin and admins.
- **Admins see every campaign.** They are not limited by `campaign_user` grants, which apply only to clients. Visibility scopes and policies check `isStaff()`.

  | Action | Superadmin | Admin | Client |
  | --- | --- | --- | --- |
  | Campaigns: create, edit, activate, close | yes | yes | no |
  | Campaigns: delete | yes | no | no |
  | Forms | yes | yes | no |
  | Entries: view, export, mark invalid | yes | yes | view and export only, granted campaigns |
  | Settings → Company | yes | yes | no |
  | Users, audit log | yes | no | no |

- **Sign-in.** Admins sign in with email or username, and can reset their own password by email (`UserRole::withEmailLogin()`). Clients sign in with a username only and cannot use self-service reset, even if their account has an email.
- The superadmin creates admins under **Users**. Email is required for admins and not allowed for clients.

## Consequences

- No migration is needed, because `users.role` is a string column.
- Admins see identity fields unmasked, like the superadmin. Masking (`mask_identity_for_client`) still applies to clients only.
