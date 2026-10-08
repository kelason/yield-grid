# Admin Operations, Support & Trust Safety — Design

Date: 2026-10-08

Status: revised proposal for review; no product implementation or account creation yet.

Implementation plan: [Admin operations plan](../plans/2026-10-08-admin-trust-safety.md)

## 1. Intent and delivery scope

Give the YieldGrid operator a dedicated admin account and in-app workspace to
monitor users/content, handle Contact Us inquiries, investigate content reports,
and resolve issues submitted by buyers and farmers.

**User-confirmed meaning of “page monitoring”:** an operational overview of users,
content, inquiries, reports, and issues. No uptime, page-view analytics, session
recordings, infrastructure metrics, or private-chat surveillance. Existing
Grafana/Sentry work remains separate.

This expands the original P0 draft. Keep its filename for existing links; reports
and the issue page are now required delivery scope, not an optional P1 follow-up.

| Milestone | Deliverable |
|---|---|
| A — foundation/support | Dedicated account, access fence, suspension, admin shell, inquiry inbox and persistent reply history |
| B — reports/moderation | One queue for forum threads/replies, forward contracts, harvest listings, and buyer demands; reversible hide/restore |
| C — issues/overview | Buyer/farmer issue page and own ticket history, admin resolution, accurate counts and content browsing |

All three milestones are needed to satisfy the request. Success requires UI and
server authorization, bounded inputs, confirmation, useful failure feedback,
responsive layouts, and keyboard verification for every flow.

## 2. Verified repository facts

- Laravel 12 is declared; CI runs PHP 8.3, PostgreSQL 16/PostGIS 3.4. Vue, Pinia,
  Sanctum, queues, Pest, Vitest, and Playwright are already installed.
- UserRole has farmer/buyer; users.role is a string. RegisterRequest accepts the
  entire enum today: adding ADMIN without a registration allowlist is unsafe.
- Existing Users/Contact classes use the legacy `Domain` namespace, supported by
  Composer. Preserve existing namespaces; new domain classes use the required
  `App\Domain\{Context}` namespace. No namespace cleanup in this work.
- ContactMessage stores submissions and unread/read/replied status. Its queued
  notification job only logs; there is no delivered reply or stored reply history.
- forum_reports has a unique reporter/type/id key but no workflow or target
  existence check. frontend forumStore.reportContent exists without UI callers
  and swallows failures into a toast.
- Marketplace has ForwardContract AND HarvestListing. Partial checkout clones an
  item into a new ID; cancellation can make the clone available again.
- Router/navigation/profile/browser fixtures assume only two roles. Adding one
  router meta flag is insufficient.
- Sanctum expiration is null; remembered tokens can be unbounded. Revoked tokens
  return 401, not the original draft's promised 403.

These observations are planning evidence, not new features or test results.

## 3. Approach and ownership

**Recommended:** extend the existing Laravel API and Vue SPA with one admin role,
explicit policies, small Actions, and existing components. Admin is a dedicated
operational identity, not a trading role with extra permissions.

Alternatives: a separate admin framework adds another UI stack (Filament also
conflicts with the Blade/Livewire prohibition); SQL/console-only operations fail
the requested in-app workflows. Neither is recommended.

- `App\Admin\{Controllers,Requests,Resources}` owns the admin HTTP boundary.
- Users, Contact, Community, Marketplace own their existing behavior. New Shared
  aggregates own cross-context ContentReport and AdminActionLog; IssueTicket
  belongs to Contact. No new bounded context or generic CRUD framework.
- New aggregates get repository interfaces plus Eloquent implementations and
  AppServiceProvider bindings, as required by project rules. Existing User and
  ContactMessage have no repositories; adding admin operations alone does not
  require inventing interfaces for these existing aggregates.
- Respect ForwardContract/Purchase repositories. Extend them for new locked reads
  and query shapes rather than introducing new bypasses.
- Jobs live in App\Jobs; external mail transport is injected from
  App\Infrastructure\Services. No external I/O inside Domain Actions.
