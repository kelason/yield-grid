# YieldGrid Modern Minimalist Organic Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [x]`) syntax for tracking.

**Goal:** Redesign YieldGrid as a modern minimalist organic website, retaining Atomic Design and delivering consistent reusable UI with meaningful browser regression coverage.

**Architecture:** Evolve the existing atoms, molecules, organisms and templates; preserve public component contracts and route/store boundaries. A shared token module powers Tailwind and JS charts/maps. Establish common control/dialog/loading behavior, prove it on both dashboards, then migrate remaining route families.

**Tech Stack:** Vue 3 Composition API, Vite, Tailwind 3, Pinia, Vue Router, existing Heroicons/Chart.js/Leaflet, Vitest and Playwright. No new runtime UI/form/animation library.

**Spec:** [Modern organic design and frontend assessment](../specs/2026-10-07-modern-organic-redesign.md).

**Status:** All 13 tasks implemented and verified on `codex/modern-organic-redesign` on 2026-10-07. Integration into `develop` is pending; nothing has been pushed or deployed.

## Global Constraints

- Keep `moss`, `harvest`, `soil`, `dew`, and `stone` names so existing consumers continue to resolve.
- Retain Lora for display/section headings and Inter for controls/body.
- Keep radii: controls 12px (`rounded-xl`), cards 16px (`rounded-2xl`), dialogs 24px (`rounded-3xl`), badges/search pills fully rounded.
- Shared buttons and icon controls target at least 44×44 CSS px. Keep navigation and form layout usable at 360px wide and with text enlarged to 200%.
- Preserve routes, roles, API payloads, payment calculations, input limits, confirmation requirements, and verification guards.
- No new runtime UI, animation or form library is required.
- Normal text contrast ≥4.5:1; large text and meaningful non-text controls ≥3:1. Verify actual foreground/background combinations; a passing palette pair does not certify a screen.
- Components stay in atomic directories; stateful logic in `src/composables/use*.js`; global state stays in existing stores. Functions ≤30 lines; decompose components with mixed responsibilities near 200+ lines.
- All state mutations require existing confirmation conventions; atoms never trigger APIs or open confirmations automatically. Read-only navigation remains direct.
- Keep min/max/length limits, counters and submit-time checks; all changed optional object props support null, including loading transitions.
- Use CSS classes for Vue presentation, named constants for limits, timings and test fixture values, and existing reduced-motion behavior. Do not add inline styles/raw colors during migration.
- Use Node 22.18.0 to match current CI or a version satisfying the package engine range. Current host 24.4.1 is unsupported.

## Review Focus

1. A confirmation opens above a form dialog while a request is pending: only the top dialog receives input; Escape/backdrop cannot dismiss a pending mutation; closing restores the correct focus. Tasks 4 and 8.
2. Null/empty/failed data appears during route transitions or retries: no crash, false zero KPI, misleading empty result or duplicate load announcement. Tasks 5, 7, 10 and 11.
3. Long Tagalog labels, user names and peso amounts at 360px or enlarged text: actions remain reachable, text wraps, and page chrome does not create horizontal overflow. Tasks 6–13.
4. Wrapper refactoring moves disabled/step/autocomplete/ARIA or layout classes to the wrong node: native behavior and wrapper layout both survive. Task 3.
5. Dashboard totals use a partial response, or role navigation exposes another role's pages: data is honestly labeled and authorization/verification behavior survives the visual changes. Tasks 6 and 7.

## Execution sequence and ownership

`1 baseline → 2 tokens → 3 controls → 4 dialogs → 5 presentation states → 6 shells → 7 dashboards → 8–12 route families → 13 final browser coverage`.

Tasks 8–12 may use separate workers only after shared interfaces are stable. Shared primitive changes remain with one owner. Each task leaves a working slice with targeted checks. Use an isolated worktree at execution time; these planning artifacts and local skills must be available there. Each task ends with a focused commit after its verification, and no automatic merge/deploy.

The detailed specification owns exact palette values and component contracts. New files are introduced only where two consumers already exist or a current component violates a meaningful responsibility boundary. Tests check behavior, not Tailwind class strings.

## Task 1: Establish trustworthy browser fixtures and baseline

**Files:** Modify `frontend/playwright.config.js`, `frontend/e2e/IdentityAndAccess/auth.spec.js`, `frontend/e2e/Insurance/enrollment.spec.js`, `frontend/package.json`, `.github/workflows/ci.yml`; create `frontend/e2e/fixtures/session.js`, `frontend/e2e/fixtures/data.js`, `frontend/e2e/constants.js`. Documentation ignore exceptions for AGENTS.md/docs/superpowers are already prepared in this planning change.

