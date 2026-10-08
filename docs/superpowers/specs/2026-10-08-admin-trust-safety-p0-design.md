# Admin Trust & Safety P0 — Design

Date: 2026-10-08
Status: draft for review (no implementation yet)
Scope: P0 only — admin foundation, user suspend, support inbox.
Roadmap context: P1 moderation, P2 money disputes, P3 hardening (see section 9).

## 1. Intent

Give the solo operator trust-and-safety power inside YieldGrid:
stop abusive users fast and answer support messages without touching
the database by hand.

Success criteria:

- Admin-only `/api/v1/admin` group; every route returns 403 for
  farmer, buyer, and guest callers.
- Suspended users cannot log in and their existing tokens stop working.
- Contact messages can be listed, read, replied to, and closed from
  the admin UI.
- Nobody can self-register as admin; the first admin is created via
  a server-side console command.

## 2. Constraints

- Solo operator; no staff RBAC and no audit log in P0.
- Follow project rules: DDD namespaces per neighboring files, Actions
  hold logic, FormRequest limits from constants, ConfirmModal for
  mutations, design tokens only, ≤30-line methods.
- `Users` and `Contact` contexts use the `Domain\...` namespace prefix
  (not `App\Domain\...`); new files in those contexts follow the
  neighbors. No namespace cleanup in this change.
- No new secrets in the repo.

## 3. Decisions

| # | Decision | Choice |
|---|----------|--------|
| 1 | Delivery | In-app `ADMIN` role + fenced API + minimal Vue section |
| 2 | Registration guard | Role allowlist farmer/buyer; `ADMIN` rejected with 422 |
| 3 | First admin | `admin:promote {email}` artisan command (console only) |
| 4 | Suspend enforcement | Login denial + revoke all tokens + request backstop middleware |
| 5 | Self-harm guard | Admins cannot suspend themselves or other admins (422) |
| 6 | Support reply | Queued email to the sender + `replied_at`/status update |

Rejected: Filament (banned Blade/Livewire stack, fights DDD and the
design system); manual SQL/artisan-only ops (no guardrails, error-prone).

## 4. Backend

- `UserRole`: add `ADMIN = 'admin'` case.
- Migration: `suspended_at` (nullable timestamp) + `suspended_reason`
  (nullable string 500) on `users`.
- `RegisterRequest`: replace `Rule::enum` with an allowlist of farmer
  and buyer so `admin` fails closed even though the enum case exists.
- `LoginUserAction`: after credential check, throw
  `AccountSuspendedException` (renders 403, message includes the reason
  when set) before any token is created.
- New `EnsureUserNotSuspended` middleware (mirrors
  `EnsureUserHasRole`): runs inside the `auth:sanctum` group, uses the
  already-loaded user row (no extra query), deletes the offending
  token, returns 403.
- Admin group `Route::prefix('admin')` inside `auth:sanctum` +
  `EnsureUserHasRole::class.':admin'`:
  - `GET /admin/users?search=&suspended=` — paginated lookup.
  - `POST /admin/users/{user}/suspend` — reason required (max 500),
    sets fields, deletes all Sanctum tokens. 409 when already
    suspended, 422 for self/other-admin.
  - `POST /admin/users/{user}/unsuspend` — clears fields. 409 when
    not suspended.
  - `GET /admin/contact-messages?status=` — paginated, filterable.
  - `GET /admin/contact-messages/{message}` — single thread.
  - `POST /admin/contact-messages/{message}/reply` — body required
    (max 5000), queued mail, sets `replied_at`/status.
  - `POST /admin/contact-messages/{message}/close` — terminal status.
- Contact status enum is `unread`/`read`/`replied` (default `unread`);
  extend it with `closed` via migration. Admin show marks `read`, reply
  sets `replied`, close sets `closed`. Expose the values as backend and
  frontend constants.
- Resources: extend `UserResource` with suspension fields (own record);
  admin lookup returns an `AdminUserResource` with full fields.

## 5. Frontend

- Router: `/admin` group with `meta: { role: 'admin' }` — the existing
  `beforeEach` role check enforces it with no guard changes. Add an
  admin branch to the home/guest and unverified redirects (today the
  else-branch assumes farmer); API 403 stays the real guard.
- `AdminUsersPage`: search, suspend/unsuspend via shared danger
  `ConfirmModal` (`pendingConfirm` + `confirmConfig` pair), reason
  textarea with maxlength + live counter.
- `AdminSupportPage`: message list, thread view, reply box with
  maxlength + counter, close action with confirmation.
- Reuse `DashboardLayout`, `AppButton`, `AppCard`, `FormField`,
  `SkeletonCard`, `EmptyState`; errors render inside the dialog with
  retry; failed drafts preserved; responsive + keyboard verified.

## 6. Error contract

- 403 non-admin on any `/admin/*` route (middleware).
- 403 suspended user on login or any authenticated request.
- 404 unknown user/message; 409 double suspend / unsuspend-when-clean;
  422 self/other-admin suspend, validation failures.
- Frontend mirrors limits before calling the API; backend re-validates.

## 7. Tests

- Pest: 403 matrix for every admin route (guest/farmer/buyer);
  register-as-admin → 422; suspend blocks login and kills tokens;
  unsuspend restores; self-suspend → 422; reply sends mail and stamps
  `replied_at`; boundary tests both sides of reason/reply maxima.
- Vitest: both pages render with null props; suspend/reply/close go
  through `ConfirmModal`; error-in-dialog and draft preservation.

## 8. Verification

- `php artisan test`, `./vendor/bin/pint --test`,
  `./vendor/bin/phpstan analyse --level=6`, `npm run lint`,
  Prettier check, `npx vitest run`.
- Manual: promote → login as admin → suspend test user → confirm
  their token 403s → reply to a contact message → close it.

## 9. Out of scope (P1–P3 roadmap, approved order)

- P1: forum/chat moderation, listing takedown, report queue (forum
  reports endpoint already exists as queue input).
- P2: purchase dispute view, provider refunds, cash-payment override.
- P3: audit log, failed-login/rate-limit visibility and alerts.

## 10. Risks

- Enum allowlist missed: mitigated by the register-as-admin Pest test.
- Operator self-lockout: mitigated by the self/other-admin suspend
  guards, which make UI lockout impossible; console access remains as
  break-glass.
- Reply mail latency: mitigated by queued sending.