- New code: strict types, final classes, typed signatures, methods ≤30 lines,
  PHP classes ≤300 lines, focused Vue components/composables, named limits/enums.
  No global Gate bypass, RBAC dependency, data-grid package, or admin Pinia store.

## 4. Admin account and security boundary

### Provisioning

`php artisan admin:create {email}` prompts for name, hidden password and password
confirmation. Reuse existing name/email constraints; admin password is 12–255
characters. No password CLI option, credential output, committed secret or default
seeded account. Create a dedicated admin with no farm/address requirement.

Reject an existing farmer/buyer email. Existing admin is an informational no-op,
without resetting password or issuing a token. No promotion of trading identities.
Create unverified; dispatch the existing verification notification after commit.
Delivery failure leaves the account recoverable through login/resend. Admin
endpoints require verified email. Public registration permits only farmer/buyer
in both FormRequest and registration Action; admin and unknown roles return 422.

No HTTP create-admin, promotion, demotion, impersonation or user-deletion endpoint.
Routine password recovery reuses the current reset flow.

### Access matrix

Every `/api/v1/admin/*` route uses Sanctum, non-suspended account, verified email,
admin role, and a controller policy ability. Run auth/role restrictions before
resource binding so non-admins cannot probe target existence. No Gate::before
that grants admin every farmer/buyer action.

| Caller/scenario | Result |
|---|---|
| Guest or revoked/expired bearer token | 401 |
| Authenticated farmer/buyer on any admin endpoint | 403, including missing target IDs |
| Unverified admin on admin endpoint | 403; verification and logout remain available |
| Verified admin | Explicit policy-checked access |
| Admin on trading, forum/chat participation, member report/issue creation | 403 |
| Unknown target after admin access checks pass | 404 |

Admin content inspection uses admin endpoints. Own profile is allowed but has no
farmer/buyer statistics. Public profile resources never expose suspension notes,
reporter identity, support information or private admin fields.

Admin tokens expire after 120 minutes regardless of remember-me. Configure the
Sanctum global cap to 43,200 minutes (30 days); ordinary non-remembered tokens keep
their shorter lifetime. Document that older remembered tokens require a new login.

### Suspension

Add nullable users.suspended_at and suspended_reason (500 characters). Suspend
and unsuspend require a trimmed 1–500 character reason, lock the user row and
append an audit event in the same transaction. Self/other-admin targets return
422; repeated suspend or unsuspend-while-active returns 409.

Suspension revokes every personal token. Login validates credentials before
revealing suspension, then locks/rechecks the user before token issuance. A denied
login must not leave the web guard authenticated (Auth::attempt currently creates
that state). Serialize login token creation with suspension to prevent a surviving
concurrent token. Wrong password and unknown email retain indistinguishable errors.

EnsureUserNotSuspended covers the shared authenticated group, including
/broadcasting/auth. Resolved suspended users return 403 with
`code=account_suspended`; already-revoked tokens return 401. Public optional-auth
browsing treats suspended accounts as guests, with no personalized/private fields.
Unsuspension does not restore tokens. Public Contact Us remains available.

Suspension does not cancel orders, hide all content, refund payments, or stop
provider webhooks. Existing websocket connections are not forcibly disconnected:
this release blocks new HTTP/subscription authorization, not instant socket eviction.

## 5. Shared validation and transport contracts

Required text is trimmed and cannot be whitespace-only. Optional text accepts
absent/null/empty; nonempty values meet bounds. Mirror constants in frontend and
validate again at submit time. Actor/status/snapshot fields are server-owned.

| Input | Bound / values |
|---|---|
| Admin search | 0–100 characters, parameterized literal substring; escape LIKE wildcards |
| page / per_page | 1–10,000 / 1–100; default PaginationConstants::DEFAULT_PER_PAGE |
| IDs | Positive integer through signed-bigint max; new API IDs serialized as decimal strings to avoid JS precision loss |
| Admin reason/note | 1–500 characters |
| Report description | 0–1,000; reason other requires 1–1,000 |
| Contact reply | 1–5,000 characters |
| Issue subject / description | 1–150 / 1–5,000 characters |
| Issue page_path | Optional 1–255; relative `/path`, no `//`, query, fragment, scheme or control characters |
| Issue resolution | 1–2,000 when resolving/closing |
| Report/issue creation | 5/minute AND 20/day per user; shared limiter for old/new report routes |
| Admin writes | 30/minute per admin; preserve existing login/contact throttles |
| Request key | UUID for contact replies and issue creation |
| expected_version | Positive integer, maximum signed-int value; required for report/issue transitions |

