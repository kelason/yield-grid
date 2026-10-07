# YieldGrid Modern Minimalist Organic Redesign

**Status:** Approved and implemented on `codex/modern-organic-redesign`, verified on 2026-10-07; integration into `develop` is pending.
**Requested:** Analyze Atomic Design, modernize the organic visual language, change the palette, consolidate reusable buttons/modals/inputs/cards/loading, and update Playwright for the overall redesign.
**Rollout:** Shared foundations → farmer and buyer dashboards → remaining frontend.
**Implementation plan:** [Task-by-task plan](../plans/2026-10-07-modern-organic-redesign.md).

## 1. Direction and scope

Create a calm, practical agricultural workspace for Filipino farmers and buyers. Prioritize clear actions, readable data, responsive navigation, and trustworthy transaction states. Keep organic warmth through Lora headings, Inter body text, muted natural colors, rounded surfaces, and restrained motion.

The approved direction is **Field & Linen**. A light neutral canvas carries the information; sage identifies primary actions; wheat highlights financial context; teal identifies advisory information. Use dark olive charcoal for structural navigation. Decorative gradients, translucent layers, emoji UI icons, and glows currently compete with content; remove them from ordinary panels while keeping a subtle primary-button gradient and a single organic divider where public sections meet.

Alternatives considered: a brighter agricultural palette retains the current busy appearance; an all-neutral dashboard weakens YieldGrid's organic identity. Field & Linen provides a visible redesign while retaining the established token names and familiar navigation.

Scope includes all 35 existing page SFCs and the shared components they use. Dashboards are the first completed vertical slice. Preserve routes, roles, API payloads, payment calculations, input limits, confirmation requirements, and verification guards. Do not add fabricated charts, trends, yields, weather, or activity data. A frontend error or accessibility fix required by a migrated component is in scope; new backend capabilities are a separate task.

## 2. Frontend audit

Inspection on 2026-10-07 at `9cd0a37` found:

| Layer     | SFC count | Assessment                                                                                 |
| --------- | --------: | ------------------------------------------------------------------------------------------ |
| Atoms     |        21 | Useful existing controls; reuse these APIs rather than install a UI library.               |
| Molecules |        34 | Good compositions, but some perform organism-sized work.                                   |
| Organisms |        31 | Existing domain sections; extract responsibilities from large components during migration. |
| Templates |         3 | Public, auth, dashboard shells already exist.                                              |
| Pages     |        35 | Route composition exists; several pages also own large forms, dialogs, and orchestration.  |

There are 124 Vue SFCs, including 24 with at least 200 lines. Size is a review signal, not a reason to split static markup arbitrarily. Existing reuse: `AppButton` 88 instances across 39 files; `AppCard` 43/22; `FormField` 38/15; `ConfirmModal` 17/17; `SkeletonCard` 10/7; `EmptyState` 12/8. Raw tags remain: 63 buttons, 23 inputs, 4 selects, 9 textareas. These counts include primitive implementations and native checkbox/radio controls; they are not all violations.

### Concrete findings