**Interfaces:** `mockSession(page, { role = 'farmer', verified = true } = {}) -> Promise<void>` installs synthetic auth and unwrapped `/user`, empty chat bootstrap and controlled realtime routes before navigation. `assertNoUnexpectedApiRequests() -> void` reports unhandled requests collected by the fixture at teardown. Export stable `FARMER`, `BUYER`, `FARMS`, `PURCHASES`, `FIXED_NOW`, viewport and HTTP constants from the fixture modules; IDs and dates must match the current API resource shape.

- [x] Use a supported Node runtime and record `node --version`, installed Playwright version, `git status`, current test discovery and existing screenshot absence. Do not update dependencies as part of environment setup.
- [x] Extend the insurance test to assert the rendered verified farmer identity and role navigation. Run `npx playwright test e2e/Insurance/enrollment.spec.js --project=chromium`; confirm the current incorrect `/user` envelope fails the new assertions for that reason.
- [x] Correct `/user` to the bare user object, add `/chat/conversations`, and route WebSocket traffic before navigation. Use a catch-all API route registered before specific mocks; unexpected calls return a deterministic failure and are asserted at teardown. Never fall through to production/local backend or Stripe.
- [x] Set test Vite to `127.0.0.1:4173` with `--strictPort`, `reuseExistingServer: false`, explicit local mock API URL, locale `en-PH`, timezone `Asia/Manila`. Create projects `chromium` (1440×900), `mobile-chromium` (390×844), `firefox` and `webkit` (targeted smoke, excluded from default PR command). Keep retries as-is; preserve trace-on-first-retry and artifact uploads.
- [x] Add a read-only `lint:check` script (`oxlint . && eslint . --no-cache`) and have CI call it. Preserve the user's explicit local auto-fix scripts. CI installs Chromium for default tests; add an explicit manual smoke input/job to install Firefox/WebKit for those projects.
- [x] Run corrected existing tests in Chromium and mobile Chromium, asserting insurance PUT payload plus saved UI. Keep semantic-selector migration that requires new dialogs in Task 4. Expected: role/verification assertions and the existing business outcome pass, no unexpected API traffic.
- [x] Capture before screenshots as local review evidence, not approved regression baselines. Verify the prepared documentation ignore exceptions and commit only the verified harness and documentation changes.

## Task 2: Introduce the Field & Linen tokens

**Files:** Create `frontend/src/constants/designTokens.js`, `frontend/src/constants/__tests__/designTokens.spec.js`; modify `frontend/tailwind.config.js`, `frontend/src/assets/main.css`, local `AGENTS.md`, local `.agents/skills/yieldgrid-design-system/SKILL.md`; the linked spec records the versioned design rules.

**Interfaces:** `DESIGN_COLORS` has `moss/harvest/soil/dew/stone` keys, each with shades 50–900 exactly as the spec table. `DESIGN_SHADOWS` exposes soft/organic/harvest-glow exact spec strings. Tailwind imports these with no duplicated palette object. Runtime consumers import only this small module, not Tailwind or its resolver.

- [x] Add one contrast regression test using the standard sRGB luminance formula: assert white/moss[500], white/moss[600], stone[900]/stone[50], stone[600]/stone[50], stone[500]/stone[50], harvest[700]/harvest[50], dew[700]/dew[50] are `>= 4.5`. Run `npx vitest run src/constants/__tests__/designTokens.spec.js` and verify it fails before the module exists.
- [x] Add exact token values; preserve font families, breakpoints and all existing palette names. Retain global focus-visible/reduced-motion behavior and tune body surfaces/type according to the spec.
- [x] Update the local design-rule documents with the new palette, calmer elevation, modern heading scale, and restrained decorative treatment. Keep the authoritative proposed rules in the tracked spec; do not unignore unrelated local skills/configuration.
- [x] Run the contrast test, `npm run build`, and inspect home, login and both dashboard shells for inherited color regressions. Expected: all token imports resolve; listed pairs pass; normal text on gradient endpoints remains readable. Tinted badge/hover pairs are checked in their owning components, not assumed to pass.
- [x] Commit the token change after reviewing site-wide inherited effects.

## Task 3: Consolidate buttons, inputs, selects and textareas

**Files:** Modify `atoms/AppButton.vue`, `AppInput.vue`, `AppSelect.vue`, `molecules/FormField.vue`, `SearchInput.vue`, `SortSelect.vue`, `ContractFilter.vue`, `DemandFilter.vue`; create `atoms/AppTextarea.vue`, `atoms/AppSpinner.vue`. All component paths are under `frontend/src/components/`. Extend existing corresponding specs; create `atoms/__tests__/AppTextarea.spec.js`, `molecules/__tests__/SearchInput.spec.js`, `SortSelect.spec.js`.

