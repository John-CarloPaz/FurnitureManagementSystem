# Security & Data Privacy (RA 10173)

How Cedarside Holding Corp. protects proprietary and customer data, mapped to the caps' Ethical Considerations and the **Data Privacy Act of 2012 (RA 10173)**.

## Compliance checklist
| Control | Status | Where |
|---|---|---|
| **PII encrypted at rest** | ✅ | `users.phone`, `users.address`, `orders.delivery_address`, `proof_of_deliveries.recipient_name` — Laravel `encrypted` cast |
| **Passwords hashed** | ✅ | bcrypt via the model `hashed` cast; verified with `Hash::check` |
| **Token authentication** | ✅ | Laravel Sanctum; inactive accounts (`is_active=false`) cannot obtain a token |
| **Least-privilege RBAC** | ✅ | 7 roles × scoped permissions (`spatie`); enforced by middleware + policies + broadcast-channel auth ([RBAC.md](./design/RBAC.md)) |
| **Role-owned state changes** | ✅ | order transitions gated to the owning role (`TransitionOwnership`) |
| **Intellectual-property protection (3D)** | ✅ | model files served only via **1-hour signed URLs**; proof photos likewise |
| **Audit trail** | ✅ | `order_state_transitions` (who/when/from→to), `order_payments.recorded_by`, stage `operator_id` |
| **Input validation** | ✅ | Form Requests on every write; file type/size limits on uploads |
| **Output minimisation** | ✅ | role-shaped API Resources (customers never see cost/operator internals) |
| **No secrets in VCS** | ✅ | `.env` gitignored; `.env.example` holds placeholders; build caches ignored |
| **JSON error envelope (no HTML leakage)** | ✅ | forced for `api/*`; stack traces only when `APP_DEBUG=true` |

## Secrets handling
- Real credentials live only in `.env` (never committed). `phpunit.xml` reads `DB_PASSWORD` from the environment, not a literal.
- The default admin is seeded from `ADMIN_EMAIL` / `ADMIN_PASSWORD` env — the seeder skips if unset, never a hardcoded password.

## Recommended before production
- [ ] `APP_DEBUG=false`, HTTPS only, HSTS + security headers
- [ ] Rate-limit `/auth/login` and upload endpoints
- [ ] Rotate `APP_KEY` and DB credentials; use a dedicated least-privilege DB role
- [ ] Move 3D/proof files to S3 with private ACLs (signed URLs already in place)
- [ ] Add MFA for admin/manager accounts
- [ ] Run notifications on a queue worker; enable daily DB + weekly file backups
- [ ] Penetration test + dependency audit (`composer audit`, `npm audit`) in CI

## Data subject rights (RA 10173)
- **Access/portability**: a customer's data is reachable via their user record + owned orders.
- **Erasure**: deleting a user cascades tokens; order history is retained for BIR/DTI audit (legal basis) with PII fields encrypted — document a retention policy before go-live.
