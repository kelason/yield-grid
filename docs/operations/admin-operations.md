# Admin operations runbook

YieldGrid admin workspace: dedicated operator accounts, inquiry inbox with
persistent reply delivery, one content-report queue, reversible moderation,
member issue tickets, and the operational overview. This runbook covers
account bootstrap, queues, the report-table cutover, rollback boundaries,
and known limits.

Safety rules:

- Never run provisioning, migration, or recovery steps against production
  unless a separate explicit operational action authorizes it. Rehearse on a
  disposable copy first.
- Recovery of a locked-out operator account happens through console access.
  There is no public recovery bypass, and this runbook gives no direct-SQL
  account recipe.
- After real tickets, replies, reports, or history rows exist, recovery is
  forward-only. Destructive down migrations are not a safe rollback path
  (see Rollback boundaries).

## 1. Account bootstrap

Create each operator account explicitly from the application console:

```sh
cd backend
php artisan admin:create operator@example.test
```

Behavior, verified from `CreateAdminCommand` and `CreateAdminAction`:

- The command takes only the email argument. It prompts for a display name
  and a hidden password plus hidden confirmation. There is no password CLI
  option, no credential output, and no default or seeded credential.
- The admin password must be 12-255 characters. Name and email reuse the
  existing registration bounds.
- A farmer/buyer email is rejected: existing trading identities are never
  converted or promoted, and an existing admin address is an informational
  no-op that changes nothing (no password reset, no token issued).
- The account is created unverified with no farm or address requirement,
  and the existing verification notification is dispatched after commit.
  Admin endpoints require a verified email; an unverified admin can still
  use verification and logout.
- Delivery failure of the verification mail leaves the account recoverable:
  the operator signs in and uses resend
  (`POST /api/v1/email/verification-notification`, throttled `6,1`).

Token and session behavior:

- Admin Bearer tokens expire 120 minutes after login, regardless of
  remember-me (`AdminConstants::ADMIN_TOKEN_EXPIRATION_MINUTES`).
- The Sanctum global cap is 43,200 minutes (30 days). Ordinary
  non-remembered tokens keep their shorter lifetime; older remembered
  tokens keep working until they expire or are revoked, then the next
  login mints under the new lifetimes.
- Suspension revokes every personal token immediately. Unsuspension does
  not restore tokens: the operator signs in again.
- Routine password recovery reuses the current forgot/reset-password flow.
  There is no HTTP create-admin, promotion, demotion, impersonation, or
  user-deletion endpoint.

## 2. Queues and reply delivery

Contact replies are persisted first and delivered by `SendContactReplyJob`.
HTTP 202 means "persisted for delivery", not "delivered". Each reply row
carries `delivery_status` (`queued|sending|sent|failed`), a lifetime
`attempts` counter, and a monotonic `delivery_generation` that invalidates
older claims.

Tuning (`ContactConstants`, `config/queue.php`):

- Job tries: 3. Retry backoff: 60 s, then 300 s. Job timeout: 60 s.
- Queue `retry_after` is 90 s (above the 60 s timeout).
- A reply stuck in `sending` for more than 300 s (5 minutes) is treated as
  a stale lease and becomes recoverable through Retry.

Operator recovery (in-app, per reply, parent-scoped):

- Dispatch failure (queue throws at send time) leaves the row `failed`
  with attempts preserved; use Retry on the same row. Retry never creates
  another reply row.
- Ordinary transport failure returns the row to `queued` with a sanitized
  `error_code` (`transport_failed`); exhausted attempts become `failed`.
- Stale `queued` rows retry silently. Stale `sending` rows retry with the
  duplicate-delivery caution: the previous attempt may already have been
  delivered, and retrying can send a duplicate. Retry bumps
  `delivery_generation` so a late worker from the old generation cannot
  overwrite the newer attempt or a closed inquiry.
- Close is blocked while any `queued`/`sending` row exists; recover it
  through Retry first. `replied_at`/`status=replied` are set only after
  transport success.

SMTP is at-least-once: a crash after the server accepts the message
duplicates on retry. Do not promise exactly-once delivery, inbox arrival,
or read receipts.

## 3. Report-table cutover

`forum_reports` is renamed to `content_reports` in migration
`2026_10_08_000004_generalize_content_reports`, together with six sibling
migrations (admin action log, suspension columns, contact workflow,
moderation columns, issue tickets). The rename preserves every row: IDs,
reporter, type, target, reason, description, and timestamps are untouched;
new workflow columns default to `open` / version 1 / null reviewer / null
snapshot. The reporter FK changes from cascade-delete to nullable
SET NULL, so deleting a reporter preserves report evidence.

Coordinated cutover sequence:

1. Back up the database (`pg_dump`) and record the dump location.
2. Enable maintenance / pause writes and drain or stop old queue workers.
   Old application code cannot run against the renamed table, so the
   rename and the new code deploy as one coordinated cutover.