**Interfaces:** Use the contracts in spec §5. `FormField` adds `multiline`, `hint`, `disabled`; preserves `labelSuffix`. Keep input/select emission types and native option slots. Add AppButton's actual `icon` slot. AppSpinner is decorative and inherits currentColor.

- [x] Add failing contract assertions: `wrapper.get('input').element.disabled === true` when FormField disabled; `step`, `autocomplete`, `name`, `readonly` reach input; wrapper grid class stays on wrapper; input's aria-describedby names rendered error/hint; invalid state toggles; maxlength clamp still handles pasted numeric input. Cover ResetPasswordForm's disabled email regression.
- [x] Add textarea tests for paste beyond maxlength, external v-model update, null-safe counter presentation, label association, and native minlength; retain feature-specific submit validation. Add button checks that loading disables click/submit, retains accessible name and sets aria-busy.
- [x] Run affected Vitest specs and verify failures reflect the missing behaviors, then implement the smallest forwarding/semantic changes. Deliberately separate wrapper attrs from control attrs instead of applying all `$attrs` blindly.
- [x] Compose SearchInput/SortSelect from the existing atoms; associate ContractFilter labels by ID. Preserve filter values and emits. Trace existing backend query bounds and use their values; record unmatched validation gaps separately rather than inventing frontend-only rules.
- [x] Migrate ConfirmModal button/spinner markup in Task 4 and remaining callers in their route batches. Do not convert radio/checkbox/file/range controls into text inputs.
- [x] Run `npx vitest run src/components/atoms/__tests__ src/components/molecules/__tests__/FormField.spec.js src/components/molecules/__tests__/SearchInput.spec.js src/components/molecules/__tests__/SortSelect.spec.js src/components/organisms/__tests__/ResetPasswordForm.spec.js`; run lint:check and format:check. Commit the control contracts.

## Task 4: Make AppModal the shared accessible shell

**Files:** Modify `molecules/AppModal.vue`, `ConfirmModal.vue`, `frontend/src/composables/useConfirmModal.js`, its existing spec, and all four current AppModal callers to supply accessible titles. Create `molecules/__tests__/AppModal.spec.js`, `ConfirmModal.spec.js`, `frontend/e2e/Shared/confirmation.spec.js`; update `frontend/e2e/Insurance/enrollment.spec.js`.

**Interfaces:** Preserve existing props/events; add spec §5 AppModal props plus `placement: 'center' | 'left' = 'center'` for the known mobile navigation consumer. Sizes map to max-w-md/max-w-xl/max-w-2xl. Footer and default slots remain passive presentation. ConfirmModal's loading maps to AppModal busy and disables both buttons. Page callers bind `isExecuting` or their existing pending flag.

- [x] Add unit assertions that title/message produce the dialog's accessible name/description, close/cancel/confirm events preserve existing names, loading prevents emission, and opening another confirmation resets old custom labels. Use minimal dialog API stubs in jsdom only; do not treat them as focus verification.
- [x] Run focused tests, observe intended failures, then replace the duplicated shell with native dialog showModal/close. Guard redundant opens and synchronize parent isOpen with user close requests; avoid double close events. A close button always has an accessible name.
- [x] Style the native backdrop and content using design tokens. Prevent default Escape cancellation while busy; backdrop clicks during busy do nothing. Clean up scroll locking and focus on unmount. No reliance on experimental dialog invoker/closedby features.
- [x] Add browser tests against a real pending PurchaseCard cancellation: opening/canceling produces zero POSTs; confirming produces exactly one `/checkout/:id/cancel` with the existing payload; a delayed response keeps the dialog busy; failure shows error and restores usable UI. Scope controls through `getByRole('dialog', { name: 'Cancel Purchase' })`.
- [x] Add nested dialog case using Profile address editor plus confirmation: Tab/Shift+Tab remain in top dialog, Escape closes top only when idle, focus returns to form then trigger, and scrolling/keyboard work in a 360×640 viewport. Check route unmount during an idle dialog cleans up modality.
- [x] Replace insurance `.last()` selector with named-dialog scope and field labels; assert response completion and saved state. Run modal unit specs, useConfirmModal spec, and `npx playwright test e2e/Shared/confirmation.spec.js e2e/Insurance/enrollment.spec.js --project=chromium --project=mobile-chromium`. Commit after keyboard checks pass.

## Task 5: Unify cards, loading, empty states and page headers

**Files:** Modify `atoms/AppCard.vue`, `SkeletonCard.vue`, `molecules/EmptyState.vue`; create `atoms/AppSkeleton.vue`, `molecules/LoadingState.vue`, `StatCard.vue`, `PageHeader.vue`, `molecules/__tests__/LoadingState.spec.js`, `StatCard.spec.js`.