| Finding                                        | Evidence                                                                                                                               | Consequence and proposed resolution                                                                                                                                                     |
| ---------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Two independent modal shells                   | `components/molecules/AppModal.vue:13`, `ConfirmModal.vue:2`                                                                           | Both implement overlay, panel, transitions; neither provides a complete dialog/focus contract. Make ConfirmModal compose AppModal and AppButton.                                        |
| Additional page-owned overlays                 | `pages/public/MarketplacePage.vue:153`, `pages/RecommendationsPage.vue:240`, `pages/dashboard/farmer/CashPaymentApprovalsPage.vue:276` | Migrate all to the common modal shell with domain content in organisms.                                                                                                                 |
| Input attributes stop at a wrapper             | `components/molecules/FormField.vue:27`                                                                                                | Undeclared `disabled`, `step`, `autocomplete`, input events, and ARIA attributes may land on its div. Forward native attributes to the control while preserving wrapper layout classes. |
| Search/sort labels are not associated          | `molecules/SearchInput.vue:23`, `SortSelect.vue:18`, `ContractFilter.vue:99`                                                           | Compose controls with stable IDs and actual labels; placeholders are supplementary.                                                                                                     |
| Molecule imports an organism                   | `molecules/AddressFields.vue:5` imports `organisms/LeafletPinPicker.vue`                                                               | Move the intact address composition and its tests to organisms and update all imports. Preserve its props/events and geographic cascade.                                                |
| Clickable card is a div                        | `molecules/DemandCard.vue:37`                                                                                                          | Use an explicit keyboard-operable link/button for opening details. Do not make every AppCard interactive.                                                                               |
| Navigation unavailable at small widths         | `organisms/AppSidebar.vue` hides below md; `templates/DashboardLayout.vue` mobile header only exposes brand/logout                     | Add a mobile navigation drawer using the same role-filtered navigation model. Preserve route access rules and unread indicators.                                                        |
| Decorative treatment and emoji vary            | `AppLogo.vue`, dashboard pages, `FarmCard.vue`, public/auth shells                                                                     | Use existing Heroicons and calm shared surfaces. Preserve emoji in user-authored content.                                                                                               |
| Palette also exists outside Tailwind           | `CropConfidenceMeter.vue`, `CreditScoreGauge.vue`, `ScoreHistoryTimeline.vue`, `constants/creditScoring.js`, map organisms             | One small shared palette module serves Tailwind and JS renderers. Review map/chart semantics separately from brand color.                                                               |
| Placeholder farmer panel                       | `pages/dashboard/FarmerDashboard.vue`                                                                                                  | Replace “Activity feed coming soon” with farm summaries from the existing farms response.                                                                                               |
| Existing accessibility support worth retaining | `assets/main.css:10,36`                                                                                                                | Global focus-visible and reduced-motion rules already exist; extend them and verify actual controls.                                                                                    |

Largest components include PlotDrawer (632 lines), RecommendationsPage (543), ProfilePage and MyDemandsPage (465 each), PriceGuideHint (437), AppSidebar (407). Extract page orchestration into focused `use*.js` composables and domain sections into organisms when those areas are migrated. Preserve existing state boundaries and events; do not introduce generic form, page, or workflow engines.

## 3. Field & Linen palette

Keep `moss`, `harvest`, `soil`, `dew`, and `stone` names so existing consumers continue to resolve. These approved replacement values are implemented on `codex/modern-organic-redesign`. Store them once in `frontend/src/constants/designTokens.js`, exporting `DESIGN_COLORS` and `DESIGN_SHADOWS`; import the colors into Tailwind and chart/map code. Hex values belong in that module, never Vue templates.

| Shade | moss: sage | harvest: wheat | soil: olive earth | dew: mist teal | stone: linen |
| ----- | ---------- | -------------- | ----------------- | -------------- | ------------ |
| 50    | #F3F6F1    | #F8F3E8        | #F5F5F0           | #EEF5F4        | #FAFAF7      |
| 100   | #E4ECE0    | #EFE4CB        | #E7E9DF           | #DCEBE8        | #F3F2EC      |
| 200   | #CDDCC7    | #E0CEA5        | #D4D8C8           | #BDD8D3        | #E4E4DA      |
| 300   | #ABC2A5    | #CCB47E        | #B7BEA8           | #94BDB7        | #CECEC2      |
| 400   | #7E9B7B    | #B29757        | #949F83           | #6E9D98        | #A0A396      |
| 500   | #55715C    | #967733        | #747E63           | #537F7C        | #6B7364      |
| 600   | #435E4B    | #85672D        | #5E684F           | #426B6A        | #596153      |
| 700   | #344D3D    | #765C29        | #4B5541           | #3D6265        | #424B3D      |
| 800   | #2B3F32    | #5B4725        | #3D4337           | #304B4E        | #2F382B      |
| 900   | #223329    | #40331F        | #2D3129           | #25393C        | #20291D      |

