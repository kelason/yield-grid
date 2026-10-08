# Admin Operations, Support & Trust Safety Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver dedicated admin access, operational monitoring, inquiry handling, content reports/moderation, and buyer/farmer issue reporting in the existing YieldGrid app.

**Architecture:** Extend the Laravel API and Vue SPA, using existing contexts and UI primitives. Shared owns content reports/admin history; Contact owns support replies/issues; Community and Marketplace enforce their own visibility. Keep moderation independent of financial state and reuse installed infrastructure.

**Tech Stack:** Laravel 12, PHP 8.3 (CI), PostgreSQL 16/PostGIS 3.4, Sanctum, Redis queues, Vue 3, Pinia, Tailwind, Pest, Vitest, Playwright 1.63.0.

**Spec:** [Admin operations design](../specs/2026-10-08-admin-trust-safety-p0-design.md). Read both artifacts; the retained P0 filename now covers all three milestones.

**Status:** Planning only. Checkboxes describe future implementation; no tests, migrations, provisioning, or product changes have been executed by writing this plan.

## Global constraints

- All three milestones are needed to satisfy the request; “page monitoring” means operational overview, not infrastructure telemetry or page analytics.
- Preserve existing namespaces; new domain classes use `App\Domain\{Context}`. Controllers/requests/resources use the context directories, not App\Http. No namespace migration.
- New aggregates get repositories and AppServiceProvider bindings. Existing ForwardContract/Purchase queries use their repository interfaces. Eloquent in Domain remains intentional.
- No new product dependencies, generic admin CRUD engine, global Gate bypass, frontend feature directories, Blade, Livewire, or admin global store.
- Strict types, final new classes, typed signatures, PHP methods ≤30 lines/classes ≤300 lines; focused Vue components and functions ≤30 lines.
- Admin tokens expire after 120 minutes regardless of remember-me; Sanctum cap is 43,200 minutes. Public registration remains farmer/buyer only.
- Copy all exact bounds/statuses from spec §5–6 into named constants/enums, mirrored in frontend. Use `HttpCode` and `PaginationConstants`; frontend HTTP_STATUS.
- New API IDs are decimal strings; preserve existing endpoint ID contracts. Never coerce new report/ticket IDs through JavaScript Number.
- Every mutation uses page-owned confirmation; failed drafts survive and errors are visible inside the active dialog. GET never marks inquiries read.
- New snapshots/audit/support fields are private; resources whitelist fields. Submitted/LLM content is escaped, never rendered through v-html.
- Keep existing purchase/offer/payment/chat authorization, quantities and reconciliation. Hide is reversible visibility, not cancel/refund/delete.
- Browser checks serve the production build. Reviewed pixels use Chromium on Ubuntu 24.04 / Playwright 1.63.0 Noble, self-hosted Inter/Lora and fixed fixtures. Run Firefox/WebKit shared/auth smoke separately.
- Match current Field & Linen/Atomic Design rules. Keep AGENTS.md and the design-system skill unchanged unless deliberately changing their shared rules.

## Review focus

1. A missing admin target ID must not bypass the access fence: test guest/non-admin/unverified-admin/verified-admin against known and missing IDs (Tasks 2, 15).
2. A partial checkout that precedes hiding and later cancels must not republish its clone: test root inheritance and both transaction orderings with PostgreSQL locks (Task 8).
3. A reply accepted by SMTP just before worker failure must not be called exactly-once: test recoverable sending leases, duplicate jobs, failed dispatch, same-row retry and honest UI states (Tasks 4–5).
4. A hidden/deleted thread or reply must not leak through profile, nested replies, event payloads, or direct mutations: test every surface, including anonymous authors (Task 7).
5. A network retry/stale reviewer must not create a second issue or overwrite a newer decision: test UUID/payload conflicts, report uniqueness and expected_version with real concurrency (Tasks 6, 9, 11–12).

## Delivery map and execution order

| Milestone | Tasks | Independently reviewable outcome |
|---|---|---|
| A | 1–5 | Admin can sign in after verification, suspend/restore users, read/reply/close inquiries |
| B | 6–10 | Members can report all five content types; admin reviews/hides/restores safely |
| C | 11–15 | Members submit/track issues; admin resolves them and monitors accurate platform counts |

Dependency graph: 1→2→3; 1+2→4→5; 1+2→6; 6→7/8;
6+7+8→9; 3+6+9→10; 1+2→11→12; 3+4+6+9+11→13;
all feature tasks→14→15. The numbered order is safe for sequential execution.
Forum visibility (7) and marketplace visibility (8) can run in parallel after the
report target contracts are fixed. Do not concurrently edit routes/provider/router
without coordinating the shared integration owner. Milestones are delivery slices,
not permission to leave the remaining request unfinished.

## File and interface map

All paths below are repository-relative. Braced filename lists mean individual
files, not directories to create with literal braces. Test paths are named in each
task. Existing files are verified against the current checkout; new files are proposals.