**Interfaces:** Exact props/slots in spec §5. AppCard preserves existing padding/variant/hover and outer class behavior. EmptyState adds an action slot; StatCard uses null as unknown, not zero. LoadingState owns one status announcement; repeated skeletons are aria-hidden.

- [x] Add behavioral assertions for `StatCard({value:null})` displaying an em dash, `value:0` displaying zero, loading hiding stale values and exposing one status, and EmptyState action event plus actual icon rendering. Run tests before implementation.
- [x] Simplify surfaces and shadows without removing legacy variants. Compose SkeletonCard and EmptyState from shared atoms. Render PageHeader with the requested heading level and actions slot; no automatic routing/data fetching.
- [x] Use a real RouterLink/AppButton inside action cards; remove visual cues implying static panels are clickable. Keep required cards rounded and avoid nested links/buttons.
- [x] Run new specs and existing consumers; visually inspect long values and loading at 360/768/1440px. Do not add tests that merely assert color class names. Commit the presentation primitives.

## Task 6: Rebuild responsive shells and navigation

**Files:** Modify `templates/DashboardLayout.vue`, `PublicLayout.vue`, `AuthLayout.vue`, `organisms/AppSidebar.vue`, `AppNavbar.vue`, `AppFooter.vue`, `atoms/AppLogo.vue`, `molecules/SidebarNavLink.vue`, `NavLink.vue`; create `molecules/SidebarNavGroup.vue`, `SidebarNavigation.vue`, `frontend/src/constants/navigation.js`, `frontend/src/composables/useDashboardNavigation.js`; extend AppSidebar/DashboardLayout specs; create `frontend/e2e/Shared/navigation.spec.js`.

**Interfaces:** `useDashboardNavigation()` returns role-filtered `navigation`, `isActive(to)`, `isGroupOpen(group)`, `toggleGroup(group)` using existing route matching/verification rules. Data constants preserve existing names/routes. `SidebarNavigation` receives items/collapsed/openGroups and emits toggle/navigate. `AppSidebar` adds mobileOpen (false) and close event; uses AppModal placement left for mobile and the same list for desktop.

- [x] Add regressions for farmer/buyer and verified/unverified menus, active deep links, chat badge, collapse labels, and unchanged profile links. Preserve existing tests for chat subscribe/unsubscribe lifecycle.
- [x] Add a browser test opening navigation at 390px, keyboard-selecting a route, observing automatic menu close/focus, and using Back. Assert `page.getByRole('navigation', { name: 'Dashboard navigation' })` exposes only role-appropriate routes. Test resizing to desktop while drawer is open.
- [x] Move navigation configuration/state out of the 407-line AppSidebar; compose the shared list in desktop/mobile containers. Add skip-to-main link and a stable main ID, named menu controls, current route indication and visible focus.
- [x] Apply the new calm shell styling. Dashboard utility header avoids a second page h1; pages own PageHeader. Auth/public layouts retain routes/SEO behavior and receive usable mobile nav. Replace structural emoji with existing SVG/Heroicons, preserving the YieldGrid wordmark.
- [x] Route logout through one ConfirmModal per shell and existing useConfirmModal; cancel leaves the session intact, confirm calls the existing store once. Preserve verification banner and realtime behavior.
- [x] Run AppSidebar/DashboardLayout/router role-guard specs and new Shared/navigation browser tests in desktop/mobile Chromium. Assert no horizontal overflow at 360px and visible focus under sticky headers at enlarged text. Commit shells.

## Task 7: Redesign farmer and buyer dashboards

**Files:** Modify `frontend/src/pages/dashboard/FarmerDashboard.vue`, `frontend/src/pages/dashboard/buyer/BuyerDashboard.vue`, `frontend/src/components/molecules/FarmCard.vue`, `PurchaseCard.vue`; create `frontend/src/pages/dashboard/__tests__/FarmerDashboard.spec.js`, `frontend/src/pages/dashboard/buyer/__tests__/BuyerDashboard.spec.js`, `frontend/e2e/Dashboard/dashboard.spec.js`.

**Interfaces:** Compose PageHeader/StatCard/LoadingState/EmptyState and current stores. Farmer data remains `fetchFarms()` + farms; buyer remains `fetchBuyerPurchases()` + buyerPurchases. Keep FarmCard/PurchaseCard existing action events and confirmation callbacks.