Use constants/enums for limits, statuses, HTTP codes, throttles and retry values.
Invalid filters return 422. Lists use stable timestamp/id ordering and Laravel
`data`, `links`, `meta`; new details use `{data: ...}`. Existing /user stays
unwrapped. New admin responses use private/no-store caching headers. Sanitized
relative paths are display/navigation references, never a server fetch URL.

## 6. Data and workflow contracts

### One content-report queue

Rename forum_reports to content_reports during a coordinated maintenance cutover.
Preserve IDs, timestamps, reasons and unique keys; retire the old model/writer.
Keep POST /forum/reports as an adapter to the new Action, restricted to legacy
thread/reply types/reasons and retaining HTTP 200 plus its existing success message.
No dual live queues or retrospective invented evidence.

ContentReport retains user_id, reportable_type, reportable_id, reason, description
and timestamps. Change reporter FK from cascade-delete to nullable SET NULL.
Add status `open|reviewing|resolved|dismissed`, version default 1, reviewed_by,
reviewed_at, resolution_note, outcome (`hidden|no_action` when resolved), and
nullable target_snapshot. Terminal reports cannot reopen in this release.

Type allowlist: `thread`, `reply`, `contract`, `listing`, `demand`. Resolve with an
explicit switch, never submitted class names or arbitrary morph-table names.
Reasons: existing `spam`, `inappropriate`, `misinformation`, `harassment`,
`off_topic`, `other`, plus `suspected_fraud`, `prohibited_item`. Legacy adapter
continues to accept only the original reasons.

Snapshot is server-built, immutable, admin-only: type/id, owner ID, public title
and excerpt (each ≤500 characters), business status when relevant, captured_at.
No private address, chat, payment details or reporter-facing anonymous identity.
Legacy rows keep null snapshots. A null snapshot does not mean the target is gone:
resolve a still-existing target normally. Only missing/deleted targets show “Target
unavailable” and permit dismissal/no-action resolution without hide/restore.

Retain lifetime unique `(user_id, reportable_type, reportable_id)`; retries return
the existing receipt without editing its reason or reopening it. A duplicate can
be returned to its reporter even after the target disappears. New reports require
a verified non-suspended buyer/farmer, accessible visible target and non-ownership.
Missing/inaccessible target is 404; self-report is 422. Replies require visible
thread and parent ancestry. Enforce uniqueness under concurrent requests. Reports
never automatically hide content. Member response contains only receipt ID/status.
Index `(status, created_at, id)` and `(reportable_type, reportable_id)`.

Admin open→reviewing/resolved/dismissed and reviewing→resolved/dismissed require
expected_version plus a note. Stale version returns 409. Resolve with hidden
atomically hides content and logs the decision. Only directly hidden content
(or the normalized marketplace root) skips another hide event. A reply suppressed
by a hidden ancestor still needs its own hidden_at when resolved as hidden, so
restoring the ancestor cannot expose that reply. no_action/dismiss leaves content unchanged.
Restoration is a separate confirmed action and does not reopen reports.

### Issue tickets

IssueTicket in Contact: id, nullable user_id (SET NULL), category
`technical|account|marketplace|payment|other`, subject, description, page_path,
status `open|in_progress|resolved|closed`, nullable resolution/resolved_by/resolved_at,
version default 1, client_request_id, timestamps. Unique (user_id, client_request_id);
indexes (status, created_at, id), (user_id, created_at, id). Reusing a key with a
changed payload is 409; same payload returns the existing ticket.