3. Run `php artisan migrate --force` and confirm all seven
   `2026_10_08_00000x` migrations report DONE.
4. Restart workers on the matching code, then `php artisan optimize:clear`
   (or at least `config:clear`, `route:clear`, `cache:clear`).
5. Verify counts and samples before resuming traffic:

```sql
SELECT count(*) FROM content_reports;              -- must equal pre-cutover forum_reports count
SELECT id, user_id, reportable_type, reportable_id, reason, status, version
  FROM content_reports ORDER BY id LIMIT 20;       -- legacy fields intact, status=open, version=1
SELECT count(*) FROM contact_messages WHERE status NOT IN ('unread','read','replied','closed');  -- 0
SELECT count(*) FROM contact_message_replies;      -- 0 on a fresh cutover
SELECT count(*) FROM issue_tickets;                -- 0 on a fresh cutover
SELECT count(*) FROM admin_action_logs;            -- 0 until admin actions run
```

6. Smoke-test with synthetic accounts and records, then resume traffic.

Missing or never-valid legacy targets (for example a report pointing at
a deleted reply, or a legacy `user` type row) migrate as-is and render in
the admin queue as "Target unavailable"; they permit dismissal or
no-action resolution without hide/restore. A null snapshot on a legacy
row does not mean the target is gone: resolve a still-existing target
normally.

The legacy `POST /api/v1/forum/reports` adapter stays live on the new
table, restricted to legacy thread/reply types and the original reasons,
keeping HTTP 200 and its existing success message. There is one live
queue, not two.

## 4. Rollback boundaries

- Before any real evidence exists (no decided reports, no null-reporter
  rows, no `closed` inquiries), `migrate:rollback` of the seven
  migrations restores the legacy schema. This is a pre-launch escape
  hatch only.
- After real evidence exists, the down migrations refuse to run:
  `000004` throws when any report has no reporter or any review state,
  and `000003` throws when any inquiry uses status `closed`. Use a
  forward corrective migration instead. Never force, edit, or work
  around these guards to drop evidence or silently coerce statuses.
- Backup restore is the disaster path and has an explicit data-loss
  window: everything written after the dump is lost. Record the dump
  time and the window before restoring.
- Old application code cannot run against the renamed table without a
  compatibility migration or a maintenance window; do not roll code back
  independently of the schema.

## 5. Privacy access rules

- Reporter identity, target snapshots, suspension notes, and support
  internals are admin-only. Member receipts contain only ID and status;
  public profile and content resources never expose them.
- Owner and counterparty responses may expose `is_hidden`, but never the
  internal hide reason or actor.
- Admin endpoints send private/no-store cache headers. Restrict database
  records and backups to operators.

## 6. Known limits

- Hiding removes in-app content and links. It does not erase cached or
  previously known media URLs: existing forum uploads keep public
  storage URLs. Never call hide "permanent deletion" or "media removal".
- Suspension and hiding block new HTTP and subscription authorization.
  Existing websocket connections are not forcibly evicted.
- Old split-purchase clones cannot reliably be reconstructed: they
  initialize as independent moderation roots (`moderation_root_id` NULL
  means self) and the operator must moderate those records separately.
  Future clones inherit root identity from `SplitPurchasableAction`.
- SMTP delivery is at-least-once (see Queues); stale-lease retries can
  duplicate a message already accepted by the mail server.
- Terminal reports cannot reopen in this release. Restoration is a
  separate confirmed action and does not reopen reports; restore never
  undeletes content.
- No retention purge or legal-hold policy ships. Records accumulate;
  define retention before adding deletion or export tooling.
- Hide, suspend, and report decisions never cancel, refund, reprice, or
  alter quantities on orders and payments. Existing PayMongo
  reconciliation, cancellation, and fulfillment behavior is unchanged.

## 7. Release verification record

Run every command from the release brief on supported runtimes against
real services, and record command, date, result, and count. Required
gates for this release:

- `cd backend && php artisan test --parallel --recreate-databases --processes=4`
- `cd backend && ./vendor/bin/pint --test`
- `cd backend && ./vendor/bin/phpstan analyse --level=6`
- `cd frontend && npx vitest run --maxWorkers=2`
- `cd frontend && npm run lint:check`
- `cd frontend && npm run format:check`
- `cd frontend && npm run build`
- `cd frontend && npx playwright test e2e/Admin e2e/Community e2e/Marketplace e2e/Shared --project=chromium --project=mobile-chromium`
- `cd frontend && npx playwright test e2e/IdentityAndAccess e2e/Shared e2e/Admin/access.spec.js --project=firefox --project=webkit`

Record pre-existing failures separately from release checks. Never label
the release passing while required new checks are failing or unrun.
Pixel comparisons run only in the approved Linux environment (see
`frontend/README.md`); macOS captures are review evidence only.