- [x] Pin actual paginated purchase semantics with fixtures having more records than one page. Assert labels “Purchases on this page”, “Paid purchases on this page”, and “Amount paid on this page”, plus existing pagination controls; numbers match only displayed purchases. Test seeded farms with missing plots_count, buyer pending/completed payments, zero values and large peso amounts. Reset stale buyer-purchases filters when loading the overview without altering the separate purchase-history route's behavior.
- [x] Add loading→empty, loading→data, rejected-fetch→Retry→data cases; assert errors do not present empty-state copy and KPI unknown values do not display zero. Add long name/value mobile assertions.
- [x] Run failing new unit tests; compose dashboards according to spec §6. Replace farmer activity placeholder with actual farm summary and existing navigation. Reuse cards and Heroicons. Keep pages small; extract state only if its complexity requires it.
- [x] Preserve buyer cancellation payload/confirmation/busy state from Task 4. Keep one clear page heading and one primary action; advisory CTA remains read-only navigation.
- [x] Run both dashboard unit specs, `npx playwright test e2e/Dashboard/dashboard.spec.js e2e/Shared/confirmation.spec.js --project=chromium --project=mobile-chromium`, build, lint:check, format:check. Visually review both dashboards at 360/768/1440px before proceeding to the remaining routes.
- [x] Commit the first completed redesign slice. Use it as the reference for later screens.

## Task 8: Migrate marketplace and financial workflows

**Files:** Modify `frontend/src/pages/public/MarketplacePage.vue`, `pages/dashboard/buyer/MyPurchasesPage.vue`, `MyDemandsPage.vue`, `PostDemandPage.vue`, `pages/dashboard/farmer/BrowseDemandsPage.vue`, `MyOffersPage.vue`, `MyContractsPage.vue`, `ManualListingPage.vue`, `CashPaymentApprovalsPage.vue`; `components/organisms/CheckoutSummary.vue`, `FarmerContractsList.vue`, `PublishContractForm.vue`, `ContractGrid.vue`; `components/molecules/ContractCard.vue`, `DemandCard.vue`, `OfferCard.vue`, `DeliveryAddressCard.vue`, `PriceGuideHint.vue`, `PriceGuidePopover.vue`; price/status atoms. Create `components/organisms/OfferPaymentPanel.vue`, `CashPaymentReviewPanel.vue`, `frontend/src/composables/useDemandActions.js`, `frontend/e2e/Marketplace/transactions.spec.js`.

**Interfaces:** `useDemandActions()` consumes existing demand/payment/notification stores and owns the current MyDemandsPage action handlers/pending state; preserve their arguments and API payloads. OfferPaymentPanel consumes offer, payment selection, loading/error and emits the current payment selection event; CashPaymentReviewPanel consumes the selected purchase, amount/notes draft, errors/loading and emits submit/cancel. Declare explicit props matching extracted fields before moving markup. Neither panel performs an API call.

- [x] Extend existing page/checkout tests to pin original payloads, amount calculations, address selection, min/max/maxlength boundaries, and confirmation sequencing before moving UI. Include duplicate-click and request-failure cases. No weakening payment/quantity assertions to accommodate styling.
- [x] Replace page-owned overlays with AppModal + the existing CheckoutSummary or focused panel organisms. Split the large MyDemands action orchestration into useDemandActions without changing transitions. Replace unsupported AppCard body-class usage with padding.
- [x] Migrate textarea/loading/button duplication, including numeric clamping already provided by AppInput. Keep native payment radios and existing payment constants. PriceGuidePopover remains an anchored popover; do not force it into a modal.
- [x] Make DemandCard's existing view event reachable through a named real button; retain mouse behavior. Confirm card internals avoid nested interactive targets.
- [x] In `transactions.spec.js`, mock pending checkout, buyer accept/reject/cancel, farmer offer withdrawal and cash approval. Assert cancel sends zero mutation requests, confirm sends one expected method/path/payload, pending prevents repeat, 409/422 errors retain actionable feedback. Assert no external payment navigation occurs in test mocks.
- [x] Run existing affected page/organism specs and new transaction/browser confirmation tests; visually inspect long prices/addresses and empty filters at mobile/desktop. Commit in separate buyer/seller batches if diff size impedes review, with unchanged interfaces between them.

## Task 9: Correct address composition and migrate forms