Buyer/farmer creates and reads only their own tickets. Admin reads all and can
move open→in_progress/resolved/closed, in_progress→resolved/closed,
resolved→in_progress/closed, closed→in_progress. Resolve/close requires a public
resolution; reopen clears current resolution fields, retaining audit history.
Same-state/invalid/stale-version transition is 409. Users see current status and
resolution, not internal audit. No user-chosen assignee/priority/status, attachments,
conversation thread, automatic email or financial operation.

### Minimal admin history

Add AdminActionLog in Shared: nullable actor_id (SET NULL), action enum,
subject_type/id, reason, bounded before/after state, nullable related_report_id,
created_at. No edit/delete API. Record in the mutation's DB transaction: console
account creation (null actor), suspend/unsuspend, contact read/close/reopen/reply/retry,
report decisions, hide/restore, issue transitions. No credentials, tokens, message
bodies, arbitrary request headers or model dumps. This moves a small action record
into initial scope; an audit explorer, full event sourcing and staff RBAC are deferred.

### Contact inquiry and reply history

Preserve existing public Contact Us and its 2,000-character message limit.
Admin sees original submission and outbound reply history, not an invented inbound
email thread. GET has no side effect; confirmed Mark read changes unread→read.
Repeated read/close, reply-to-closed and invalid transitions return 409.

ContactMessageReply child aggregate: message_id, nullable admin_id, immutable
recipient/body, client_request_id, delivery_status `queued|sending|sent|failed`,
attempts, sending_started_at, delivery_generation, sent_at, sanitized error_code,
timestamps. delivery_generation increments on each claim or explicit retry; all
completion/failure writes require the same generation and sending state. Unique (message_id, request key).
Same key/different body is 409. Only one queued/sending reply per inquiry; lock the
parent to serialize queue/retry/close. HTTP 202 means persisted for delivery.

Dispatch after commit. Dispatch failure leaves failed state and Retry. A job
atomically claims the same reply row; duplicate jobs skip sent/sending rows.
Use three attempts with 60/300-second retry backoff, 60-second timeout and queue
retry_after above timeout. Ordinary failure resets the claim to queued before
retry; exhausted attempts become failed. Retry can recover queued or sending rows
older than 5 minutes, covering crashes between DB commit and queue dispatch. A
stale-sending retry shows a duplicate-delivery caution and invalidates the old
generation so late workers cannot overwrite a newer attempt or closed inquiry.
Close is blocked while any queued/sending row exists; recover it through Retry.
Retry never creates another reply row. Job retry attempts reset per explicit retry;
the lifetime attempts counter and delivery generation remain monotonic.

Set replied_at/status=replied only after transport success. Failed delivery retains
body/recipient and a retryable failure; ordinary SMTP is at-least-once, so a crash
after server acceptance may duplicate on retry. Do not promise exactly-once delivery,
inbox arrival or read receipt. Use injected Infrastructure mail transport, escaped
plain text and the existing plain-PHP template precedent or Mail::raw, never Blade.

Close any open inquiry with no outstanding delivery; reopen closed→read while
preserving replies/replied_at. Extend the existing PostgreSQL contact status check
with closed in a forward migration; do not silently remap/drop closed rows on rollback.

## 7. Moderation visibility and financial safety

Expose direct and effective hiding separately to admin controls: restoring a
reply cannot make it visible while its thread remains hidden.

Add nullable hidden_at/hidden_by/hidden_reason to ForumThread, ForumReply,
ForwardContract, HarvestListing and CropDemand. Hide/restore locks the target and
requires an admin reason; records remain in the DB. Owner/counterparty responses
can expose is_hidden, but not internal reasons. Do not reuse business status or
add a global Eloquent scope that removes historical transaction relationships.