| Role                         | Application                                                              |
| ---------------------------- | ------------------------------------------------------------------------ |
| Dashboard canvas             | stone-50                                                                 |
| Public canvas / inset areas  | stone-100                                                                |
| Card / input surface         | white                                                                    |
| Main text                    | stone-900                                                                |
| Secondary text               | stone-600                                                                |
| Muted small text             | stone-500 on white/stone-50; use stone-600 on tinted surfaces            |
| Card dividers                | stone-200; decorative, not the only control boundary                     |
| Input boundary               | stone-500, 1px; focus ring moss-500, 2px with 2px offset                 |
| Primary action               | white on subtle moss-500 → moss-600; hover moss-600 → moss-700           |
| Secondary action             | moss-700 on moss-50 with moss-200 outline                                |
| Navigation shell             | subtle soil-900 → soil-800; stone-100 text, stone-300 secondary text     |
| Navigation selected          | moss-100 surface and moss-800 text; selected marker + aria-current       |
| Financial accent             | harvest-700 on harvest-50; do not color every monetary number            |
| Advisory / AI                | dew-700 on dew-50                                                        |
| Errors / destructive actions | existing red semantics, red-600 button with red-700 hover and white text |

Calculated sRGB contrast: white/moss-500 **5.38:1**, white/moss-600 **7.14:1**, stone-900/stone-50 **14.38:1**, stone-600/stone-50 **6.16:1**, stone-500/stone-50 **4.71:1**, harvest-700/harvest-50 **5.69:1**, dew-700/dew-50 **6.06:1**. The current white/moss-500 pair is approximately **4.10:1**, so the new normal-size primary label improves readability. These calculations verify only these pairs; tinted, translucent, hovered, chart, and disabled states still need browser inspection.

## 4. Shape, type, spacing, motion

- Retain Lora for display/section headings and Inter for controls/body. Use body weight 400 for readability; reserve 300 for large supporting marketing copy.
- Page h1: 32px/40px on mobile, 40px/48px at sm and above, bold, tight tracking. Public hero may reach 48px/56px. Section h2: 24px/32px. Body and inputs: 16px/24px; compact labels: 14px/20px. Avoid tiny navigation labels.
- Keep radii: controls 12px (`rounded-xl`), cards 16px (`rounded-2xl`), dialogs 24px (`rounded-3xl`), badges/search pills fully rounded.
- Use a 4px spacing rhythm: 16px mobile gutters, 24px tablet gutters, 32px desktop gutters; 24–32px section gaps; max content width `max-w-7xl` (1280px).
- Set `soft` to `0 2px 6px rgba(32,41,29,0.04)`, `organic` to `0 12px 32px rgba(32,41,29,0.10)`, `harvest-glow` to `0 4px 14px rgba(150,119,51,0.10)`. Keep token compatibility, but use glow only for an intentional highlighted action.
- Interactive feedback: color/opacity first, subtle existing button scale at most 1.02, 200–300ms. Static cards do not lift. All animation respects reduced motion. Dialog opening uses the existing soft fade/scale language; closing must never delay restoring usable focus.
- Shared buttons and icon controls target at least 44×44 CSS px. Keep navigation and form layout usable at 360px wide and with text enlarged to 200%.
- Preserve a restrained organic divider on public section boundaries. Remove ornamental accent strips, decorative blobs, and repetitive gradients from operational screens.

These decisions explicitly update the old bright palette, oversized dashboard h1 recommendation, and mandatory decorative treatments in the project design documentation. Atomic Design, organic radii, accessibility, validation, and mutation confirmation requirements continue to apply. `AGENTS.md` and `.agents/skills/yieldgrid-design-system/SKILL.md` now record the approved direction and shared component/verification contracts. Keep both synchronized for future changes. That local skill remains ignored by Git; the tracked spec carries the design contract.

## 5. Atomic Design and reusable interfaces

**Dependency direction:** pages → templates/organisms/molecules/atoms; templates → organisms/molecules/atoms; organisms → molecules/atoms; molecules → atoms; atoms must not import higher layers. Same-level composition is acceptable when it prevents duplication (ConfirmModal → AppModal). Router and Pinia integration stays out of purely presentational atoms. A native checkbox, radio, file input, or progress element does not need a new abstraction without repeated styling/behavior.