**Files:** Move `components/molecules/AddressFields.vue` and `molecules/__tests__/AddressFields.spec.js` into organisms and organisms/**tests**. Modify imports in `organisms/RegisterForm.vue`, `pages/dashboard/shared/ProfilePage.vue` and test mocks; update `organisms/LoginForm.vue`, `ForgotPasswordForm.vue`, `ResetPasswordForm.vue`, `RegisterForm.vue`, `ContactForm.vue`, `CreateFarmForm.vue`, `ClaimForm.vue`, `RsbsaPanel.vue` and their existing specs.

**Interfaces:** AddressFields retains modelValue/idPrefix/showPinPicker/showLabel/errors plus update:modelValue and pin-validation. Form organisms retain submit/close events, existing limits and error handling. No map/geocoding behavior changes in this task.

- [x] Run existing address and form tests before moving files. Add FormField forwarding regressions at actual form integration points and localized long-label cases.
- [x] Move AddressFields intact to its correct Atomic Design layer; update imports and colocated test paths. Add a null guard where optional errors/transition data can arrive null. Do not change address payload shape, cascade resets, pin validation, or map bounds.
- [x] Replace repeated textareas with FormField multiline/AppTextarea and keep the exact existing counters/limits. Use shared form errors, loading and ConfirmModal; preserve submit-time validation and draft retention.
- [x] Check every migrated field against backend limits. If a mismatch requires changing a backend contract, record it as a distinct guarded change with boundary tests rather than silently widening inputs.
- [x] Run affected form/address tests and the corrected insurance browser flow. Verify password manager/autocomplete behavior and that disabled reset-email actually stays disabled. Commit the form migration.

## Task 10: Migrate farming, advisory, insurance and credit screens

**Files:** Modify `pages/dashboard/FarmManager.vue`, `PlotPlanner.vue`, `pages/RecommendationsPage.vue`, `CompatibilityPage.vue`, `pages/dashboard/farmer/InsurancePage.vue`, `CreditScorePage.vue`; their existing organisms (`PlotDrawer`, `PlotInfoPanel`, `LeafletPinPicker`, `AnalysisPreferencesForm`, `CompatibilityChecker`, `CreditScoreGauge`, `ScoreBreakdownChart`, `ScoreHistoryTimeline`, `ImprovementTips`, `EnrollmentGuide`, `EnrollmentTracker`, `ClaimTracker`, `OfficeDirectory`, `ReminderCenter`), recommendation/insurance/report/tier molecules, `atoms/AnalysisProgress.vue`, `CropConfidenceMeter.vue`, `constants/creditScoring.js`. Create `composables/useRecommendationAnalysis.js`, `usePlotMap.js`, `organisms/RecommendationAnalysisPanel.vue`, `frontend/e2e/Farming/planning.spec.js`.

**Interfaces:** `useRecommendationAnalysis({ route, router })` consumes the existing reactive route and router plus farming/recommendation stores; extracts the page's current selection/analysis state and handlers without new polling or backend calls. `usePlotMap({ mapContainer, farm, existingPlots, onPlotDrawn, onPlotError })` receives Vue refs/getters for the element, nullable farm and nullable GeoJSON FeatureCollection; it returns `isGeocodingCity`, `isCheckingZone`, `allowedCityBounds` and `zoomToPlot(plotId)`. Callbacks preserve PlotDrawer's existing `plot-drawn` payload `{ layer, coordinates }` and `plot-error` message payload. It owns Leaflet lifecycle/layers/cleanup; geometry and restrictions remain unchanged. RecommendationAnalysisPanel presents existing progress/preferences/error state.

- [x] Apply the Leaflet Geospatial skill for map changes. Preserve existing PlotDrawer null/loading-overlay regression. Add component cases for clearing farm during loading, switching farms, map resize after dialog opens, unmount cleanup, failed analysis and retry.
- [x] Break mixed map lifecycle and recommendation orchestration out of their large SFCs; keep rendering in atomic components. Use shared AppModal for analysis/publishing and shared loading/empty states. Keep existing server/queue contracts and map geometry behavior.
- [x] Replace hardcoded chart/map brand colors with DESIGN_COLORS references. Keep hazard/selected/disabled styles distinguishable by stroke/dash/labels as well as color; map overlays must stay legible over real tiles. Replace inline width/transition styles with native progress/SVG attributes or fixed token classes where appropriate; preserve units and numeric labels.
- [x] In farming browser tests, mock API and tiles locally; exercise selecting a farm/plot, analysis loading/error/result, and keyboard-operable adjacent controls. Geometry correctness remains in existing map tests; visual tests do not stand in for spatial validation.
- [x] Run affected Vitest files, insurance/farming browser tests and a manual real-tile visual check. Inspect charts at narrow widths and English/Tagalog insurance copy. Commit farming/advisory and insurance/credit as separately verified batches if necessary.

## Task 11: Migrate community, chat and profile

**Files:** Modify `pages/dashboard/shared/ChatPage.vue`, `ForumPage.vue`, `ThreadPage.vue`, `ProfilePage.vue`; `molecules/ChatComposer.vue`, `ThreadComposer.vue`, `ThreadCard.vue`, `ReplyCard.vue`, `ConversationItem.vue`; relevant chat/forum atoms. Create `organisms/ProfileAddressPanel.vue`, `composables/useProfileAddresses.js`, `frontend/e2e/Community/community.spec.js`.

**Interfaces:** ProfileAddressPanel consumes addresses, loading/errors, editor draft and emits edit/add/save/delete with existing IDs/drafts. useProfileAddresses consumes addressStore and current profile ownership; extracts existing modal/confirmation handlers without changing auth scoping. Keep chat/forum events, unread badges and subscription lifecycles intact.

- [x] Pin current profile address CRUD payloads and ownership guards in existing tests. Test null profile during route change, long messages/labels, keyboard send, draft retention after failure, and existing message/textarea length limits.
- [x] Compose shared input/button/modal/loading/card primitives. Give chat textarea and icon send action accessible names; preserve keyboard send behavior and explicit mutation confirmations required by current flows. Extract the address panel/orchestration from ProfilePage.
- [x] Keep user text escaped, including forum/message content, and maintain loading/error/empty distinctions. Do not change realtime protocols or make visual snapshots depend on a live connection.
- [x] Run existing shared-page/chat/forum specs and mocked community browser flow; verify unread changes and null→loaded transitions do not move keyboard focus unexpectedly. Commit the community/profile batch.

## Task 12: Complete public, auth and error-page design

**Files:** Modify `pages/public/HomePage.vue`, `AboutPage.vue`, `ContactPage.vue`, `CheckoutSuccessPage.vue`, `CheckoutCancelPage.vue`; all six `pages/auth/*.vue` files (LoginPage, RegisterPage, ForgotPasswordPage, ResetPasswordPage, EmailVerificationCallback, VerifyEmailPage); `pages/error/ForbiddenPage.vue`, `NotFoundPage.vue`, `ServerErrorPage.vue`; `organisms/HeroSection.vue`, `molecules/CategoryCard.vue`; create `frontend/e2e/Public/navigation.spec.js` and extend `e2e/IdentityAndAccess/auth.spec.js`.

**Interfaces:** Preserve route names/URLs, page metadata, form contracts and authentication guard behavior. Public pages compose the approved shared shells and primitives. Check VerifyEmailPage's current route reachability before styling it; do not activate an unused route as a design change.

- [x] Extend browser tests for guest home→login/register, public mobile navigation, protected redirect, cross-role denial, unverified access behavior and checkout result pages with mocked data. Assert read-only navigation never opens confirmation.
- [x] Simplify the hero and supporting sections to one clear primary action per region, purposeful whitespace, restrained nature references and one organic divider. Keep real product copy and existing assets/metadata; do not add invented social proof or placeholder imagery.
- [x] Apply the same field, button, card and loading language to auth/error/result pages. Ensure password reset and verification callbacks preserve working/error states and redirects.
- [x] Run public/auth browser tests plus existing error/auth/contact unit specs. Visually check 360px/768px/1440px and enlarged text. Commit the final page-family migration.

## Task 13: Approve visual baselines and run whole-site verification

**Files:** Create `frontend/e2e/Visual/redesign.spec.js`, its generated Chromium/Linux `redesign.spec.js-snapshots/` baselines; update fixtures/config/CI from Task 1 as needed, and `frontend/README.md` with test commands/baseline environment. Update this plan's checkboxes and the design spec with final approved decisions.

**Interfaces:** Visual cases reuse mockSession and fixed API fixtures. Use named main/dialog/nav regions and labels, not CSS class selectors. Screenshot names describe route/role/state/viewport. Use the self-hosted product Inter/Lora fonts consistently; do not substitute fonts for screenshots.

- [x] Add selected screenshot assertions after fonts/fixtures/UI readiness: populated farmer and buyer dashboards desktop/mobile, one dashboard loading/empty state, ordinary and destructive confirmation, representative form errors, marketplace and public home. Use `animations: 'disabled'`; do not mask the redesigned content to suppress differences.
- [x] Add separate behavior/layout cases for reduced motion, 360×800, 768×1024, 200% enlarged text, long localized labels and values, and short-viewport dialogs. Assert document scrollWidth does not exceed clientWidth for page chrome; allow intentional map/table internal scrolling.
- [x] Generate initial baselines in the pinned Linux Chromium environment with `npx playwright test e2e/Visual/redesign.spec.js --project=chromium --project=mobile-chromium --update-snapshots`. Review each image and diff before accepting. A missing baseline is not an automatic approval; never update snapshots just to turn CI green.
- [x] Run `npm run lint:check`, `npm run format:check`, `npx vitest run`, `npm run build`, and `npx playwright test --project=chromium --project=mobile-chromium` in the supported environment. Also check Prettier on new Playwright/config files. Expected: zero lint/format/test/build failures, no unexpected API calls or browser page errors.
- [x] Run targeted shared/navigation/confirmation/auth smoke in Firefox and WebKit after installing the matching browsers in the dedicated smoke environment. Keep screenshot comparisons limited to Chromium/Linux. Verify real Lora/Inter, maps, keyboard operation and short-screen dialogs manually.
- [x] Review all 35 page SFCs against the migration inventory; scan remaining raw tags/colors/inline styles and upward imports, distinguishing intentional native controls from duplicate styled controls. No app-wide feature folders or new design-framework dependency. Any old large component retained must have one coherent responsibility, not a deferred mixture of forms, fetching and overlays.
- [x] Run a final whole-diff review focused on financial/auth regressions, component contracts, null transitions and accessibility. Commit reviewed visual baselines and docs; report exact passing checks and any remaining limitations. CI backend checks still apply normally to the PR. Implementation added only matching server-side catalog/forum/login input bounds; domain/payment behavior is unchanged.

## Page inventory and verification ownership

Paths below are relative to `frontend/src/pages/`; all remain in their existing route directories.

| Batch   | Existing page files                                                                                                                                                                                                                                                                                                                                                            |
| ------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Task 7  | dashboard/FarmerDashboard.vue; dashboard/buyer/BuyerDashboard.vue                                                                                                                                                                                                                                                                                                              |
| Task 8  | public/MarketplacePage.vue; dashboard/buyer/MyPurchasesPage.vue; dashboard/buyer/MyDemandsPage.vue; dashboard/buyer/PostDemandPage.vue; dashboard/farmer/BrowseDemandsPage.vue; dashboard/farmer/MyOffersPage.vue; dashboard/farmer/MyContractsPage.vue; dashboard/farmer/ManualListingPage.vue; dashboard/farmer/CashPaymentApprovalsPage.vue                                 |
| Task 10 | dashboard/FarmManager.vue; dashboard/PlotPlanner.vue; RecommendationsPage.vue; CompatibilityPage.vue; dashboard/farmer/InsurancePage.vue; dashboard/farmer/CreditScorePage.vue                                                                                                                                                                                                 |
| Task 11 | dashboard/shared/ChatPage.vue; dashboard/shared/ForumPage.vue; dashboard/shared/ThreadPage.vue; dashboard/shared/ProfilePage.vue                                                                                                                                                                                                                                               |
| Task 12 | public/HomePage.vue; public/AboutPage.vue; public/ContactPage.vue; public/CheckoutSuccessPage.vue; public/CheckoutCancelPage.vue; auth/LoginPage.vue; auth/RegisterPage.vue; auth/ForgotPasswordPage.vue; auth/ResetPasswordPage.vue; auth/EmailVerificationCallback.vue; auth/VerifyEmailPage.vue; error/ForbiddenPage.vue; error/NotFoundPage.vue; error/ServerErrorPage.vue |

## Execution evidence

Implementation used native execution in this chat with one fresh independent whole-branch review. All 13 tasks are committed on `codex/modern-organic-redesign` in the managed worktree. Final product revision: `4f87cdf`; later changes only record verification/documentation. Supported Node 24.19.0 locally and Node 24.20.0 in the pinned Linux browser container.

| Final check | Result |
| --- | --- |
| ESLint/Oxlint, source Prettier, Playwright/config Prettier | Passed |
| Full Vitest | 89 files / 386 tests passed |
| Production build | Passed |
| Linux Chromium desktop/mobile full suite | 104 passed; no unexpected API calls/page errors |
| Reviewed visual baselines | All 20 real-font images replayed without updates |
| Firefox/WebKit shared/auth smoke before review fixes | 11 passed in each engine |
| Final Firefox/WebKit checkout, pending/nested dialogs, both-role mobile profile | 5 passed in each engine |
| Full backend suite | 541 passed / 3297 assertions; 4 unchanged Debug* tests risky because they contain no assertions |
| Full Pint and PHPStan level 6 | Passed |
| Catalog/forum/login server boundary checks | Passed |

Real OSM map tiles: 20/20 HTTP 200 during route-family QA; overlay/popup legibility inspected. Public/dashboard layouts checked at 360/768/1440, 200% text, Tagalog labels and reduced motion. These isolated checks do not claim a GitHub Actions or deployment run.

The final independent reviewer found four Important issues and no Critical/Minor issues; Declined to judge was empty. One fix pass verified each RED→GREEN: cent-valid bounded cash suggestions, checkout errors inside the active native dialog, message error/retry before empty states, and authenticated own-profile links in mobile navigation for both roles. Focused checks passed 11 unit and 6 Chromium desktop/mobile cases before the fresh full suites above. No second reviewer was dispatched.

Field & Linen is implemented with centralized tokens, self-hosted Inter/Lora, Atomic Design primitives, page/composable mutation ownership, and native dialog focus/scroll behavior. AGENTS.md and the local design skill are synchronized. The four legacy risky backend tests remain unchanged; no other minors were deferred. The branch and worktree remain available for the user’s integration choice.