| Surface | Enforcement |
|---|---|
| Forums | Exclude hidden content from lists/detail/profile feeds and nested replies. Hidden thread suppresses replies; hidden reply suppresses descendants. Block new reply/vote/accept/edit/attachment through direct IDs too. Existing owner delete may remain. Restore never undeletes content. |
| Forum events | Check user/thread on new subscription; reload ancestry before queued broadcast and build an explicit sanitized payload instead of broadcasting the raw model. Preserve anonymous-author confidentiality. |
| Marketplace | Exclude both contracts/listings from public list/detail; recheck hidden state under checkout locks for cash and online. Keep owner/purchase history through existing private endpoints. |
| Demands | Exclude public list/detail except explicitly authorized owner/existing offer party. Block new offers and pending-offer acceptance under demand lock. Existing accepted offers remain payable; reject/withdraw/cancel/deliver/complete remain available. |
| Derived discovery | Hidden demands do not enter fresh crop-analysis context; hidden items do not enter marketplace reference-price sampling. Do not rewrite historical financial/credit aggregates. |
| Payments | Hide/suspend never cancels/refunds/reprices orders or changes quantities. Existing PayMongo reconciliation, cancellation and fulfillment keep their authorization/idempotency behavior. |

**Partial-purchase lineage:** contracts/listings gain nullable moderation_root_id
self-FK with restricted deletion; null means self. Future SplitPurchasableAction
clones inherit root identity. Effective visibility checks root, including clones
made available after cancellation/gateway failure. Admin hide/restore of a clone
normalizes to the root; checkout locks root before item. Clients cannot set lineage.
Old clones cannot reliably be reconstructed: initialize as independent roots and
document that the operator must moderate those records separately.

Whichever operation takes the root lock first defines checkout/hide ordering.
An already-created purchase continues; new checkout after hide commits fails 409.
Forum uses thread-before-reply locks; demand mutation and hide use the same demand
lock. Reply counts/accepted-answer display must match visible content without
corrupting stored physical counters on hide/restore.

Existing forum uploads have public storage URLs. Hiding removes in-app content and
links; it does not erase cached or known media URLs. Private media migration/erasure
is separate work. UI/runbook must not call hide “permanent deletion” or “media removal”.

## 8. API inventory

Paths are under /api/v1. Controllers authorize and validate every request. All
writes confirm in UI; 422 means invalid input/self-target; 409 state/version
conflict; 429 rate limit; 404 inaccessible/missing member target.

| Method/path | Contract |
|---|---|
| GET /admin/overview | Counts in §9, generated_at |
| GET /admin/users, /admin/users/{user} | search/role/suspended/page filters; operational fields only |
| POST /admin/users/{user}/suspend or /unsuspend | {reason}; 200 |
| GET /admin/contact-messages, /admin/contact-messages/{message} | status/search/page, original and reply history |
| POST /admin/contact-messages/{message}/read, /close, /reopen | Explicit transition; 200 |
| POST /admin/contact-messages/{message}/replies | {body, client_request_id}; 202 |
| POST /admin/contact-messages/{message}/replies/{reply}/retry | Parent-scoped; 202 |
| POST /reports | {reportable_type, reportable_id, reason, description?}; 201 new, 200 duplicate |
| POST /forum/reports | Legacy adapter, HTTP 200 |
| GET /admin/reports, /admin/reports/{report} | status/type/reason/page; admin-only reporter/snapshot |
| POST /admin/reports/{report}/decision | {status, outcome?, note, expected_version}; 200 |
| GET /admin/content/{type}, /admin/content/{type}/{id} | Allowlisted type; search/visibility/page |
| POST /admin/content/{type}/{id}/hide or /restore | {reason}; 200, repeat state 409 |
| POST /issues | {category, subject, description, page_path?, client_request_id}; 201 new, 200 replay |
| GET /issues, /issues/{issue} | Own tickets only; other user's ID 404 |
| GET /admin/issues, /admin/issues/{issue} | status/category/search/page |
| POST /admin/issues/{issue}/transition | {status, resolution?, expected_version}; 200 |

Constrain type routes so they cannot consume fixed paths. Never trust a submitted
URL for server fetching, email recipient, or navigation outside the application.

## 9. Frontend and monitoring