### Existing primitives to keep and improve

| Component                                     | Contract                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| --------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `atoms/AppButton.vue`                         | Keep `variant`, `size`, `type`, `loading`, `disabled`, `rounded`, default slot, native event/attribute fallthrough. Add `icon` slot (already assumed by EmptyState). Loading disables activation and sets aria-busy without erasing the accessible name. Icon-only callers supply aria-label. Do not put confirmation or API calls inside the button.                                                                                                                                                     |
| `atoms/AppCard.vue`                           | Keep `padding`, `variant`, `hover`, default slot and default div root. Retain all variants while simplifying their treatment. Callers use actual links/buttons for actions; no automatic click handler or universal card navigation API.                                                                                                                                                                                                                                                                  |
| `atoms/AppInput.vue`                          | Preserve id, type, v-model string/number input, min/max/maxlength and numeric digit clamping. Forward native attributes/events. Reflect `error` via aria-invalid. Do not coerce money or trim password values.                                                                                                                                                                                                                                                                                            |
| `atoms/AppSelect.vue`                         | Preserve current label/error/options/default-option-slot and string-emitting behavior during rollout. Make IDs, errors, hint references, disabled state and native attribute forwarding correct. Its label wrapper is a pragmatic compatibility exception, not a reason to break every caller.                                                                                                                                                                                                            |
| `molecules/FormField.vue`                     | Keep existing props/events and `labelSuffix` slot. Add disabled, hint and `multiline` (default false). Use AppInput normally and AppTextarea for multiline. Class/style/data-test wrapper placement remains deliberate; input attrs and listeners go to the control, error/hint/counter IDs compose into aria-describedby.                                                                                                                                                                                |
| `molecules/SearchInput.vue`, `SortSelect.vue` | Keep v-model/options/placeholder APIs. Add id and label (defaults “Search”/“Sort by”) with accessible hidden-label option; compose AppInput/AppSelect. Keep pill search geometry.                                                                                                                                                                                                                                                                                                                         |
| `molecules/AppModal.vue`                      | Preserve required `isOpen`, default content slot, and `close` event. Add `title` (string, default empty), `labelledby`/`describedby` (optional IDs), `size` (`sm`/`md`/`lg`, default `lg` = existing max-w-2xl), `placement` (`center`/`left`, default `center`, with left used by mobile navigation), and `busy` (default false). Optional footer slot. Every caller provides title or labelledby. Implement with native dialog showModal/close, styled backdrop, and close button with accessible name. |
| `molecules/ConfirmModal.vue`                  | Preserve isOpen/title/message/confirmText/cancelText/type/loading, confirm/cancel events. Compose AppModal + AppButton; default focus to Cancel. While loading, prevent confirm, cancel, Escape, backdrop and close-button dismissal. One page-level instance.                                                                                                                                                                                                                                            |
| `composables/useConfirmModal.js`              | Preserve returned isOpen/isExecuting/config/confirm/execute/cancel. Clear per-action defaults when opening a new confirmation so previous labels do not leak. Guard repeated execution. Wire isExecuting to ConfirmModal loading at every caller.                                                                                                                                                                                                                                                         |
| `atoms/SkeletonCard.vue`                      | Keep withAvatar/withAction props and import path; compose AppCard/AppSkeleton. Although composite, retaining this compatibility wrapper avoids a cosmetic path-only migration.                                                                                                                                                                                                                                                                                                                            |
| `molecules/EmptyState.vue`                    | Keep title/description/actionLabel/action event and icon/actionIcon slots; compose AppCard and AppButton. Optional action slot permits RouterLink without nested interactive controls.                                                                                                                                                                                                                                                                                                                    |

### Small additions with known consumers

