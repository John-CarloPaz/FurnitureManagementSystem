# Role-Based Auth & User Management

Quotation module #5. Caps §Expected Output role table, Objective #7, §Ethical Considerations (least privilege), RA 10173.

## Purpose
Authenticate users via token and enforce that each of the 7 roles can only reach the data/actions their job requires.

## Responsibilities
- Token authentication (Sanctum): login, logout, current identity.
- The 7-role + permission model (spatie), seeded from [RBAC.md](../design/RBAC.md).
- User management (list, create + assign role) — RBAC-protected.
- Role-shaped API output (roles + permissions on the user resource).

## Public API
| Method | Route | Guard | Description |
|---|---|---|---|
| POST | `/api/v1/auth/login` | public | `{email,password}` → `{ token, user }` |
| POST | `/api/v1/auth/logout` | `auth:sanctum` | revoke current token |
| GET | `/api/v1/auth/me` | `auth:sanctum` | user + roles + permissions |
| GET | `/api/v1/users` | `permission:users.view` | paginated users |
| POST | `/api/v1/users` | `permission:users.manage` | create user + assign role |

## Domain / HTTP classes
| Class | Responsibility |
|---|---|
| `RolePermissionSeeder` | creates 31 permissions, 7 roles, the matrix, and the `.env` admin |
| `Api\AuthController` | login (credential + `is_active` check → token), logout, me |
| `Api\UserController` | user list + create-with-role |
| `Http\Resources\UserResource` | shapes user incl. `getRoleNames()` + `getAllPermissions()` |
| `LoginRequest` / `StoreUserRequest` | validation |

## Enforcement layers
1. **Middleware** — `auth:sanctum` + `permission:*` on routes (aliases registered in `bootstrap/app.php`).
2. **Policies** — per-model ownership checks (added per module, e.g. `OrderPolicy` in M2).
3. **Channel auth** — same checks for Pusher private channels (M3).
4. **Resources** — role-shaped output.

## Security notes (RA 10173)
- Passwords hashed via the model's `hashed` cast; credentials checked with `Hash::check`.
- PII (`phone`, `address`) encrypted at rest via `encrypted` cast.
- Default admin seeded from `ADMIN_EMAIL`/`ADMIN_PASSWORD` env — never hardcoded; seeder skips if unset.
- Inactive accounts (`is_active=false`) cannot obtain a token.

## Frontend (SPA)
- `lib/api.ts` — axios client; bearer-token request interceptor + global 401 → clears token, redirects to `/login`.
- `lib/auth-api.ts` + `hooks/use-auth.ts` — `useMe` (TanStack Query), `useLogin`, `useLogout`, and `useAuth()` with `has()/hasAny()` permission helpers.
- `components/auth/protected-route.tsx` — redirects unauthenticated users to `/login`.
- `pages/login-page.tsx` — real login form → stores token → routes to dashboard; shows API error.
- `components/layout/sidebar.tsx` — **role-scoped nav** (items filtered by `hasAny(permissions)`), real user identity + sign-out.

## Verified
Login→token, `/me` roles+perms, `/users` 200 (admin) / 401 (no token), bad login 422, logout 200. Larastan level 6 clean. Full-stack: SPA → Vite proxy → Laravel login returns token + user. Automated tests in task 2.4.