Reuse DashboardLayout/AppSidebar with explicit role mapping. Admin navigation:
Overview, Users, Content, Inquiries, Reports, Issues, own profile and logout.
Farmer/buyer desktop/mobile navigation gains Report an issue and keeps own
profile/address management. Admin does not bootstrap chat/realtime trading state.
Unverified admin routes to /auth/verification-required, wiring the existing
VerifyEmailPage.vue separately from the signed-link EmailVerificationCallback.vue,
avoiding dashboard redirect loops.

New admin pages: AdminOverviewPage, AdminUsersPage, AdminContentPage,
AdminInquiriesPage, AdminReportsPage, AdminIssuesPage. Shared ReportIssuePage
contains submission and My issues list/detail. Admin detail stays on admin routes,
not links to farmer/buyer-only screens.

Overview is database-wide, never the count of one paginated response:

- Farmer/buyer account total and suspended total, excluding admin identities.
- Non-deleted content records, visible/hidden counts for each of five types.
  Contract/listing totals include split records and are labeled as record counts;
  inherited root/ancestor suppression counts as hidden.
- Inquiries by unread/read/replied/closed; failed outbound reply count.
- Reports by each status; issues by each status.

Each card links to matching filtered list. Fetch on entry/manual Refresh, show
Updated at from generated_at, no polling or fabricated trends. Lists default newest
first with ID tie-breaker; open/reviewing report queue defaults oldest first. Failed
loads show retry before empty states; filters survive retry.

Reuse AppButton, AppCard, FormField, AppTextarea, AppModal, ConfirmModal, PageHeader,
StatCard, LoadingState, EmptyState, PaginationControls and confirmation composables.
One page-level pending confirmation owner. Cards emit report events, pages perform
API calls. Report controls cover threads, nested replies, contract/listing cards
and demand detail without triggering checkout/offer actions. Guests get sign-in;
unverified members get verification guidance; owners do not see self-report.

Report form shows target summary/reason/description counter, then confirmation.
Cancel sends no mutation. Busy prevents duplicates/dismissal. On failure preserve
the underlying form/detail, draft and inline error even if confirmation closes;
global toast outside native dialog is insufficient. Issue/reply request keys persist
across uncertain network retries. No v-html. Null/deleted targets render safely.

Use Field & Linen, Atomic Design, Lora/Inter, existing tokens, ≥44px targets, native
labels, visible focus, focus restoration, reduced motion and aligned controls.
Keep admin status labels separate from StatusBadge's “Pending Payment” mapping.

## 10. Verification, deployment and limits

Plan tasks specify role/IDOR, exact input bounds, concurrency, migration preservation,
missing targets, draft retention, delivery failures, nested visibility, split-item
cancellation and operational-count tests. Use PostgreSQL for locks/constraints.

Browser checks serve the production build. Reviewed pixels use Chromium on
Ubuntu 24.04 / Playwright 1.63.0 Noble, self-hosted Inter/Lora and fixed fixtures.
Run Firefox/WebKit shared/auth smoke separately. Existing redesign results are
historical, not verification of these new flows.

Deploy foundation first. Back up DB; pause writes/workers for report-table rename
and reader/writer cutover; verify row counts, IDs and legacy fields; restart workers
on matching code. Provision dedicated admin explicitly, verify email, smoke-test
with synthetic accounts/records. No account auto-created by deploy.

After real tickets/replies/reports/history exist, prefer forward corrective
migrations. Do not rollback by dropping evidence or silently coercing statuses.
Old application code cannot run against the renamed table without a compatibility
migration/maintenance window. Backup restore has an explicit data-loss window.
No automated purge or claimed legal retention policy ships; restrict records and
backups to operators and define retention before adding deletion/exports.

Deferred: staff RBAC/assignments/bulk actions, impersonation, admin-user UI, private
chat moderation, automatic sanctions, appeals, reporter notifications, issue
attachments, inbound email threading, financial disputes/refunds/cash overrides,
analytics/uptime dashboards, private-media erasure, forced socket eviction,
retroactive split lineage, MFA/SSO and audit explorer UI. These exclusions do not
defer any requested reporting entry point, inquiry visibility or operational overview.