| File                            | Interface and reason                                                                                                                                                                                                                                                        |
| ------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `atoms/AppTextarea.vue`         | id required; modelValue string default ''; maxlength/minlength, rows default 4, required/disabled/error; update:modelValue string. Forward native attrs. FormField owns label/counter. Replaces nine custom textarea implementations while retaining each feature's limits. |
| `atoms/AppSpinner.vue`          | size sm/md/lg default sm; decorative SVG with aria-hidden. Parent owns the status text. Used in buttons, dialogs and loading regions.                                                                                                                                       |
| `atoms/AppSkeleton.vue`         | shape line/block/circle default line; size/layout via classes; decorative aria-hidden placeholder with reduced-motion support.                                                                                                                                              |
| `molecules/LoadingState.vue`    | label string default “Loading…”; one status region and default slot for skeleton composition; default content AppSpinner. The data container owns aria-busy.                                                                                                                |
| `molecules/StatCard.vue`        | label required; value string/number/null; loading bool default false; tone default/harvest default default; icon slot. Uses AppCard and AppSkeleton; null displays an em dash, never invented zero. Both dashboards consume it.                                             |
| `molecules/PageHeader.vue`      | title required, description optional, headingLevel 1/2 default 1; actions slot. Used by pages inside the shell, with only one page h1.                                                                                                                                      |
| `molecules/SidebarNavGroup.vue` | item, collapsed bool, open bool; toggle event. Reuses SidebarNavLink. Navigation data/filtering lives in useDashboardNavigation, shared by desktop and mobile.                                                                                                              |

Native dialog supplies modality and makes the rest of the document inert, avoiding a home-grown focus-trap library. Keep open state controlled by the parent; handle cancel/backdrop via the existing close event, guard redundant showModal calls, and restore focus to the trigger or a logical fallback if it unmounted. Test nested form + confirmation dialogs, body-scroll locking, route unmount cleanup and Leaflet inside a modal in real browsers. Do not rely on newer closedby/invoker APIs. [Native dialog reference](https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/dialog)

## 6. Dashboard and shell composition

Desktop: dark olive sidebar, light compact utility header, linen content canvas. Mobile: named menu button, role-aware navigation drawer, current page title and accessible profile/logout actions. Shared navigation maintains existing restricted links, current-route expansion and chat badge behavior. Public header receives its own usable mobile menu. Logout follows the same confirmation convention as other mutations.

Farmer overview: PageHeader (“Farm overview”), primary navigation to My Farms; compact farm/plot StatCards; an existing-data farm summary with name/location/plot count; an AI Advisor action linking to Recommendations. Explicit loading, empty and retryable error states replace the placeholder activity panel. Do not sum area across unclear units or fabricate a recent-activity timeline.

Buyer overview: PageHeader (“Buyer overview”), Browse Marketplace action; purchase count, completed-payment purchase count, amount paid; recent PurchaseCards with existing cancellation confirmation. Label the completed-payment count accurately rather than implying fulfillment status. `/buyer/purchases` is paginated (`stores/marketStore.js:217`, backend `PurchaseController.php:331`), so label the summaries “Purchases on this page”, “Paid purchases on this page”, and “Amount paid on this page”. Use existing pagination controls to keep the displayed scope explicit; do not infer lifetime payment totals from one page or fetch all records just for KPI cards.

Reuse existing FarmCard/PurchaseCard where their contracts fit. Page-level fetching/errors may stay in small page scripts; only extract a composable when orchestration or line limits justify it. Do not create a generic dashboard store.

## 7. Site migration boundaries

1. Shared primitives and shells establish the new language for every existing consumer.
2. Farmer and buyer dashboards prove the full composition and state model.
3. Marketplace, checkout, contracts, demands, offers, cash approvals and addresses: preserve financial payloads, validation and confirmations. Extract checkout/offer/address panels into organisms.
4. Farming, recommendations, compatibility, insurance and credit: preserve map interactions, null safety, measured values and localized copy. Chart/map colors use the shared palette while retaining distinct semantic series/geometry styles.
5. Community/chat/profile: preserve unread/realtime behavior, text limits and form drafts; restyle bubbles, lists and composers through the primitives.
6. Public/auth/error pages: simplify hero/sections, keep restrained organic dividers and clear CTA hierarchy, preserve guest/protected route behavior, SEO metadata and auth validation.

Finish each batch with relevant unit/browser checks. Review inherited token effects site-wide even before a route's detailed layout is migrated. Avoid introducing a parallel legacy theme or a new component library.