| Responsibility | Location |
|---|---|
| Provisioning and shared admin fence | backend/app/Console/Commands/CreateAdminCommand.php; existing Users/Auth; backend/app/Shared/Middleware |
| Admin HTTP boundary | backend/app/Admin/{Controllers,Requests,Resources}; backend/routes/admin.php included under /api/v1/admin |
| Minimal action history | backend/app/Domain/Shared/{Models/AdminActionLog.php,Enums/AdminAction.php,Repositories/AdminActionLogRepositoryInterface.php}; backend/app/Infrastructure/Shared/Repositories/EloquentAdminActionLogRepository.php |
| One report aggregate | backend/app/Domain/Shared/{Models/ContentReport.php,Enums,Actions,Repositories}; backend/app/Infrastructure/Shared/Repositories/EloquentContentReportRepository.php |
| Report member HTTP | backend/app/Shared/{Controllers/ContentReportController.php,Requests/StoreContentReportRequest.php,Resources/ContentReportReceiptResource.php}; existing forum adapter |
| Support replies / issue tickets | new App\Domain\Contact classes beside legacy Contact classes; Infrastructure/Contact repositories; App\Jobs reply delivery |
| Content enforcement | Existing Community/Marketplace models, Actions, policies, repositories, controllers and event resources |
| Admin pages | frontend/src/pages/dashboard/admin/*.vue |
| Report/issue UI | Existing shared/forum/market pages; frontend/src/components/organisms/ContentReportForm.vue; frontend/src/pages/dashboard/shared/ReportIssuePage.vue |
| Shared role/validation constants | frontend/src/constants/{roles,admin,reporting,issues}.js; backend/app/Constants/{Admin,Reporting,Issue}Constants.php |

Repository APIs below are deliberately small. Arrays have PHPDoc shapes matching
the spec; use enums/DTOs where already idiomatic, but do not add an abstraction for
every request. All `*Locked`/`lock*` methods require an enclosing transaction.

## Task 1: Provision a dedicated admin and record operator actions

**Files**

- Create: `backend/app/Console/Commands/CreateAdminCommand.php`, `backend/app/Domain/Users/Actions/CreateAdminAction.php` (new App namespace), `backend/app/Constants/AdminConstants.php`.
- Create: `backend/database/migrations/2026_10_08_000001_create_admin_action_logs_table.php`; Shared history model/enum/repository and implementation from the file map.
- Modify: `backend/app/Domain/Users/Enums/UserRole.php`, `backend/app/Auth/Requests/RegisterRequest.php`, `backend/app/Domain/Users/Actions/RegisterUserAction.php`, `backend/app/Providers/AppServiceProvider.php`.
- Test: `backend/tests/Feature/Admin/AdminProvisioningTest.php`, existing `backend/tests/Feature/Auth/AuthBoundaryTest.php`.

**Interfaces**

- `CreateAdminAction::execute(string $name, string $email, string $password): User` returns a newly persisted unverified dedicated admin; rejects any existing email. The command handles existing-admin no-op before invoking it, with DB uniqueness as race backstop.
- `AdminActionLogRepositoryInterface::append(?int $actorId, AdminAction $action, string $subjectType, string $subjectId, string $reason, array $before, array $after, ?int $reportId = null): AdminActionLog`.
- Action values cover `admin_created`, `user_suspended`, `user_unsuspended`, `contact_read`, `contact_closed`, `contact_reopened`, `reply_queued`, `reply_retried`, `report_decided`, `content_hidden`, `content_restored`, `issue_transitioned`.

- [ ] Add failing provisioning tests: new account is admin/unverified, password hashed, no address/farm; existing buyer/farmer rejected unchanged; existing admin command is no-op; neither console output nor log contains password; verification notification dispatches after persistence. Test password lengths 11/12/255/256, blank name, malformed/duplicate email using existing auth caps.
- [ ] Retain the existing register-as-admin 422 assertion, rename its misleading “unknown role” description, and add direct RegisterUserAction rejection. Assert role remains farmer/buyer for successful normal registrations.
- [ ] Run `cd backend && php artisan test tests/Feature/Admin/AdminProvisioningTest.php tests/Feature/Auth/AuthBoundaryTest.php`; first run must fail for missing behavior, not environmental setup.
- [ ] Implement the enum/allowlist, minimal history table/repository, transactional creation Action and hidden interactive prompts. Keep mail outside Domain and do not issue an access token. History stores only changed operational fields; do not serialize the user model.
- [ ] Repeat focused tests; run Pint/PHPStan on this change using the verification commands below. Commit only the task files with message `feat: provision dedicated admin accounts`.

## Task 2: Fence admin access and enforce suspension

**Files**

- Create: `backend/routes/admin.php`; `backend/app/Shared/Middleware/EnsureUserNotSuspended.php`; `backend/app/Domain/Users/Actions/{SuspendUserAction,UnsuspendUserAction}.php`; `backend/app/Policies/AdminUserPolicy.php`.
- Create: `backend/app/Admin/Controllers/AdminUserController.php`, `Requests/{AdminUserFilterRequest,AdminReasonRequest}.php`, `Resources/AdminUserResource.php`.
- Create: `backend/database/migrations/2026_10_08_000002_add_user_suspension_fields.php`.
- Modify: `backend/routes/api.php`, `backend/bootstrap/app.php`, `backend/config/sanctum.php`, existing `Domain/Users/{Models/User,Actions/LoginUserAction}.php`, `backend/app/Shared/Middleware/AuthenticateIfTokenPresent.php`, `backend/app/Providers/AppServiceProvider.php`.
- Test: `backend/tests/Feature/Admin/{AdminAccessTest,UserSuspensionTest}.php`; auth/security regression tests.

**Interfaces**

- `SuspendUserAction::execute(User $actor, User $target, string $reason): User`; matching UnsuspendUserAction. Lock/reload target before guards; transaction includes tokens/history.
- AdminUserController exposes index/show/suspend/unsuspend with explicit policy abilities. Search/role/suspended/page bounds are spec §5; index resource never exposes credentials/addresses by default.
- Resolved suspension failure JSON: `{message: 'This account is suspended. Contact support for assistance.', code: 'account_suspended'}`. Internal notes are not login-error text.

- [ ] Add matrix tests for guest=401, farmer/buyer=403, unverified-admin=403, verified-admin allowed, on known/missing target IDs. Test administrator denial on business and forum/chat participation routes. Do not use an all-powerful policy before-hook.
- [ ] Add real bearer tests (forget auth guards between requests): revoke two tokens; neither works afterward; suspended authenticated/session backstop returns 403; invalid password stays normal auth failure; password reset/verification do not unsuspend; unsuspend requires fresh login. Test self/other-admin 422, repeated transitions 409 and 0/1/500/501 reason boundaries.
- [ ] Test login racing suspension with separate PostgreSQL connections/processes: after suspension commits no surviving usable token; a rejected Auth::attempt path leaves no session. Test optional-token public responses match guest privacy and new broadcasting authorization is denied.
- [ ] Run `cd backend && php artisan test tests/Feature/Admin tests/Feature/AuthTest.php tests/Feature/Security/AuthenticationTest.php` and record the intended failures.
- [ ] Implement middleware placement/priority before bindings; nested admin group adds verified+role. Add explicit farmer/buyer guard to member forum/chat/report/issue routes without changing existing member verification requirements. Use credential validation before session establishment/token issuance and shared row locking; admin token 120 minutes, global cap 43,200.
- [ ] Register policies; implement safe resources/actions and filters. Keep public UserProfileResource unchanged regarding internal notes. Repeat focused tests, including database concurrency test, then commit `feat: secure admin routes and user suspension`.

## Task 3: Make frontend authentication/navigation explicitly three-role

**Files**

- Create: `frontend/src/constants/roles.js`, `frontend/src/pages/dashboard/admin/AdminUsersPage.vue`, `frontend/src/constants/admin.js`, `frontend/src/composables/useAdminUsers.js`.
- Modify: `frontend/src/router/index.js`, `frontend/src/constants/navigation.js`, `frontend/src/composables/useDashboardNavigation.js`, `frontend/src/components/templates/DashboardLayout.vue`, `frontend/src/pages/dashboard/shared/ProfilePage.vue`, `frontend/src/pages/error/{ForbiddenPage,NotFoundPage}.vue`, `frontend/src/pages/auth/VerifyEmailPage.vue`, `frontend/src/stores/auth.js`, `frontend/src/composables/useApi.js` as required for session cleanup.
- Modify/test: `frontend/src/router/__tests__/roleGuard.spec.js`, sidebar/layout/profile/auth tests; create `frontend/src/pages/dashboard/admin/__tests__/AdminUsersPage.spec.js`.

**Interfaces**

- `ROLE_HOME = {farmer: {name:'farmer-dashboard'}, buyer:{name:'buyer-dashboard'}, admin:{name:'admin-users'}}` initially; switch admin to admin-overview in Task 13. Unknown role fails closed to forbidden, never farmer.
- Admin root `/admin` uses DashboardLayout; user route `/admin/users`. Add only working navigation items as pages land. Wire the existing VerifyEmailPage.vue at `/auth/verification-required` (`requiresAuth`, no requiresVerification); keep signed-link `/auth/verify-email` callback distinct. Unverified admins go to this route, never an admin↔dashboard redirect cycle. Update its logout redirect/resend error handling if required.
- `useAdminUsers()` exposes list/loading/error/filters/pagination plus fetch and suspend/unsuspend calls; page owns pendingConfirm/confirmConfig and reason draft.

- [ ] Add failing tests for /dashboard, home/guest and verification redirects across all three roles; admin cannot reach buyer/farmer views; unverified admin can reach verification/resend/logout. Admin shell must make zero chat/bootstrap business requests; own profile displays no buyer metrics.
- [ ] Add user-page tests for search limits, loading/error→Retry→data, cancel=zero POST, confirmed mutation=one POST, busy duplication blocked, inline failed reason preserved, missing/null user safe, and 401/account_suspended clearing both storage and reactive auth state.
- [ ] Run `cd frontend && npx vitest run src/router/__tests__/roleGuard.spec.js src/pages/dashboard/admin/__tests__/AdminUsersPage.spec.js src/components/templates/__tests__/DashboardLayout.spec.js --maxWorkers=2` before implementing.
- [ ] Implement explicit role map/navigation and existing shared controls. Do not rebuild the shell; retain farmer/buyer mobile profile/address links. Read AGENTS.md/design-system skill before visual changes. Use one confirmation owner and a reason form in an AppModal; display API errors in the still-active form after confirmation failure.
- [ ] Repeat focused unit tests, `npm run lint:check`, `npm run format:check`, `npm run build`; commit `feat: add admin navigation and user management`.

## Task 4: Persist inquiry workflow and deliver replies reliably

**Files**

- Create: `backend/database/migrations/2026_10_08_000003_extend_contact_support_workflow.php` (status check + reply table).
- Create: `backend/app/Domain/Contact/Enums/{ContactStatus,ReplyDeliveryStatus}.php`, `Models/ContactMessageReply.php`, `Repositories/ContactMessageReplyRepositoryInterface.php`, `Actions/{TransitionContactMessageAction,QueueContactReplyAction,RetryContactReplyAction}.php`.
- Create: `backend/app/Infrastructure/Contact/Repositories/EloquentContactMessageReplyRepository.php`, `backend/app/Infrastructure/Services/ContactReplyMailService.php`, `backend/app/Jobs/SendContactReplyJob.php`.
- Create: `backend/app/Admin/Controllers/AdminContactMessageController.php`, `Requests/{ContactInboxFilterRequest,QueueContactReplyRequest}.php`, `Resources/{AdminContactMessageResource,ContactMessageReplyResource}.php`, `backend/app/Policies/ContactMessagePolicy.php`.
- Modify: legacy `backend/app/Domain/Contact/Models/ContactMessage.php`, admin routes/provider, constants; inspect `backend/config/queue.php` retry_after for chosen worker connection.
- Test: `backend/tests/Feature/Admin/{ContactInboxTest,ContactReplyTest,ContactMigrationTest}.php`; preserve existing contact tests.

**Interfaces**

- `QueueContactReplyAction::execute(User $actor, ContactMessage $message, string $body, string $requestId): ContactMessageReply`; matching Retry action receives existing parent/reply and returns same row.
- `TransitionContactMessageAction::execute(User $actor, ContactMessage $message, ContactStatus $next): ContactMessage` only permits spec transitions.
- Reply repository: `create(array $attributes): ContactMessageReply`, `findLockedById(int $id): ContactMessageReply`, `findByRequestId(int $messageId, string $requestId): ?ContactMessageReply`, `save(ContactMessageReply $reply): void`, `forMessage(int $messageId): Collection` (typed collection PHPDoc).
- `ContactReplyMailService::send(string $recipient, string $body): void`; `SendContactReplyJob::__construct(int $replyId)`. Action stores first; transport/job enqueue happens after commit in application orchestration.

- [ ] Test GET leaves unread unchanged; confirmed read/close/reopen transition matrix; closed reply and repeated transition=409; existing submissions/legacy statuses survive migration; parent-scoped retry rejects another inquiry's reply; list filters/pagination are bounded.
- [ ] Test 0/1/5,000/5,001 body lengths; recipient comes from original inquiry, not request. Same UUID/payload returns same row, changed payload=409; two queue attempts produce at most one outstanding reply. Assert queued response does not set replied_at or claim sent.
- [ ] Test enqueue failure, transport exception, terminal failure, stale queued/sending lease, old-generation late completion, duplicate queued job, already-sent job, stable-ID retry, close-vs-queue race, and sent_at/status only after successful service return. Assert failure body preserved and error sanitized. Fake transport, not an actual SMTP server.
- [ ] Run `cd backend && php artisan test tests/Feature/Admin/ContactInboxTest.php tests/Feature/Admin/ContactReplyTest.php tests/Feature/Admin/ContactMigrationTest.php tests/Feature/ContactTest.php tests/Feature/Contact` for expected failure.
- [ ] Implement PostgreSQL check alteration explicitly and persistent replies. Queue after commit; parent-before-reply locking everywhere. Claim queued→sending, send outside DB transaction, then complete under lock. On caught retryable failure return to queued before releasing/rethrowing; terminal failure marks failed. Persist sending_started_at and delivery_generation. Increment generation on claim or explicit retry and condition completion/failure on matching generation and sending state. Recover queued/sending rows older than 5 minutes via explicit Retry (same row), covering death before enqueue; stale-sending retry warns of possible duplicate delivery and invalidates late workers. Do not hold a DB lock while making SMTP calls.
- [ ] Configure 3 attempts, backoff 60/300 seconds, timeout 60 seconds, lease 5 minutes; verify retry_after exceeds timeout. Prevent retry while a live job owns the row; queued/sending blocks close. Use Mail::raw or plain-PHP templates, no Blade and no Domain transport.
- [ ] Repeat tests/static checks and migration rehearsal on disposable PostgreSQL; commit `feat: add durable contact inquiry replies`.

## Task 5: Build the admin inquiry inbox

**Files**

- Create: `frontend/src/pages/dashboard/admin/AdminInquiriesPage.vue`, `frontend/src/composables/useAdminInquiries.js`, `frontend/src/components/organisms/AdminInquiryDetail.vue`, `frontend/src/pages/dashboard/admin/__tests__/AdminInquiriesPage.spec.js`.
- Modify: router/navigation/admin constants; reuse existing public ContactForm without unrelated redesign.

**Interfaces**

- Inquiry list filters status/search/page; detail organism receives nullable message, replies, draft, errors/busy and emits draft/transition/reply/retry events. Page owns request key and confirmation.
- API URLs/payloads are Task 4/spec §8. Labels distinguish Queued, Sending, Sent, Failed; do not feed pending into payment StatusBadge.

- [ ] Add failing tests for status filter, original inquiry/reply rendering, no POST on detail open, explicit Mark read confirmation, read/reply/close/reopen/retry cancellation, busy prevention and scoped retry ID. Include malformed/null records and HTML-like content rendered as text.
- [ ] Test request key retained across network uncertainty; successful acceptance shows Queued, not Sent; delivery failure exposes same-row Retry; live sending disables close/retry; stale queued shows Retry; stale sending shows caution and Retry; rejected reply keeps draft and inline dialog error.
- [ ] Run `cd frontend && npx vitest run src/pages/dashboard/admin/__tests__/AdminInquiriesPage.spec.js src/components/organisms/__tests__/ContactForm.spec.js --maxWorkers=2` for failure.
- [ ] Implement page/detail with shared modal/textarea/counter/pagination primitives. Bound search/body and validate before API. Avoid a giant all-purpose admin detail component; separate fetching into the focused composable.
- [ ] Repeat tests/lint/format/build; commit `feat: add admin inquiry inbox`.
- [ ] Milestone A demonstration: create/verify dedicated admin; exercise suspension with a real test token; persist reply→worker send→close using sandbox mail. Record limitation that existing sockets are not forcibly evicted and SMTP is not exactly-once.

## Task 6: Migrate forum reports into one typed report queue

**Files**

- Create: `backend/database/migrations/2026_10_08_000004_generalize_content_reports.php`.
- Create: `backend/app/Domain/Shared/Models/ContentReport.php`, `Enums/{ReportTargetType,ContentReportReason,ContentReportStatus}.php`, `Repositories/ContentReportRepositoryInterface.php`, `Actions/SubmitContentReportAction.php`, `Services/ContentTargetResolver.php`.
- Create: `backend/app/Infrastructure/Shared/Repositories/EloquentContentReportRepository.php`, `backend/app/Constants/ReportingConstants.php`, `backend/app/Shared/{Controllers/ContentReportController,Requests/StoreContentReportRequest,Resources/ContentReportReceiptResource}.php`, `backend/app/Policies/ContentReportPolicy.php`.
- Modify: forum ReportContentAction/ForumReportController/StoreReportRequest, api routes/provider, existing ForumConstants/ReportReason as needed for compatibility; retire `backend/app/Domain/Community/Models/ForumReport.php` after updating its callers/tests.
- Test: `backend/tests/Feature/Reporting/{ContentReportTest,ContentReportMigrationTest}.php`, `backend/tests/Feature/Community/ReportBoundaryTest.php`.

**Interfaces**

- `SubmitContentReportAction::execute(User $reporter, ReportTargetType $type, string $targetId, ContentReportReason $reason, ?string $description): ContentReport` returns existing/new row; controller selects 200/201 from wasRecentlyCreated. Old adapter always preserves 200/message.
- Resolver `find(ReportTargetType $type, string $id): Model`, `assertReportable(User $reporter, Model $target): void`, `snapshot(Model $target): array`. Target access uses existing ownership/access rules, with visibility integrated and tested in Tasks 7/8; no dynamically supplied class names.
- Report repository: `findByReporterTarget(int $userId, ReportTargetType $type, string $id): ?ContentReport`, `create(array $attributes): ContentReport`, `findLockedById(int $id): ContentReport`, `save(ContentReport $report): void`, `paginate(array $filters, int $perPage): LengthAwarePaginator`.

- [ ] Add migration test with legitimate, duplicate-key constrained, deleted-target and never-valid legacy rows; preserve IDs/reason/description/timestamps and row count after rename; new ID exceeds prior max; reporter deletion preserves evidence. Do not populate invented snapshots for legacy rows.
- [ ] Test all five target aliases with real fixtures, invalid class/type/ID=422, missing/inaccessible=404, self-report=422, verified role matrix and limits (description 1,000 succeeds/1,001 fails; other-empty fails). Snapshot bounds/admin-only privacy must be asserted.
- [ ] Test legacy adapter payload/200 compatibility; repeated report returns unchanged receipt even after terminal decision/target deletion; parallel submissions yield one row. Old/new routes share the 5/minute and 20/day user limit. Duplicate insertion must recover outside a failed PostgreSQL transaction/savepoint, not query in an aborted transaction.
- [ ] Run `cd backend && php artisan test tests/Feature/Reporting tests/Feature/Community/ReportBoundaryTest.php` for failure.
- [ ] Implement rename/constraints/indexes, repository, switch resolver, author/access checks, bounded snapshots and adapters. Update existing tests that previously used nonexistent target IDs to create genuine targets rather than weakening existence checks.
- [ ] Repeat tests and PostgreSQL migration rehearsal, then commit `feat: unify content reports across the platform`. Do not expose member UI until Tasks 7/8 visibility checks and Task 9 review workflow are ready.

## Task 7: Enforce forum visibility through every read/write path

**Files**

- Create: `backend/database/migrations/2026_10_08_000005_add_forum_moderation_fields.php`, `backend/app/Domain/Community/Actions/ModerateForumContentAction.php`, `backend/app/Policies/ForumContentPolicy.php`.
- Modify: `backend/app/Domain/Community/Models/{ForumThread,ForumReply}.php`; `backend/app/Community/Controllers/{ForumThreadController,ForumReplyController,ForumVoteController,ForumAttachmentController}.php`; `backend/app/Community/Requests/{StoreReplyRequest,UploadAttachmentRequest}.php`.
- Modify: `backend/app/Domain/Community/Actions/{CreateReplyAction,AcceptReplyAction,VoteAction,UploadAttachmentAction}.php`, `Events/NewReplyPosted.php`, Community thread/reply resources, `backend/app/Users/Controllers/UserProfileController.php`, `backend/routes/channels.php`.
- Test: new `backend/tests/Feature/Community/ModerationVisibilityTest.php`; extend ThreadTest, ThreadReplyBoundaryTest, AnonymousAuthorTest, AttachmentBoundaryTest, Users/UserProfileTest.

**Interfaces**

- `ModerateForumContentAction::execute(User $actor, ReportTargetType $type, string $id, bool $hide, string $reason): Model`; thread/reply only. Transactional hide/restore/history; repeated state 409 for direct calls.
- Local visible query scopes/helpers implement thread + reply ancestry checks. No global scope. Administrative resolver reads hidden rows; member policies/resources do not.

- [ ] Test hide/unhide list/detail/profile/nested paths; hidden parent suppresses descendants and accepted answer/counts agree; restored visibility does not restore soft-deleted records. Include orphan/deleted/null parent and anonymous author snapshots/events.
- [ ] Test direct reply/vote/accept/edit/attachment IDs on hidden content; parent_id from another thread rejected; attachment ownership/access check; transactionally concurrent reply vs thread hide respects thread-before-reply locks.
- [ ] Test new channel authorization denies hidden/missing thread and suspended user. Queue a reply event, hide before broadcast evaluation, assert no public payload. Reload target/ancestors at evaluation and serialize an explicit sanitized payload; a raw public model property must not leak author identity or hidden bodies. Keep public media URL limitation explicit instead of claiming storage deletion.
- [ ] Run `cd backend && php artisan test tests/Feature/Community tests/Feature/Users/UserProfileTest.php` for relevant failures.
- [ ] Implement explicit scopes and centralized ancestry policy/locked guards. Reuse them across callers, not per-controller ad hoc checks. Keep controller logic in Actions where extracting is required by size limits. Preserve owner delete and existing anonymization.
- [ ] Repeat tests/static checks; commit `feat: enforce reversible forum moderation`.

## Task 8: Enforce marketplace/demand visibility without financial side effects

**Files**

- Create: `backend/database/migrations/2026_10_08_000006_add_marketplace_moderation_fields.php`, `backend/app/Domain/Marketplace/Actions/ModerateMarketplaceContentAction.php`.
- Modify: models `ForwardContract.php`, `HarvestListing.php`, `CropDemand.php`; `ForwardContractRepositoryInterface.php` and `EloquentForwardContractRepository.php`.
- Modify: `backend/app/Marketplace/Controllers/{MarketplaceController,PurchaseController,CropDemandController}.php`, `backend/app/Policies/CropDemandPolicy.php`, `backend/app/Domain/Marketplace/Actions/{CreateCashPurchaseAction,SplitPurchasableAction,SubmitDemandOfferAction,DecideDemandOfferAction}.php`.
- Modify: marketplace item/contract/demand/offer resources; `backend/app/Domain/CropRecommendation/Actions/BuildAnalysisContextAction.php`, `backend/app/Infrastructure/Marketplace/Repositories/EloquentCropReferencePriceRepository.php` for derived discovery.
- Test: `backend/tests/Feature/Marketplace/{MarketplaceItemVisibilityTest,ModerationCheckoutConcurrencyTest}.php`; extend CheckoutBoundaryTest, ReverseMarketplace/DemandTest, OfferTest, OfferPaymentTest, DemandChatTest, Security/WebhookIntegrityTest and price/analysis tests.

**Interfaces**

- `ModerateMarketplaceContentAction::execute(User $actor, ReportTargetType $type, string $id, bool $hide, string $reason): Model`; contract/listing/demand only; return root for split items.
- Extend ForwardContract repository with `findById(int $id): ForwardContract`, `findModerationRootLocked(int $id): ForwardContract`, `paginateForAdmin(array $filters, int $perPage): LengthAwarePaginator`, and public visibility query shape required by MarketplaceController. Keep existing `findByIdLocked`/update; no new direct contract queries.
- effective visibility includes moderation_root_id. Null root means self; split stores original root and never copies inconsistent root/hidden flags. Always root-before-item locking for checkout and moderation.

- [ ] Add contract AND listing tests for list/public-detail suppression, owner/purchase history intact, is_purchasable false, public demand hidden while existing party access remains. Notes stay admin-only.
- [ ] Assert cash/online checkout rejection calls no gateway, creates no purchase and changes no quantity when hide wins; mock current PayMongo service/interface. Test normal checkout first then hide: existing purchase completes/cancels/reconciles normally, root-hidden clones never republish after gateway failure or cancellation.
- [ ] Implement true concurrency checks with separate PostgreSQL processes/connections and synchronization barriers, not sleeps or sequential HTTP calls. Test full and partial checkout, both lock orderings, root/clone selected for hide, restoring root, historical null roots and duplicate webhooks. Assert no deadlock/quantity drift.
- [ ] Test hidden demand blocks new offer and pending acceptance, while already-accepted cash/online payment, reject/withdraw/cancel/deliver/settle/complete/chat/address policies retain behavior. No hidden flag cleared by payment callbacks or quantity restoration.
- [ ] Run `cd backend && php artisan test tests/Feature/Marketplace tests/Feature/ReverseMarketplace tests/Feature/Security/WebhookIntegrityTest.php` for expected new failures. Keep existing failure baseline separate.
- [ ] Add fields/root FK and explicit visibility scopes; carry root in SplitPurchasableAction. Recheck visibility under existing locks in both checkout paths; do not guard accepted-offer payment as a new offer. Extend repository and extract only touched oversized PurchaseController orchestration as needed, preserving gateway behavior.
- [ ] Exclude hidden demands from fresh analysis context and hidden contract/listing rows from new reference-price sampling; keep existing cache key formats and TTL behavior. Do not invalidate historical financial/credit results.
- [ ] Repeat targeted tests plus crop-analysis/price tests. Read llm-integration skill before modifying BuildAnalysisContextAction, since it affects prompt context. Commit `feat: moderate marketplace visibility safely`.

## Task 9: Add admin content review and atomic report decisions

**Prerequisite:** Tasks 6, 7 and 8 complete. The concrete moderation Actions from Tasks 7–8 must exist before connecting decision writes.

**Files**

- Create: `backend/app/Admin/Controllers/{AdminContentController,AdminReportController}.php`, `Requests/{AdminContentFilterRequest,AdminReportFilterRequest,DecideContentReportRequest}.php`, `Resources/{AdminContentResource,AdminReportResource}.php`.
- Create: `backend/app/Domain/Shared/Actions/DecideContentReportAction.php`, `backend/app/Policies/AdminContentPolicy.php`; extend resolver with allowlisted admin pagination/detail dispatch.
- Modify: admin routes, provider, report repository, history enum if required.
- Test: `backend/tests/Feature/Admin/{AdminContentTest,AdminReportDecisionTest}.php`.

**Interfaces**

- `DecideContentReportAction::execute(User $actor, ContentReport $report, ContentReportStatus $status, ?string $outcome, string $note, int $expectedVersion): ContentReport`.
- ContentTargetResolver `paginate(ReportTargetType $type, array $filters, int $perPage): LengthAwarePaginator`; delegates repository-backed ForwardContract queries. No arbitrary SQL table input.
- Lock order: report row→content root/thread/demand→reply/item where needed. Direct hide takes only target hierarchy locks; never lock reports from inside a target moderation Action.

- [ ] Test all allowed/forbidden transitions, terminal reports, note 0/1/500/501 and missing/wrong expected_version. Two parallel decisions on one version: exactly one succeeds, one 409; version increments once. GET causes no mutation.
- [ ] Test resolved-hidden atomically updates visibility/report/history; injected failure rolls all back. Already-directly-hidden resolution adds decision history without duplicate hide; a reply only ancestor-hidden gains its own flag and remains hidden after thread restoration. Null snapshot on a valid legacy target does not disable moderation. Missing target permits no_action/dismiss, rejects hidden; restore does not reopen report. Reporter identity/snapshot appear only in admin responses.
- [ ] Test content type/search/visibility pagination and null/deleted author rendering. Contract/listing moderation of a split resolves the canonical root ID; response includes both selected ID and affected root ID so confirmation is honest.
- [ ] Run `cd backend && php artisan test tests/Feature/Admin/AdminContentTest.php tests/Feature/Admin/AdminReportDecisionTest.php` before implementation.
- [ ] Implement thin policy-checked controllers and transactional Action using Tasks 7/8 moderation Actions. Snapshot remains immutable. Non-admin/guest fence precedes bindings for every new endpoint.
- [ ] Repeat tests plus Reporting/visibility suites; commit `feat: add admin report review and content decisions`.

## Task 10: Add member report controls and admin moderation pages

**Files**

- Create: `frontend/src/constants/reporting.js`, `frontend/src/components/organisms/ContentReportForm.vue`, `frontend/src/composables/useContentReport.js`.
- Create: `frontend/src/pages/dashboard/admin/{AdminContentPage,AdminReportsPage}.vue`, `frontend/src/composables/{useAdminContent,useAdminReports}.js`, `frontend/src/components/organisms/AdminReportDetail.vue`.
- Modify: `frontend/src/components/molecules/{ThreadCard,ReplyCard,ContractCard,DemandCard}.vue`, `frontend/src/components/organisms/ContractGrid.vue`, `frontend/src/pages/dashboard/shared/{ForumPage,ThreadPage}.vue`, `frontend/src/pages/public/MarketplacePage.vue`, `frontend/src/pages/dashboard/farmer/BrowseDemandsPage.vue`, `frontend/src/stores/forumStore.js`, router/navigation.
- Test: new reporting composable/form/admin page specs, existing ThreadPage/card/forumStore/market/demand specs.

**Interfaces**

- Card report event: `{reportable_type, reportable_id, title}`; propagate reply events recursively. IDs retain original string/number representation without lossy coercion; normalized new request uses decimal string.
- `useContentReport()` owns target/draft/error/busy, validates, and returns a submit function that rejects on failure; page composes it with its existing single confirmation owner. Passive ContentReportForm emits edits/submit, never calls API.
- Admin detail displays source snapshot + current state, reporter, notes/version; context-specific actions consume Task 9 APIs. Confirmation for clone moderation names affected root/future split family.

- [ ] Test all five types, anonymous replies, guest/unverified/owner visibility, event propagation and target identity collisions (contract 7 ≠ listing 7). Report click must not trigger checkout, card navigation, vote or offer submission.
- [ ] Test reason/description boundaries and other-required; cancel sends zero POST; duplicate click sends one; error retains description and target; successful/duplicate receipt closes safely. Changing target resets draft/error; late list/detail responses cannot overwrite a newer selected target.
- [ ] Test admin filters/detail/missing target, no-action/dismiss/hide/restore confirmations, required note, expected_version conflict retaining note + offering reload, and reports staying terminal after restoration. Do not expose internal notes to member cards.
- [ ] Run new focused specs and existing forumStore/ThreadPage/market/demand specs to see expected failures. Update forumStore.reportContent to return/rethrow rather than swallowing failures; retain compatibility for any callers.
- [ ] Implement controls using shared AppModal/FormField/textarea/counters/buttons. Admin content detail reads admin endpoint even where member role routes would deny access. Keep one shared confirmation per page and null-safe target rendering.
- [ ] Repeat units/lint/format/build; commit `feat: expose content reporting and admin moderation`.
- [ ] Milestone B demonstration: report thread, nested reply, contract, harvest listing and demand as permitted members; review/hide/restore as admin; verify normal user direct URL and new checkout/offer denials without changing existing purchases.

## Task 11: Add private member issue tickets and admin transitions

**Files**

- Create: `backend/database/migrations/2026_10_08_000007_create_issue_tickets_table.php`, `backend/app/Constants/IssueConstants.php`.
- Create: `backend/app/Domain/Contact/{Models/IssueTicket,Enums/IssueCategory,Enums/IssueStatus,Repositories/IssueTicketRepositoryInterface,Actions/CreateIssueTicketAction,Actions/TransitionIssueTicketAction}.php`, `backend/app/Infrastructure/Contact/Repositories/EloquentIssueTicketRepository.php`.
- Create: `backend/app/Contact/{Controllers/IssueTicketController,Requests/StoreIssueTicketRequest,Resources/IssueTicketResource}.php`, `backend/app/Admin/{Controllers/AdminIssueController,Requests/AdminIssueFilterRequest,Requests/TransitionIssueTicketRequest,Resources/AdminIssueResource}.php`, `backend/app/Policies/IssueTicketPolicy.php`.
- Modify: routes/provider; create `backend/app/Contact/Requests/IssueTicketFilterRequest.php` using shared pagination/status bounds.
- Test: `backend/tests/Feature/Contact/IssueTicketTest.php`, `backend/tests/Feature/Admin/AdminIssueTest.php`.

**Interfaces**

- `CreateIssueTicketAction::execute(User $reporter, array $validated): IssueTicket` and `TransitionIssueTicketAction::execute(User $actor, IssueTicket $issue, IssueStatus $status, ?string $resolution, int $expectedVersion): IssueTicket`.
- Repository `findForReporter(int $id, int $userId): IssueTicket`, `findLockedById(int $id): IssueTicket`, `findByRequestId(int $userId, string $requestId): ?IssueTicket`, `create(array $attributes): IssueTicket`, `save(IssueTicket $issue): void`, `paginateForReporter(int $userId, array $filters, int $perPage): LengthAwarePaginator`, `paginateForAdmin(array $filters, int $perPage): LengthAwarePaginator`.

- [ ] Test verified buyer/farmer create/read own; guest 401, unverified/admin submit 403, cross-user GET 404, list scope fixed server-side. Attempt injected user_id/status/resolved_by fields cannot change ownership/workflow.
- [ ] Test subject 0/1/150/151, description 0/1/5,000/5,001, resolution 0/1/2,000/2,001; categories and page paths (external, protocol-relative, query/token, fragment, control chars) rejected. IDs/filter/page bounds follow spec.
- [ ] Test key replay 200 with one ticket, changed payload 409, simultaneous create one row; 5/minute and 20/day throttles. Admin transitions require expected_version, resolution where needed, preserve history and increment once; stale/repeated/invalid transitions 409. Reporter deletion retains ticket with null actor identity.
- [ ] Run `cd backend && php artisan test tests/Feature/Contact/IssueTicketTest.php tests/Feature/Admin/AdminIssueTest.php` for failure.
- [ ] Implement aggregate/repository/policies and transactional history; no mail, upload or payment side effect. Null users render as unavailable without exposing cached private identity. Use safe relative paths only.
- [ ] Repeat tests/static checks and commit `feat: add member issue tickets and admin resolution`.

## Task 12: Build Report an issue / My issues and admin issue pages

**Files**

- Create: `frontend/src/pages/dashboard/shared/ReportIssuePage.vue`, `frontend/src/pages/dashboard/admin/AdminIssuesPage.vue`, `frontend/src/constants/issues.js`, `frontend/src/composables/{useIssueTickets,useAdminIssues}.js`, `frontend/src/components/organisms/IssueTicketForm.vue`.
- Modify: router/navigation; create shared/admin issue-page and issue-form specs.

**Interfaces**

- `/dashboard/issues` is shared buyer/farmer verified route (not available to admin); contains create form and own paginated history/detail. `/admin/issues` consumes admin APIs. Both have explicit accessible page titles.
- Capture only router pathname as optional context, never full current URL/query. UUID survives retries; regenerate only after accepted receipt or deliberate new draft. Page owns submit/transition confirmation.

- [ ] Test both roles/mobile nav link and preserved profile/address links; subject/description/category/path bounds; copy warns not to include passwords/payment credentials without asking for implementation details.
- [ ] Test form→confirmation cancel/no request, busy/exactly-one request, inline failed draft, uncertain retry same UUID, accepted receipt/status; list failure→Retry and own-detail missing/deleted author safety.
- [ ] Test admin category/status/search, resolution boundaries, reopen clears current resolution, expected_version conflict/reload and private notes not appearing in member view. A payment issue triggers no payment call.
- [ ] Run the new issue page/form specs for failure; implement shared controls and focused composables. Avoid one component containing both member/admin policy branches.
- [ ] Repeat units/lint/format/build; commit `feat: add issue reporting and admin issue inbox`.

## Task 13: Complete the operational overview with scoped counts

**Files**

- Create: `backend/app/Domain/Shared/Actions/GetAdminOverviewAction.php`, `backend/app/Admin/Controllers/AdminOverviewController.php`, `backend/app/Admin/Resources/AdminOverviewResource.php`.
- Create: `frontend/src/pages/dashboard/admin/AdminOverviewPage.vue`; modify role-home mapping/router/navigation.
- Modify: `AdminUserPolicy` to add `viewOverview(User $user): bool` and register/authorize this ability against User; admin routes/provider. Extend new aggregate repositories with `countsByStatus(): array`; ContentTargetResolver with `visibilityCounts(ReportTargetType $type): array`; existing contract repository owns its count query. Keep primitive scoped User/ContactMessage aggregates in the Action unless an interface exists by this point.
- Test: `backend/tests/Feature/Admin/AdminOverviewTest.php`, `frontend/src/pages/dashboard/admin/__tests__/AdminOverviewPage.spec.js`.

**Interfaces**

- `GetAdminOverviewAction::execute(): array` → `{users:{members_total,members_suspended}, content:{thread,reply,contract,listing,demand}, inquiries:{unread,read,replied,closed,failed_replies}, reports:{open,reviewing,resolved,dismissed}, issues:{open,in_progress,resolved,closed}, generated_at}`. Each content bucket is `{total,visible,hidden}`.
- Counts use the same effective visibility/filter semantics as list endpoints, including thread ancestry/root-hidden clones. Totals exclude soft-deleted content. No revenue/trend/traffic fields.

- [ ] Seed more than one page of mixed role/status content, split roots/clones and hidden ancestors. Assert exact full-dataset counts, total=visible+hidden for each bucket, admin identities excluded from member totals, failed replies independent of inquiry status, and no writes during GET.
- [ ] Test every card link opens the matching list/filter and returns consistent totals. Missing/failed overview displays unknown/loading/error, never zero-success placeholder. Manual Refresh retains last successful timestamp only until a new result arrives.
- [ ] Run `cd backend && php artisan test tests/Feature/Admin/AdminOverviewTest.php` and new frontend overview spec for failure.
- [ ] Implement aggregate queries through required repositories, avoiding per-row loops/eager loading entire tables. Compose PageHeader/StatCard/loading/error primitives; switch admin role home to admin-overview. No polling/chart library.
- [ ] Repeat tests/lint/format/build and commit `feat: add operational admin overview`.

## Task 14: Browser behavior and reviewed visual coverage

**Files**

- Modify: `frontend/e2e/fixtures/{data,session}.js`; add `frontend/e2e/fixtures/admin.js`.
- Create: `frontend/e2e/Admin/{access,operations,moderation,issues}.spec.js`.
- Extend: `frontend/e2e/IdentityAndAccess`, `frontend/e2e/Shared/navigation.spec.js`, `frontend/e2e/Community/community.spec.js`, `frontend/e2e/Marketplace/transactions.spec.js`, `frontend/e2e/Visual/redesign.spec.js` with representative new surfaces.

**Interfaces**

- Add explicit ADMIN fixture and role selection; no “non-buyer means farmer” fallback. Preserve unwrapped /user response, fixed clock, en-PH/Asia/Manila and unexpected-request/pageerror failure rules.
- Admin mocks match actual envelopes, versions, string IDs and response codes. HTTP mocks verify UI contracts; they do not substitute for backend authorization/concurrency tests.

- [ ] Write browser scenarios: provisioned verified admin session→overview→users suspend/restore; inquiry reply queued→failed→retry→sent→close; report each target→review→hide/restore; member issue create/history→admin resolution; non-admin direct admin navigation rejected.
- [ ] Assert cancel sends zero mutation calls; confirm sends one exact method/path/body; delayed response blocks repeat/dismiss; 409/422/429/500 shows actionable inline feedback and preserves drafts. Test background list request finishing after selection changes cannot display the wrong detail.
- [ ] Cover real keyboard focus containment/restoration across form+confirmation, Escape, Tab/Shift+Tab, mobile navigation, own profile/address reachability, 360×640/390px/768px/1440px, 200% text, long values, reduced motion and no page overflow. Assert admin loads no chat/trading APIs.
- [ ] Run `cd frontend && npx playwright test e2e/Admin e2e/Community e2e/Marketplace e2e/Shared --project=chromium --project=mobile-chromium` against the production build and fix failures.
- [ ] Capture representative overview, inquiry failure, report review and issue form pixels in the approved Linux environment. Inspect each new/changed screenshot and diff; only then accept baselines. Replay without snapshot updates. macOS captures are review evidence, not Linux baseline approvals.
- [ ] Run shared/auth Firefox/WebKit smoke plus admin access/dialog smoke: `npx playwright test e2e/IdentityAndAccess e2e/Shared e2e/Admin/access.spec.js --project=firefox --project=webkit`. Commit `test: cover admin and reporting browser workflows`.

## Task 15: Release rehearsal, full verification and operator runbook

**Files**

- Create: `docs/operations/admin-operations.md` (add a narrow .gitignore allowlist for this runbook) (account bootstrap, queues, report cutover, rollback boundaries and known limits).
- Modify: `frontend/README.md` only to add admin browser commands if needed; this plan's verification record after execution.
- Tests: complete current suites and new migration/concurrency cases.

- [ ] On a disposable PostgreSQL copy, run migrations over populated legacy user/contact/forum data. Compare row counts, IDs, timestamps, statuses, reasons and FK behavior; test missing-target rows and next generated ID. Verify no old code still writes forum_reports (`rg 'ForumReport|forum_reports' backend/app backend/routes`). Historical migrations/tests/runbook references are expected.
- [ ] Complete the admin endpoint matrix over every registered route, including known/missing IDs and wrong-parent nested replies. Verify no member endpoint accepts admin role injection or arbitrary owner/snapshot/status fields.
- [ ] Run the full commands below using supported runtimes and real test services. Record command/date/result/count and any pre-existing failures separately. Never label this release passing while required new checks are failing or unrun.
- [ ] Rehearse deploy: backup, maintenance/write pause, drain/stop old workers, migrate rename+new code as a coordinated cutover, restart workers, clear/rebuild relevant caches, verify counts, then resume. Rehearse failed dispatch/sending-lease recovery without sending mail to real users.
- [ ] Document `php artisan admin:create operator@example.test` with hidden interactive password, email verification/resend, token-expiration behavior, password reset, no trading-account conversion, no default credential, and console access as the recovery boundary; no public recovery bypass or direct SQL recipe. Do not execute against production as part of plan writing.
- [ ] Record media-URL and existing-socket limitations, old split lineage limitation, SMTP at-least-once semantics, privacy access rules, no retention purge, and forward-only recovery after real evidence is written. Never describe a destructive down migration as safe rollback.
- [ ] Request a latest-diff security/correctness review with focus on IDOR, registration privilege escalation, lock order, financial preservation, queue retry, migration loss and null/XSS behavior. Fix findings, rerun affected checks and review the latest revision.
- [ ] Commit documentation/verified fixes as `docs: document admin operations and release checks`. Mark A/B/C complete only after their demonstrated journeys and required checks pass. Deployment and account provisioning remain a separate explicit operational action.

## Verification commands and environment

Commands are run from the stated directory; do not run migrations against a live DB
for tests. backend/phpunit.xml targets yieldgrid_testing/PostgreSQL with sync queue,
so queue timing/retry cases must explicitly fake transport and exercise worker/job
transitions or use an isolated async test queue. Real concurrency uses independent
connections/processes and committed fixtures, not one enclosing test transaction.

Backend, PHP 8.3 with PostgreSQL/PostGIS and Redis available:

```sh
cd backend
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --level=6
php artisan test
```

If PHPStan's parallel listener is sandbox-blocked, record the condition and use the
repository's documented `./vendor/bin/phpstan analyse --level=6 --debug` fallback.
Do not claim the normal command passed. Run formatter write mode only on intended
files before checking; do not sweep unrelated changes.

Frontend, Node ^22.18.0 or >=24.12.0:

```sh
cd frontend
npm run lint
npm run lint:check
npm run format:check
npx prettier --check "src/**/*.{vue,js,ts}"
npx prettier --check "e2e/**/*.js" playwright.config.js vite.config.js
npx vitest run --maxWorkers=2
npm run build
npm run test:e2e
npx playwright test e2e/IdentityAndAccess e2e/Shared e2e/Admin/access.spec.js --project=firefox --project=webkit
```

npm run lint fixes files: inspect its diff. Browser baseline commands/environment
and pinned container digest are in frontend/README.md. Run baseline comparisons
without --update-snapshots after review. This task changes no map rendering; if a
later implementation does, add the required real-tile check then.

## Spec coverage and execution handoff

| Spec requirement | Tasks |
|---|---|
| Dedicated account/public registration guard | 1–2 |
| Access policies/suspension/session/role UI | 2–3 |
| Inquiries/persistent delivery/recovery | 4–5 |
| All five report targets, migration/duplicates | 6, 10 |
| Atomic decisions, reversible hide, audit | 1, 7–9 |
| Forum/direct/profile/event visibility | 7 |
| Marketplace split lineage/demand/payment preservation | 8 |
| Member issues/history/admin resolution | 11–12 |
| Monitoring/content browser/full counts | 9, 10, 13 |
| Confirmation/accessibility/responsive/error feedback | 3, 5, 10, 12, 14 |
| Migration safety/deploy/runbook/complete checks | 15 |

Execution recommendation: subagent-driven, with separate reviewers for the access
fence and financial visibility work. The contracts share routes/providers but the
forum and marketplace enforcement can be isolated after Task 6. Review this spec
and plan before implementation; no execution method or implementation approval is
implied by requesting these documents.

### Verification record

Planning-time checks: source inspection, documented existing-path checks and
Markdown/diff review only. Product test commands above are pending execution.
