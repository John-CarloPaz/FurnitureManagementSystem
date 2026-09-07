# Module — User Management, Roles & Invitations

Lets a **super admin** define roles by ticking exactly what they can access, and lets a super admin or admin **invite** teammates by email. Invitees set their own name + password from an email link and land in the app with the assigned role.

## Responsibilities
- **Role builder** — super admin creates/edits/deletes custom roles from a Create/Read/Update/Delete matrix (core models) plus action toggles (workflow areas). The eight seeded *system* roles are locked.
- **User management** — list team members, change a user's role, activate/deactivate, remove.
- **Invitations** — issue an email invite for `(email, role)`, track pending/accepted/expired, revoke, copy the accept link.
- **Public accept flow** — token link → invitee sets name + password → active user, auto-signed-in.

## Design
- **Single source of truth:** [`app/Domain/Access/PermissionCatalog.php`](../../backend/app/Domain/Access/PermissionCatalog.php) lists every permission grouped by resource, each tagged `crud` (renders as a C/R/U/D column) or `action` (renders as a chip). The seeder, the `GET /permissions` endpoint, and the frontend matrix all consume it, so enforcement and UI never drift.
- **Two admin tiers:** `super_admin` (seeded from `ADMIN_*`) may mutate roles and grant any role; `admin` can do everything else, including inviting into non-privileged roles. Enforced by the `roles.create` permission and `StoreInvitationRequest`.
- **Single-use tokens:** an invitation carries a random 64-char token, an expiry, and `accepted_at`. Accepting is idempotent-safe (checked `isPending()`), and reissuing an invite for the same email rotates the token (`updateOrCreate`).
- **Email transport is swappable:** [`BrevoMailer`](../../backend/app/Domain/Access/Services/BrevoMailer.php) posts to Brevo's HTTP API. With no `BREVO_API_KEY` it logs and returns `false`, and the UI falls back to a copyable accept link — so the flow works in every environment.

## Key files
| Layer | Files |
|---|---|
| Catalog | `Domain/Access/PermissionCatalog.php` |
| Invitations | `Domain/Access/Models/Invitation.php` · `Actions/{CreateInvitationAction,AcceptInvitationAction,SendInvitationEmail}.php` · `Services/BrevoMailer.php` |
| HTTP | `Http/Controllers/Api/{RoleController,InvitationController,UserController}.php` · requests under `Http/Requests/{Roles,Invitations,Users}` · `Http/Resources/{RoleResource,InvitationResource}.php` |
| Config | `config/invitations.php` · `config/services.php` (`brevo`) |
| Frontend | `pages/{roles-page,users-page,accept-invitation-page}.tsx` · `components/access/{permission-matrix,role-form,invite-user-form}.tsx` · `lib/{roles-api,invitations-api,users-api}.ts` · `hooks/{use-roles,use-invitations,use-users}.ts` |

## API
| Method | Path | Guard |
|---|---|---|
| GET | `/api/v1/permissions` | `roles.viewAny` — the builder matrix |
| GET/POST | `/api/v1/roles` | `roles.viewAny` / `roles.create` |
| PATCH/DELETE | `/api/v1/roles/{role}` | `roles.update` / `roles.delete` (system roles locked → 422) |
| GET | `/api/v1/users` | `users.view` |
| PATCH/DELETE | `/api/v1/users/{user}` | `users.update` / `users.delete` (not self) |
| GET/POST | `/api/v1/invitations` | `invitations.viewAny` / `invitations.create` |
| DELETE | `/api/v1/invitations/{invitation}` | `invitations.revoke` |
| GET/POST | `/api/v1/invitations/accept/{token}` | **public** — token is the credential |

## Environment
```
FRONTEND_URL=https://<your-app>.vercel.app   # SPA origin the accept link points at
BREVO_API_KEY=xkeysib-...                     # empty → copy-link fallback
MAIL_FROM_ADDRESS=no-reply@cedarside.local    # Brevo sender
MAIL_FROM_NAME="Cedarside Holding Corp."
INVITATION_EXPIRES_DAYS=7
```

## Tests
`backend/tests/Feature/RoleManagementTest.php` (role builder, system-role locks, validation) · `InvitationTest.php` (invite + Brevo send, privilege guard, accept, expiry, single-use, revoke) · `frontend .../permission-matrix.test.tsx` (matrix render + toggle).