## 8. Playwright audit and target

Observed: `playwright test --list --reporter=list` succeeds and discovers **3 tests / 2 files**, Desktop Chromium only. Authentication test is a landing-to-login navigation smoke. Insurance tests cover unauthenticated redirect and mocked RSBSA save/locale switching. There are no committed visual baselines. There are 70 Vitest spec files, but no dedicated dashboard or modal specs. No browser behavior was executed during this planning audit.

Fix before recording baselines:

- `/api/v1/user` fixture currently returns `{ data: farmer }`; `stores/auth.js` and the backend expect an unwrapped user. Correct it and assert role/verification in the rendered UI.
- A verified dashboard loads `/chat/conversations` and creates a Reverb connection. Mock bootstrap endpoints and WebSocket traffic before navigation. Unexpected API requests must fail the test instead of reaching a live backend.
- Replace wrapper-dependent field selectors and `.last()` confirmation selection with labels and named dialogs. Assert mutation payload, response and resulting UI, not just outgoing requests.
- Use an isolated Vite test port, deterministic fixtures/date/locale/timezone, and controlled fonts. Pin supported Node and the locked Playwright browser version. Current host Node 24.4.1 is outside package engines; CI uses 22.18.0.

PR matrix: Chromium 1440×900 and mobile Chromium 390×844. Verify both roles, populated/empty/loading/error, keyboard navigation, confirmations, and representative English/Tagalog. Narrow 360px, tablet 768px, 200% enlarged text and reduced motion are additional representative layout cases. Firefox and WebKit run targeted behavior smoke before rollout, with optional explicit CI invocation; avoid tripling all screenshots.

Visual baselines: selected desktop/mobile dashboards, a loading/empty state, standard/destructive dialog, input error form, marketplace and public home. Use Chromium/Linux as the single baseline environment; commit reviewed baselines alongside the intentional UI change. Freeze API data and date, wait for fonts, and disable animation only for snapshots. Keep reduced-motion behavior as a separate test. External fonts must be served from checked-in licensed test assets or blocked with an explicit consistent fallback; a timeout or `document.fonts.ready` alone is not a deterministic font policy. Choose fallback fonts initially, verify real Lora/Inter in manual visual QA.

Playwright notes that screenshot rendering varies by OS/browser; generate and compare in the same environment. [Visual comparison documentation](https://playwright.dev/docs/test-snapshots)

## 9. Baseline verification and acceptance

Planning audit results: ESLint `eslint . --no-cache` **passed**; Prettier `--check 'src/**/*.{vue,js,ts}'` **passed**; Playwright discovery **passed**. Vitest, build and browser execution were not run. Current host Node does not meet the package engine range, so implementation verification must use a supported runtime.

Completion requires reusable primitive behavior verified, every route visually reviewed, no loss of roles/limits/confirmation, relevant regression tests passing, mobile navigation working, named dialogs and usable focus, no unintended horizontal overflow, and intentional screenshot differences reviewed. Test null data during transitions, slow/error responses, long Tagalog/English labels, long currency amounts and repeated clicks.

No new runtime UI, animation or form library is required. Native controls, existing Vue/Tailwind/Heroicons, existing Pinia stores, Vitest and Playwright cover the work. UI UX Pro Max supplied useful minimal/organic guidance; its generic Swiss sharp-corner and form-library recommendations do not override this project's organic shapes or existing form architecture.

The original `.gitignore` excluded `docs/superpowers`, `AGENTS.md`, and `.agents`. Narrow exceptions now track `AGENTS.md` and this exact spec/plan; `.agents` remains local. The tracked specification carries the design contract. Implementation is isolated on `codex/modern-organic-redesign`; no merge, push or deployment has occurred.

Final independent review found four Important gaps, all corrected through failing-then-passing regressions: valid cent-rounded cash suggestions, checkout errors inside the active dialog, message-load error/retry states, and own-profile access in mobile navigation for both roles. No Critical or Minor findings were reported, and no behaviors were declined for judgment. Full verification results are recorded in the matching plan.
