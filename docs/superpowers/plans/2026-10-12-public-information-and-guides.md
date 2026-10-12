# YieldGrid Public Information and User Guides Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rework Home around YieldGrid's purpose and vision, move its full capability inventory to Features, and provide practical How It Works and Documentation pages for Filipino farmers and buyers.

**Architecture:** Extend the existing Vue public layout with three public destinations and reusable documentation topic routes. Render trusted, localized content through focused Atomic Design components; use the existing router, locale, auth, and design primitives. Preserve existing business behavior and keep reading navigation confined to public pages.

**Tech Stack:** Vue 3 Composition API, Vue Router, Vue I18n, Pinia, Tailwind, Heroicons, installed `@unhead/vue`, Vite, Vitest, Playwright. No new runtime dependency, backend endpoint, migration, CMS, or search service.

**Spec:** [Approved public information and user guides design](../specs/2026-10-12-public-information-and-guides-design.md), approved 2026-10-12. Read it alongside this plan for the exact journey and article requirements.

## Global Constraints

- Preserve the Field & Linen identity, current business workflows, and English/Tagalog/Cebuano support.
- Preserve centralized `moss`, `harvest`, `soil`, `dew`, and `stone` tokens and current shadows. No palette replacement or new design framework.
- Use self-hosted Lora headings and Inter body text; one h1 per page/article; orderly h2/h3 hierarchy. Keep existing 16px body/control typography, relaxed line-height, 44px targets, visible focus, and reduced-motion behavior.
- Keep 16px/24px/32px page gutters across mobile/tablet/desktop, existing `max-w-7xl`, warm backgrounds, rounded shared surfaces, and subtle elevation. Static cards remain still.
- Keep one header row at 360–390px. Check 360, 390, 768, 1024, 1280, and 1440px; 100%/200% text; long translations; short-height menus; reduced motion.
- New learning pages are readable without login. Preserve authenticated Home redirects and role/email-verification guards. Reading content never creates or changes domain records.
- Retain all eight feature areas and About's existing summary; move only Home's full feature inventory. Preserve the existing landscape asset, responsive preload, mobile background behavior, scrim, and white wave.
- Preserve the separate external API Docs destination and its new-tab protections. User help uses `/documentation`; API documentation remains at the configured API host's `/docs`.
- No invented metrics, customer quotes, government affiliation, provider coverage, guaranteed yields/income/sales, loan acceptance, or insurance decisions. Cite problem context; describe app behavior from current code.
- Follow Atomic Design, `<script setup>`, existing control/modal contracts, nullable-prop rules, named constants, and ≤30-line functions. Split mixed-responsibility components near 200+ lines; do not split every paragraph into a component.
- Content uses Vue interpolation and existing locale catalogs, without `v-html`, runtime Markdown, arbitrary component names, or a generic block engine.
- Use production-build previews, synthetic data, real self-hosted fonts, and Linux-installed dependencies for pixels. Baselines use Ubuntu 24.04 / Playwright 1.63.0 Noble; inspect every diff, replay without updates, then run the full browser suite before committing changed baseline sets.
- Use Node `^22.18.0` or `>=24.12.0`. Existing `frontend/README.md` owns environment commands and the pinned container digest.

## Review Focus

1. Direct hashes, repeated article history entries, and rapid Back/Forward must restore the intended reading location without scrolling a dashboard or letting stale work steal focus. Task 4.
2. A buyer reading farmer help, an unverified member, and a guest must still read every guide while protected action links remain appropriate to their role/session. Tasks 1–3 and 5.
3. A locale switch inside an open mobile dialog must update article/title/language without moving focus into inert content; long labels and API Docs must still fit the header/menu. Tasks 1, 4, and 8.
4. A manual seller without a farm, a pending cash purchase, and an accepted-but-unpaid offer must receive accurate instructions; screenshots must not imply extra prerequisites or successful payment. Tasks 2, 3, and 5.
5. Missing tutorial images and unknown topic/hash URLs must leave usable text and recovery links; moving Home content must not silently drop About/features coverage. Tasks 1, 4, 5, 6, and 7.

---

## Execution state, ownership, and sequence

This is a planning deliverable. No product implementation or application verification has been performed for it. The release notes selected in the IDE contain older failures and are not a current test report.

Planning began at `fb6c1a9`; the API Docs footer was committed as `8b57151`, followed by the design at `77bb77c`. At plan preparation, local `AppNavbar.vue`, its test, all three locale catalogs, and the locale test add API Docs to the navbar. Preserve those changes. Run `git status --short` and inspect current diffs again before execution; concurrent work may have advanced since this record.

Use an isolated execution worktree with the approved spec/plan and relevant latest changes available. Do not create an execution checkout that silently loses the API Docs work, stash another session's changes, or stage unrelated work. Planning documents are ignored by the repository's narrow allowlist; use exact-path `git add -f` for these two documents rather than broad ignore changes.

Sequence: **1 documentation foundation → 2 farmer articles → 3 buyer/shared articles → 4 reading navigation → 5 tutorials → 6 Features → 7 Home → 8 shared navigation → 9 acceptance**.

One owner maintains the guide schema, locale namespace, router, and public shell. Tasks 2 and 3 have separable content, but share catalogs/manifests; native sequential execution avoids merge overhead. If delegated, assign nonoverlapping guide entries and integrate through the shared owner. Tasks 5–7 depend on completed guide targets. Do not publish intermediate commits with broken destinations or stale existing snapshots.

For this plan, **native execution plus one final independent whole-diff review** is recommended: most work shares content and navigation contracts, and business APIs remain unchanged. The user must review this plan and select an execution method before product implementation.

## File and interface map

All paths below are relative to the repository root. New component files remain in Atomic Design directories.

| Files | Responsibility |
| --- | --- |
| `frontend/src/constants/userGuides.js` | Stable topic/section IDs, audience metadata, locale-key references, related-topic/action destinations; trusted fixed schema |
| `frontend/src/locales/guides/en.js`, `tl.js`, `ceb.js` | Detailed guide text, imported under `guides` in existing root catalogs |
| `frontend/src/locales/en.js`, `tl.js`, `ceb.js` | Existing catalog assembly; Home/Features/tutorial/nav copy; retain concurrent API Docs keys |
| `frontend/src/pages/public/DocumentationPage.vue` | Guide index, known article, or unknown-topic recovery; route-derived selection and metadata |
| `frontend/src/components/organisms/DocumentationNavigation.vue` | Topic groups/current topic, desktop sidebar and mobile native contents disclosure |
| `frontend/src/components/organisms/DocumentationArticle.vue` | Fixed readable article structure and related guides; no API calls or mutations |
| `frontend/src/composables/usePublicPageHead.js` | Reactive title, description, and OG text shared by four public destinations |
| `frontend/src/App.vue` | Persistent reactive document language using the existing active locale and Unhead |
| `frontend/src/composables/usePublicReadingNavigation.js` | Public window scroll/focus/history coordination and teardown |
| `frontend/src/components/templates/PublicLayout.vue` | Reading readiness integration with actual page rendering and transition events |
| `frontend/src/constants/tutorialJourneys.js` | Four fixed journeys referencing known guide topics, exact UI-label keys, and screenshot metadata |
| `frontend/src/components/organisms/TutorialJourney.vue`, `frontend/src/pages/public/HowItWorksPage.vue` | Ordered step presentation, text outcomes, farmer/buyer anchors, and screenshot figures |
| `frontend/public/images/guides/*-en.png` | Four reviewed, reproducible synthetic app screenshots |
| `frontend/src/pages/public/FeaturesPage.vue`, `frontend/src/constants/platformFeatures.js` | Three capability groups with all eight existing features and guide destinations |
| `frontend/src/pages/public/HomePage.vue`, `frontend/src/components/organisms/HeroSection.vue` | Page composition versus landscape opening; reactive Home metadata/preloads |
| `frontend/src/components/organisms/HomePurposeSection.vue`, `HomeVisionSection.vue` | What YieldGrid is/sourced needs; founder vision and next steps |
| `frontend/src/components/organisms/AppNavbar.vue`, `AppFooter.vue`, `frontend/src/router/index.js` | New links/routes, public reading metadata, preserved external API reference and guards |
| `frontend/src/components/molecules/NavLink.vue` | Existing link semantics plus optional explicit active state for Documentation topic routes |
| Existing and new tests listed per task | Behavior, content-link integrity, locale compilation, responsiveness, and reviewed pixels |

### Fixed guide schema

Export `USER_GUIDES: Guide[]`, `GUIDE_GROUPS: { id, titleKey }[]`, and `getGuide(topic: string): Guide | undefined` from `constants/userGuides.js`. Use named exports for audience identifiers (`both`, `farmer`, `buyer`) and reuse `USER_ROLES` for actual protected actions. The guide audience is descriptive, never an access restriction.

`Guide` fields: `id`, `groupId`, `audience`, `titleKey`, `summaryKey`, `prerequisiteKeys: string[]`, `sections: GuideSection[]`, `relatedTopics: string[]`, and optional `action: { role, to, labelKey }`. An action's role is a single existing `USER_ROLES` value and `to` is an existing route location without an invented record ID; omit the action where no useful role-specific destination exists. All article content remains readable to every role.

`GuideSection` fields: `id`, `titleKey`, `introKey`, `steps: { id, instructionKey, resultKey }[]`, `noteKeys: string[]`, and `recoveryKeys: string[]`. Empty lists are permitted when that category genuinely does not apply. No HTML-valued fields or arbitrary block types. Related links use the known registry; workflow actions use existing router targets. Static prose and control labels remain translation keys.

`DocumentationArticle` accepts required `guide: Guide`; its page renders it only after successful lookup. `DocumentationNavigation` accepts `guides: Guide[]` and `activeTopic: string | null`; it emits no domain events. Documentation root and topic pages use separate named routes, `documentation` and `documentation-topic`, with the latter's param named `topic`.

Group order is `start`, `grow`, `trade`, `support`. Render only implemented guides during intermediate tasks; all 13 must exist before acceptance. Never publish blank placeholder articles.

### Article inventory and stable section IDs

Titles are localized; IDs below are fixed across languages. The approved spec owns the complete prose requirements.

| Topic | Group / owner | Required section IDs |
| --- | --- | --- |
| `getting-started` | start / Task 1 | `choose-role`, `register`, `verify-email`, `sign-in-and-recover`, `next-steps` |
| `profile-addresses-language` | start / Task 1 | `open-profile`, `saved-addresses`, `change-language` |
| `troubleshooting-and-support` | support / Task 1, expanded Tasks 2–3 | `account-access`, `farm-and-analysis`, `trading-and-payments`, `messages-and-downloads`, `contact-support` |
| `farms-and-plots` | grow / Task 2 | `create-farm`, `draw-a-plot`, `save-a-plot`, `map-problems` |
| `crop-advisor` | grow / Task 2 | `request-analysis`, `read-results`, `publish-a-contract`, `compare-crops` |
| `selling-harvests` | trade / Task 2 | `manual-listing`, `optional-source`, `review-and-publish`, `manage-listings` |
| `verification-and-trust-score` | support / Task 2 | `email-and-farm-verification`, `read-your-score`, `download-report` |
| `insurance-and-rsbsa` | support / Task 2 | `rsbsa-records`, `prepare-enrollment`, `download-and-submit`, `policy-and-claims` |
| `buying-harvests` | trade / Task 3 | `browse-and-filter`, `inspect-listing`, `review-checkout`, `track-purchase` |
| `payments-and-purchases` | trade / Task 3 | `payment-options`, `deposits-and-totals`, `cash-confirmation`, `statuses-and-cancellation` |
| `demands-and-offers` | trade / Task 3 | `post-demand`, `submit-offer`, `decide-offers`, `pay-and-deliver`, `complete-and-settle` |
| `price-guide` | trade / Task 3 | `open-price-guide`, `sources-and-freshness`, `unavailable-prices` |
| `community-and-messaging` | support / Task 3 | `chat-eligibility`, `use-messages`, `forum-participation`, `report-content` |

### Verification conventions for every task

Commands run from `frontend` unless stated otherwise. Use `npm run lint:check` (non-mutating), `npm run format:check`, and `npx prettier --check "e2e/**/*.js" playwright.config.js vite.config.js` on relevant changes. Component mounts install real i18n and router plugins, plus Pinia/head where used; restore locale and unmount after each test.

For behavioral changes, write the named regression first, run it to observe the expected failure, implement, then rerun. Pure prose needs factual/editorial review and catalog/link checks rather than redundant markup tests. Commit steps stage exact owned paths only; never `git add .` in this workspace.

When a task changes existing visual output, add/adjust the relevant visual test, regenerate its affected Linux images in the pinned environment, inspect every image/diff, replay without updates, and run the full browser suite before that task's commit. New route baselines can be recorded together in Task 9; do not accept missing expected baseline files as a passing check.

## Task 1: Deliver the documentation reader and first useful guides

**Files:** Create the guide registry, three `locales/guides/*.js` files, Documentation page/navigation/article, and `usePublicPageHead.js` from the file map. Modify root locale catalogs, `App.vue`, and `router/index.js`. Create `frontend/src/constants/__tests__/userGuides.spec.js`, `frontend/src/pages/public/__tests__/DocumentationPage.spec.js`, `frontend/src/composables/__tests__/usePublicPageHead.spec.js`, `frontend/src/__tests__/AppLanguage.spec.js`, and `frontend/e2e/Public/documentation.spec.js`. Extend `frontend/src/locales/__tests__/locales.spec.js` and `frontend/src/router/__tests__/roleGuard.spec.js`.

**Interfaces:** Produce the guide schema above, `/documentation` and `/documentation/:topic`, and `usePublicPageHead({ title: ComputedRef<string>, description: ComputedRef<string> }): void`. Both routes have `meta.publicReading: true`; do not add an auth/role requirement. Other tasks consume these components and metadata contract.

- [ ] **1. Write guide integrity tests.** Resolve every key into every locale, assert nonempty strings and matching placeholders, unique topic/section IDs, valid related-topic targets, and valid named router action targets. First-task registry contains the three starting guides in the inventory, with no links to unpublished topics.

  ```js
  expect(new Set(USER_GUIDES.map((guide) => guide.id)).size).toBe(USER_GUIDES.length)
  expect(getGuide('getting-started')?.sections.map((section) => section.id))
    .toContain('verify-email')
  expect(getGuide('not-a-guide')).toBeUndefined()
  ```

- [ ] **2. Write reader/metadata/access tests.** Mount known, root, and unknown topic routes with actual plugins; test one h1, selected navigation, localized recovery link, and metadata after changing locale and topic on the same component. Root language tests cover initial `tl`, a change to `ceb`, and unsupported locale preserving the current value. Route guard tests admit guest, unverified farmer/buyer, and admin to these reading routes while retaining existing protected-route results.

  ```js
  await router.push('/documentation/not-a-guide')
  expect(wrapper.find('h1').text()).toBe('Guide not found')
  expect(wrapper.find('a[href="/documentation"]').exists()).toBe(true)
  ```

- [ ] **3. Run the new focused unit tests.** Use `npx vitest run src/constants/__tests__/userGuides.spec.js src/pages/public/__tests__/DocumentationPage.spec.js src/composables/__tests__/usePublicPageHead.spec.js src/__tests__/AppLanguage.spec.js src/router/__tests__/roleGuard.spec.js`. Expected initial failure: missing new modules/routes, not a missing plugin/setup error.
- [ ] **4. Implement the registry and complete the three starting articles in all languages.** Import `guides` into each existing root catalog; keep all older keys and API Docs work. Use the exact fixed section IDs. Account setup must distinguish email verification from administrator farm review; profile instructions include both roles' mobile own-profile path. Troubleshooting initially describes actual recovery steps without linking to unpublished chapters.
- [ ] **5. Build the reader/index.** Desktop uses topic navigation plus a `minmax(0,1fr)` article column; mobile uses native details/summary for contents. Use one h1, sequential headings, localized audience/prerequisites/results, support/related links, stable IDs, and `tabindex="-1"` on programmatic heading targets. Every article remains public. Guests get auth entry actions; only a matching signed-in role gets a protected workflow action. Cross-role/admin readers still get all instructional text and public links.
- [ ] **6. Add reactive metadata and document language.** The shared head composable consumes already translated computed strings. Unknown topic metadata uses the recovery title. Set `htmlAttrs.lang` from the active locale once in persistent `App.vue`, preserving existing transitions and locale persistence. No canonical URLs or SEO infrastructure are needed here.
- [ ] **7. Add/run initial browser reader checks.** `documentation.spec.js` loads index, a known article, and unknown topic with `mockSession(page, { authenticated: false })`; verifies headings, content links, no unexpected API calls, and readable narrow layout. Run `npx playwright test e2e/Public/documentation.spec.js --project=chromium --project=mobile-chromium` plus Step 3 and `npx vitest run src/locales/__tests__/locales.spec.js`. Expected: all pass; full reading-position behavior is owned by Task 4.
- [ ] **8. Check formatting/lint/build and commit owned files.** Commit message: `feat: add public documentation reader and getting-started guides`. Record commands/results; do not claim untranslated or future topics are complete.

## Task 2: Complete farmer planning, selling, and support documentation

**Files:** Modify `constants/userGuides.js`, `locales/guides/{en,tl,ceb}.js`, `constants/__tests__/userGuides.spec.js`, and `e2e/Public/documentation.spec.js`.

**Interfaces:** Add `farms-and-plots`, `crop-advisor`, `selling-harvests`, `verification-and-trust-score`, and `insurance-and-rsbsa` using Task 1's schema. All links target the inventory's stable section IDs.

- [ ] **1. Add chapter/relationship tests before content.** Assert the five expected IDs, listed section IDs, same-language copy availability, and `selling-harvests` linking to `price-guide` only after that target exists in Task 3. Add a browser case for a buyer reading farmer help: article visible; farmer-only action absent; no redirect.

  ```js
  for (const id of ['farms-and-plots', 'crop-advisor', 'selling-harvests',
    'verification-and-trust-score', 'insurance-and-rsbsa']) {
    expect(getGuide(id), id).toBeDefined()
  }
  expect(getGuide('selling-harvests').sections.map((section) => section.id))
    .toContain('optional-source')
  ```

- [ ] **2. Run the focused registry/browser tests to see the missing-content failures.** Use `npx vitest run src/constants/__tests__/userGuides.spec.js` and the relevant named `documentation.spec.js` case.
- [ ] **3. Write farm/advisory articles against current controls.** Read `FarmManager.vue`, `PlotPlanner.vue`, `CreateFarmForm.vue`, `usePlotMap.js`, `RecommendationsPage.vue`, and `useRecommendationAnalysis.js`. Farm name is required; location fields are optional. Explain drawing restrictions, saving confirmation, discarding an unsaved drawing, queued analysis, retry, interpretation, compatibility, and the current `farmer.rec_card.accept_contract` label (**Accept & Contract**). Do not document saved boundary editing/deletion or automatic publishing.
- [ ] **4. Write independent manual selling and support articles.** Read `ManualListingPage.vue`, `useInsurancePageActions.js`, `EnrollmentGuide.vue`, credit-score page/constants, and verification utilities. Manual listing requires no farm/analysis; source links are optional. Document insurance preparation/printable pack/external MAO submission and user-recorded policy/claim states; distinguish PCIC from the app. Explain the internal trust score without bank-acceptance claims.
- [ ] **5. Complete EN/TL/CEB copy and update related/support links.** Link the new chapters from existing troubleshooting sections; use actual control locale keys or copy their current translated labels accurately. Keep numeric limits out of prose unless needed and verified against constants. Record factual checks in the commit description, not a second product-content system.
- [ ] **6. Run registry/catalog tests and reader browser checks.** `npx vitest run src/constants/__tests__/userGuides.spec.js src/locales/__tests__/locales.spec.js`; `npx playwright test e2e/Public/documentation.spec.js --project=chromium --project=mobile-chromium`. Expected: all eight published chapters reachable, complete copy, role-appropriate actions, no invented prerequisites.
- [ ] **7. Run applicable format/lint checks and commit owned paths.** Commit message: `docs(ui): add farmer planning and support guides`.

## Task 3: Complete buyer, trade, price, and community documentation

**Files:** Modify the same registry, guide catalogs, and guide tests as Task 2. Add a checked-in content audit checklist to the implementation evidence section of this plan, not a new runtime file.

**Interfaces:** Add the remaining five topics: `buying-harvests`, `payments-and-purchases`, `demands-and-offers`, `price-guide`, `community-and-messaging`. Complete all related-topic links. The final registry contains exactly the 13 IDs in the inventory.

- [ ] **1. Extend registry tests with the complete explicit inventory.** Assert all 13 known IDs/section sets and every topic/fragment link resolves. Extend browser reader coverage to every topic in EN, with representative long payments/insurance articles in TL/CEB; catalog tests prove full string/key completeness.

  ```js
  expect(USER_GUIDES).toHaveLength(13)
  expect(getGuide('demands-and-offers').sections.map((section) => section.id))
    .toEqual(['post-demand', 'submit-offer', 'decide-offers', 'pay-and-deliver', 'complete-and-settle'])
  ```

- [ ] **2. Run the registry test and observe the five missing topics.** Reuse Task 2's unit command.
- [ ] **3. Write purchase/payment instructions from current behavior.** Read `MarketplacePage.vue`, `CheckoutSummary.vue`, `usePayment.js`, `MyPurchasesPage.vue`, `CashPaymentApprovalsPage.vue`, and payment constants. Explain PayMongo versus cash, future-harvest deposit versus available-harvest total, pending/failed/completed states, supported cancellation, and farmer confirmation of actual cash receipt. Do not copy the old `stripe` value from unrelated synthetic fixtures into user instructions.
- [ ] **4. Write demands/offers instructions from both sides.** Read buyer/farmer demand/offer pages, `useDemandActions.js`, and corresponding backend Actions. Show the saved-address requirement, live remaining quantity, one active offer restriction, acceptance/allocation, payment, farmer delivery, buyer completion, and supported full-balance recording in the actual order. Do not equate allocation, paid, delivered, and complete.
- [ ] **5. Write price/community articles and finalize all cross-links.** Price Guide is embedded, source/date aware, and can be unavailable; no standalone dashboard route. Chat requires a qualifying purchase or accepted offer. Cover forums, attachments, accepted answers, reports, and support using current UI. All 13 articles have prerequisites, actionable steps/results, appropriate recovery notes, and natural translations.
- [ ] **6. Perform the factual content audit.** Check manual selling independence; account/farm-verification distinction; cash-not-received state; accepted-unpaid offer; Price Guide AI attribution; external insurance submission; transaction chat; and absence of flood-risk or guaranteed-outcome claims against the approved spec/current code. This editorial review complements tests; string matching alone cannot prove the instructions true.
- [ ] **7. Run registry/catalog tests and full documentation browser file.** Same commands as Task 2; expected all 13 topics and related links work. Run applicable lint/format checks, then commit `docs(ui): add buying trading and community guides`.

## Task 4: Make public reading links, focus, and history reliable

**Files:** Create `frontend/src/composables/usePublicReadingNavigation.js`, `frontend/src/composables/__tests__/usePublicReadingNavigation.spec.js`, and `frontend/e2e/Public/reading-navigation.spec.js`. Modify `PublicLayout.vue`, `DocumentationPage.vue`, and narrowly scoped rules in `frontend/src/assets/main.css`; consume existing route `meta.publicReading`.

**Interfaces:** `usePublicReadingNavigation({ mainRef: Ref<HTMLElement | null> }): { onPageReady: () => void }`, mounted only by PublicLayout. PublicLayout calls readiness after initial render and actual transition completion; the composable also observes post-render same-component topic/hash updates. It owns only public **window** scrolling/focus; it does not write dashboard element scroll or change global `history.scrollRestoration`.

- [ ] **1. Write browser regressions for actual navigation timing.** Use stable headings and route links with the real out-in transition. Cover fresh `/documentation/farms-and-plots#draw-a-plot`, same-article hash changes, topic changes from a scrolled article, invalid hash/topic, two history entries for the same article, Back/Forward restoration, and rapid consecutive navigation. Save/assert actual positions within a named rounding tolerance; never sleep for animation duration.

  ```js
  await page.goto('/documentation/farms-and-plots#draw-a-plot')
  const target = page.locator('#draw-a-plot')
  await expect(target).toBeFocused()
  const targetBox = await target.boundingBox()
  const headerBox = await page.locator('header').boundingBox()
  expect(targetBox.y).toBeGreaterThanOrEqual(headerBox.y + headerBox.height)
  ```

- [ ] **2. Write lifecycle/unit checks for the composable.** Null main before mount, superseded pending navigation, popstate with saved position, unknown hash, and teardown must be safe. Assert no writes to `history.scrollRestoration` and no dashboard `scrollTo`. Keep DOM timing/focus assertions in Playwright rather than pretending jsdom proves layout.
- [ ] **3. Run new tests and observe failures.** `npx vitest run src/composables/__tests__/usePublicReadingNavigation.spec.js`; `npx playwright test e2e/Public/reading-navigation.spec.js --project=chromium --project=mobile-chromium`.
- [ ] **4. Implement scoped reading coordination.** Store window positions by browser history entry, not URL alone; any namespaced entry metadata must preserve Vue Router's existing `history.state` fields. Saved Back/Forward position takes precedence over hashes and heading focus. Fresh valid hash focuses its heading with `preventScroll`, then scrolls below the measured sticky header; fresh topic goes to its h1. Ignore stale work and clean listeners on unmount. Do not install router `scrollBehavior`: the installed implementation globally switches browser restoration to manual even if a route guard returns false.
- [ ] **5. Connect actual readiness and safe focus timing.** An after-enter hook alone misses reused topic/hash updates; a lone nextTick misses an outgoing transition. Wire both lifecycle paths without globally keying RouterView. Account for the outer App.vue layout transition when entering public content from auth/dashboard; use actual DOM transition readiness, not a duration sleep. Locale-only changes update text/metadata but do not steal focus from an open native dialog. Preserve existing About menu-trigger restoration. Use measured header geometry or scoped CSS scroll-margin, not a fixed assumption that enlarged headers remain 80px.
- [ ] **6. Add non-regression browser assertions.** Navigate from a reading article to a dashboard with synthetic session data and assert its existing scroll behavior; also enter a hashed guide from a different layout and assert the final heading offset/focus after the outer transition. Preserve the About keyboard-menu case in `design.spec.js`. Test a locale switch with the public dialog open and focus remaining inside it. Repeat direct hash/Back under reduced motion.
- [ ] **7. Run focused unit and cross-engine browser checks.** Run the commands in Step 3 plus `npx playwright test e2e/Public/reading-navigation.spec.js e2e/Public/design.spec.js --project=firefox --project=webkit`. Expected: all cases pass without larger timeouts or disabled transitions masking behavior. Run lint/format and commit `fix: preserve public guide reading position and focus`.

## Task 5: Deliver role-based tutorials with authentic app examples

**Files:** Create `HowItWorksPage.vue`, `TutorialJourney.vue`, `constants/tutorialJourneys.js`, `frontend/src/constants/__tests__/tutorialJourneys.spec.js`, `frontend/e2e/Public/tutorials.spec.js`, and `frontend/e2e/Public/tutorial-assets.spec.js`. Create `frontend/e2e/fixtures/farming.js` by extracting only fixture definitions shared with `frontend/e2e/Farming/planning.spec.js`; update that spec's imports. Create the four PNGs listed below. Modify router, root locales, and `frontend/README.md` with asset reproduction instructions.

**Interfaces:** `TUTORIAL_JOURNEYS: Journey[]` with stable IDs `plan-a-crop`, `sell-a-harvest`, `buy-a-harvest`, `request-a-crop`. A `Journey` has `id`, `role`, `titleKey`, `introKey`, and ordered steps `{ id, instructionKey, resultKey, guide: { topic, sectionId }, screenshotId? }`. `TutorialJourney` takes required `journey`; the parent renders `#farmers` and `#buyers`. Export `TUTORIAL_SCREENSHOTS` records `{ id, src, width, height, altKey, captionKey }`, keyed by `plot-setup`, `recommendations`, `manual-listing`, and `buyer-checkout`, after capture dimensions are known. Route name `how-it-works`, `meta.publicReading: true`.

- [ ] **1. Add journey/guide tests and browser behavior cases.** Every journey has ordered nonempty instructions/results and valid topic/section links. Check farmers' two independent paths, buyer purchase/request paths, both audience anchors, role-appropriate start actions, and complete text when images fail to load. Failed images must not cause a page error or blank instructions.

  ```js
  expect(TUTORIAL_JOURNEYS.map((journey) => journey.id))
    .toEqual(['plan-a-crop', 'sell-a-harvest', 'buy-a-harvest', 'request-a-crop'])
  for (const journey of TUTORIAL_JOURNEYS) {
    for (const step of journey.steps) {
      expect(getGuide(step.guide.topic).sections.map((section) => section.id))
        .toContain(step.guide.sectionId)
    }
  }
  ```

- [ ] **2. Run the new test files for the expected missing-page/registry failures.** `npx vitest run src/constants/__tests__/tutorialJourneys.spec.js`; `npx playwright test e2e/Public/tutorials.spec.js --project=chromium --project=mobile-chromium`.
- [ ] **3. Extract only genuinely shared farming fixtures.** Move existing `farmingFixtures`, `PLOT`, `GEOJSON`, `TAXONOMY`, `RESULT`, and the mock tile into `e2e/fixtures/farming.js` with named exports required by both tests. Keep socket orchestration local if only the old analysis test uses it. Re-run `e2e/Farming/planning.spec.js` to prove extraction preserved behavior; no new live API dependency.
- [ ] **4. Implement four deterministic screenshot cases using existing fixture patterns.** Import `test/expect/mockSession` from the strict session fixture. Register scene mocks after session setup. Generate review artifacts with `testInfo.outputPath`, never overwrite production PNGs during normal test runs. Use the following targets:

  | Asset | Synthetic scene and readiness |
  | --- | --- |
  | `plot-setup-en.png` | Farmer `/dashboard/farms/3/plots`; existing North Field/East bed fixture; fixed city extent, empty restricted zones/Overpass results, mocked tiles. Draw an unsaved polygon using Leaflet controls, wait for Save New Plot and `#plot-name`/`#plot-soil`, fill without saving. Crop the map/form controls. Caption identifies an illustrative map. |
  | `recommendations-en.png` | `/dashboard/plots/4/recommendations`; populated Pechay result fixture; wait for Pechay and **Accept & Contract**. Crop the recommendation card; no real analysis or publication. |
  | `manual-listing-en.png` | `/dashboard/contracts/new-listing`; empty farms/plots; Rice, title Rice harvest, quantity 100, price 50, available harvest. Wait for expected `#quantity_kg`/`#price_per_kg` values; crop useful fields without submitting. |
  | `buyer-checkout-en.png` | Verified buyer marketplace with one available Rice harvest at ₱50/kg; details endpoint `/market/items/listing/3`; quantity 25, Cash (Off-Site); wait for Contract Details and ₱1,250 total. Capture the review dialog before Proceed to Payment. |

  Map setup also needs deterministic `https://overpass-api.de/api/interpreter` handling; the existing map test does not draw a polygon. Preserve UI attribution and do not imply mock tiles represent real geography. Checkout needs `/user/addresses`, catalog/detail, and Price Guide mocks. No payment mutation/provider navigation is needed.
- [ ] **5. Capture and visually approve the images in the pinned Linux environment.** Run `npx playwright test e2e/Public/tutorial-assets.spec.js --project=chromium`. Wait for target states, real fonts/images, and loaded map tiles. Reset window, dashboard `#main-content`, and dialog scrolling, then deliberately position the crop; fullPage does not expand dashboard internal scrolling. Inspect images, copy only the four reviewed outputs into `public/images/guides/`, and record their intrinsic dimensions. Keep reproduction commands and the synthetic/English-label policy in README.
- [ ] **6. Implement the page and full EN/TL/CEB journeys from spec §6.** Use normal role anchor links and semantic ordered lists. Include where-to-go, action, confirmation where required, expected result, and read-more links. Screenshots use width/height, lazy loading, localized alt/captions, and visible English-example labels for TL/CEB; textual instructions always use current localized UI labels. Retain complete steps when images fail.
- [ ] **7. Run journey/catalog/browser regressions.** Unit command from Step 2 plus locale tests; `npx playwright test e2e/Public/tutorials.spec.js e2e/Public/tutorial-assets.spec.js e2e/Farming/planning.spec.js e2e/Marketplace/listing.spec.js e2e/Marketplace/transactions.spec.js --project=chromium --project=mobile-chromium`. Asset generation can be desktop-only, but tutorial layout/behavior must run in both projects. Expected: no real transactions, missing mocks, or page errors.
- [ ] **8. Run lint/format/build, review screenshots/copy, and commit owned paths.** Commit message: `feat: add farmer and buyer getting-started tutorials`.

## Task 6: Build the Features destination from the shared inventory

**Files:** Create `FeaturesPage.vue`, `frontend/src/pages/public/__tests__/FeaturesPage.spec.js`, and `frontend/e2e/Public/features.spec.js`. Modify `constants/platformFeatures.js`, root locales, and router. Read both existing consumers (`HeroSection.vue`, `AboutPage.vue`) before changing the constant shape.

**Interfaces:** Route `features` at `/features`, `meta.publicReading: true`. Retain every existing feature field/key; add stable `id`, `groupId`, `audienceKey`, and `guide: { topic, sectionId }`. Group IDs are `plan-grow`, `trade-connect`, `farm-support` in that order. No feature-specific state/store.

- [ ] **1. Add the inventory and link tests.** Assert all eight existing translated feature headings and descriptions, correct group membership (3/3/2), guide links resolve, and metadata changes with locale. Mount About with the changed inventory and confirm its existing feature summaries remain intact.

  ```js
  expect(PLATFORM_FEATURES).toHaveLength(8)
  expect(PLATFORM_FEATURES.filter((feature) => feature.groupId === 'plan-grow')).toHaveLength(3)
  expect(wrapper.findAll('a[href^="/documentation/"]')).toHaveLength(8)
  ```

- [ ] **2. Run the new test for missing route/page/fields.** `npx vitest run src/pages/public/__tests__/FeaturesPage.spec.js`.
- [ ] **3. Extend the shared inventory without breaking Home/About.** Link advisor→`crop-advisor`, planning→`farms-and-plots`, market→`selling-harvests`, weather→`crop-advisor#read-results`, demands→`demands-and-offers`, insurance→`insurance-and-rsbsa`, score→`verification-and-trust-score`, community→`community-and-messaging`. Each fragment must be in its guide registry. Use stable IDs for new lists; do not remove older fields still consumed elsewhere.
- [ ] **4. Implement grouped summaries and all locale text.** Use shared surfaces, icons, descriptive role labels, and explicit guide links. Explain crop compatibility within advisory and Price Guide within marketplace content; no fake standalone feature routes. Include concise supported-behavior limits where material. Keep the page readable in one column at narrow widths and up to three columns when text fits.
- [ ] **5. Run unit/catalog/browser checks and commit.** `npx playwright test e2e/Public/features.spec.js e2e/Public/design.spec.js --project=chromium --project=mobile-chromium`; relevant Vitest plus lint/format. Home still has its inventory until Task 7, so existing assertions remain valid here. Commit `feat: add grouped YieldGrid features page`.

## Task 7: Rework Home around purpose, farming needs, and vision

**Files:** Modify HomePage, HeroSection, router metadata, and root locales. Create HomePurposeSection and HomeVisionSection from the file map. Modify `e2e/Public/navigation.spec.js`, `e2e/Public/design.spec.js`, and `e2e/Visual/redesign.spec.js` with corresponding Home snapshots. Add `frontend/src/pages/public/__tests__/HomePage.spec.js` for reactive metadata and preserved responsive image preloads using actual head/i18n plugins.

**Interfaces:** HeroSection owns only the landscape opening. HomePage composes hero→purpose/three needs→vision/audience next steps. Preserve existing responsive image preloads in a separate Home head entry alongside the shared reactive metadata. Home route becomes `meta.publicReading: true` without changing its authenticated redirect.

- [ ] **1. Change the behavior assertions first.** Home has one new h1, definition, vision, source links, farmer/buyer tutorial links, and Browse Market; all eight features are asserted on `/features` and still on About. Assert Home no longer contains the full eight-heading grid. Sweep all e2e for `Smarter farming`, old Home keys, `platform-heading`, and button-role Get Started references; change only expectations affected by the new content/semantics.

  ```js
  await page.goto('/')
  await expect(page.getByRole('heading', { level: 1 }))
    .toHaveText('Practical tools. Stronger farming futures.')
  await expect(page.locator('a[href="/how-it-works#farmers"]')).toBeVisible()
  await expect(page.locator('a[href="/how-it-works#buyers"]')).toBeVisible()
  await expect(page.getByRole('heading', { name: 'AI Crop Advisor', exact: true })).toHaveCount(0)
  ```

- [ ] **2. Run changed public tests to confirm the old page fails the new contract.** `npx playwright test e2e/Public/navigation.spec.js e2e/Public/design.spec.js --project=chromium --project=mobile-chromium`.
- [ ] **3. Recompose Home and simplify the hero.** Use the approved headline/definition/vision from spec §4. Preserve the existing image/scrim/wave and image preloads; replace repeated decorative hero support text with the focused introduction and Get Started/How It Works links. Use real links for navigation and update role selectors accordingly. Keep current authenticated Home redirect behavior.
- [ ] **4. Add three grounded need/response rows and source notes.** Market access→listings/demands; price context→source-labeled Price Guide; crop planning→plot/soil/crop/weather support. Use the World Bank/DA/PAGASA URLs and wording boundaries in the spec. Present better livelihoods as the creator's vision; include no borrowed project results, current-weather alerts, invented statistics, or implied agency partnerships.
- [ ] **5. Complete natural EN/TL/CEB copy and retain About.** Update home metadata per locale, preserve unrelated keys, and remove only keys proven unused by `rg` across source/tests. Do not rewrite About or delete its shared feature summaries.
- [ ] **6. Run public/locale/head checks and review Home baselines.** Focused browser files plus `features.spec.js`; `npx vitest run src/pages/public/__tests__/HomePage.spec.js src/constants/__tests__/userGuides.spec.js src/locales/__tests__/locales.spec.js src/composables/__tests__/usePublicPageHead.spec.js`; lint/format/build. In the pinned Linux environment regenerate only intended Home baselines after modifying the visual headline expectation; inspect desktop/mobile images and diffs. Replay visuals without updates and run `npm run test:e2e` before committing the changed Home baseline set.
- [ ] **7. Commit the complete Home slice.** Commit message: `feat: explain YieldGrid purpose and farmer-first vision on home`.

## Task 8: Connect public navigation without crowding the header

**Files:** Modify AppNavbar, AppFooter, NavLink, root locale catalogs, their existing unit specs, locale shared-chrome coverage, and `e2e/Public/{navigation,design,language-switch}.spec.js`. Create `frontend/src/components/molecules/__tests__/NavLink.spec.js` if no current equivalent exists. Modify affected public/marketplace/mobile-menu baselines in `e2e/Visual/redesign.spec.js-snapshots/`.

**Interfaces:** Desktop internal links: Features→How It Works→Documentation→About; brand links Home; preserve external API Docs after internal links. Mobile includes Home plus all four, Contact, API Docs, existing auth/profile/language controls. Footer includes Home, Features, How It Works, Documentation, About, Contact, API Docs. Add optional `active: boolean | null = null` to NavLink; null retains existing behavior, explicit true marks both Documentation routes active with correct `aria-current` semantics.

- [ ] **1. Update exact nav contracts and write preservation tests.** Navbar/footer internal Documentation points to `/documentation`; API Docs keeps `API_DOCS_URL`, `_blank`, and `noopener`. Test topic-route active styling/accessible current state, mobile external-link dismissal, both roles' own profile, logout confirmation, Escape/Close restoration, and Contact access. Preserve the new API Docs tests by adapting expected internal order, not deleting their assertions.

  ```js
  expect(footer.find('a[href="/documentation"]').attributes('target')).toBeUndefined()
  const apiLink = footer.find(`a[href="${API_DOCS_URL}"]`)
  expect(apiLink.attributes('target')).toBe('_blank')
  expect(apiLink.attributes('rel')).toBe('noopener')
  ```

- [ ] **2. Run nav tests and confirm old link lists fail.** `npx vitest run src/components/organisms/__tests__/AppNavbar.spec.js src/components/organisms/__tests__/AppFooter.spec.js src/components/molecules/__tests__/NavLink.spec.js src/constants/__tests__/apiDocs.spec.js`.
- [ ] **3. Implement link groups and current-topic semantics.** Keep API URL derivation in the existing constant. Reuse public link records between desktop/mobile where they truly overlap; no navigation engine. Use explicit active override only where a section owns nested topic routes. Preserve current menu/confirmation behavior and all supported-language labels.
- [ ] **4. Verify header fit with real text at every required width/font size.** Keep desktop navigation at the existing 1280px breakpoint where it fits. If localized/enlarged content cannot fit, show the existing drawer before anything overlaps; do not shrink labels or create another mobile header row. Implement a small measured-fit check in Navbar if needed, using ResizeObserver with cleanup and a stable measurement that avoids toggling loops. Add a focused test for the actual fallback implementation. Do not remove API Docs to manufacture space.
- [ ] **5. Expand responsive and keyboard checks.** `design.spec.js` covers new routes, all three locales, 360/390/768/1024/1280/1440 widths, 100%/200% text, short menu height, visible focus and reduced motion. `language-switch.spec.js` verifies article route/hash stays the same and focus remains in the open dialog during locale changes. Preserve the existing About menu-trigger focus assertion.
- [ ] **6. Run focused navigation/reading tests and review all shared-chrome baselines.** Run nav unit command, locale tests, and `npx playwright test e2e/Public --project=chromium --project=mobile-chromium`. Inspect Home/About/Contact/marketplace/mobile-menu snapshots, plus already-existing new-page snapshots if recorded. Regenerate intended regions in the pinned Linux environment, inspect every diff, replay without updates, then full browser suite before commit.
- [ ] **7. Commit integrated navigation and its reviewed baseline changes.** Commit message: `feat: connect public learning pages and preserve API docs navigation`.

## Task 9: Complete visual, content, and regression acceptance

**Files:** Extend `e2e/Visual/redesign.spec.js` and its snapshot directory; finalize `e2e/Public/{documentation,reading-navigation,tutorials,features,design}.spec.js`; update `frontend/README.md` reproduction commands and this plan's execution evidence. No business-code changes are expected unless an introduced defect is found.

**Interfaces:** Reuse strict session fixtures and the existing `capture(page, testInfo, name)` policy. New baseline names are `public-features`, `public-how-it-works`, `public-documentation-index`, and `public-documentation-article`; each gets desktop/mobile Linux images. Use `payments-and-purchases` as the representative article. Give all new names the `public-` prefix so existing public font/image/scroll readiness runs.

- [ ] **1. Add final visual cases and evidence captures.** Assert route/title/content readiness before capturing the new page/index/article states. Keep a scrolled article evidence image when below-fold content would otherwise be missed; do not mask content to avoid differences. Check full-page length/readability manually as well as viewport interactions. Reset window and `#main-content` explicitly before every pixel capture, including marketplace cases that do not use the `public-` prefix.
- [ ] **2. Sweep the whole e2e tree and content registry.** Run `rg -n 'Smarter farming|platform-heading|feature_advisor|feature_planning|API Docs|/documentation|Get started' e2e src/components/organisms/__tests__ src/locales/__tests__`. Inspect matches for stale selectors, moved content, external/internal docs ambiguity, and stale API fixture payloads; do not mechanically replace unaffected text. Verify all 13 topics, four journeys, eight feature areas, three source links, and both role CTAs against the spec.
- [ ] **3. Run static/unit/build checks in a supported Node environment.** All commands must finish successfully; record actual counts and output, not expected totals from older releases.

  ```sh
  npm run lint:check
  npm run format:check
  npx prettier --check "e2e/**/*.js" playwright.config.js vite.config.js
  npx vitest run --maxWorkers=2
  npm run build
  ```

- [ ] **4. Generate proposed Linux baselines only in the pinned container.** Use `mcr.microsoft.com/playwright:v1.63.0-noble@sha256:eff16c30e6f3f4af0a03fa4b706120d5e9b0891c344a27d64559aff5900a4a27`, Linux-installed dependencies, self-hosted fonts, and the README production preview. Do not mount macOS node_modules. In `frontend`:

  ```sh
  npm ci --legacy-peer-deps
  npx playwright test e2e/Visual/redesign.spec.js --project=chromium --project=mobile-chromium --update-snapshots
  ```

  Before accepting, open every changed/new image and every available diff. Changed regions must match the approved page design or intentional shared chrome. A missing baseline is a proposal, not proof. Do not regenerate unrelated failures blindly.
- [ ] **5. Replay and run the full Chromium suite without update flags.**

  ```sh
  npx playwright test e2e/Visual/redesign.spec.js --project=chromium --project=mobile-chromium
  npm run test:e2e
  ```

  Expected: no visual mismatches, unexpected requests, page errors, or failures. Diagnose deterministic failures; do not lengthen timeouts or accept snapshots merely to turn checks green.
- [ ] **6. Run separate Firefox/WebKit behavior smoke.**

  ```sh
  npx playwright install --with-deps firefox webkit
  npx playwright test e2e/IdentityAndAccess e2e/Shared e2e/Public/reading-navigation.spec.js e2e/Public/documentation.spec.js e2e/Public/tutorials.spec.js --project=firefox --project=webkit
  ```

  Expected: keyboard/dialog/auth/reading behavior passes. Pixels are still approved only in Linux Chromium; do not create cross-engine baseline sets.
- [ ] **7. Perform final human-readable QA and current-diff review.** Read each translated journey against the UI, open every guide link, inspect mobile article/screenshots and enlarged text, and review source attribution/vision language. Verify real map tiles separately only if map rendering/styles changed; tutorial mocks cannot certify live tiles. Request one independent whole-diff review against the intended base, fix introduced issues, and verify the latest revision. No backend suite is required for untouched backend code; if that scope changes, add the relevant backend checks.
- [ ] **8. Record evidence and commit the accepted deliverable.** Include reviewed baselines, reproducible tutorial assets, finished translations, and updated evidence. Commit message: `test: verify public guides and reviewed organic page baselines`. Do not merge, push, or deploy without the corresponding user instruction.

## Completion criteria and evidence to fill during execution

The work is complete only when all nine tasks' deliverables and acceptance checks are met. Do not check a box solely because code was written or images were regenerated.

| Evidence | Required record |
| --- | --- |
| Revision/environment | Executed commit/base, Node version, pinned Linux browser environment |
| Content coverage | 13 complete topics × 3 locales; four complete journeys; eight Features areas; actual control/permission review |
| Sources/mission | Source URLs checked, contextual claims supported, founder aspiration clearly identified |
| Unit/static/build | Actual commands/results/counts; any remaining failures explicitly recorded |
| Browser behavior | Full Chromium/mobile result; separate Firefox/WebKit result; responsive/keyboard/200% text evidence |
| Visuals/assets | Each affected public baseline and four tutorial PNGs reviewed; no-update replay passed |
| Regression scope | About, existing public routes, API Docs, role guards, profile/menu focus, dashboard scrolling preserved |
| Review | Latest diff reviewed and actionable introduced issues resolved |

### Specification coverage

| Approved specification sections | Owning tasks |
| --- | --- |
| 1–3: intent, existing work, page ownership/navigation | Global constraints and Tasks 1, 6–8 |
| 4: homepage order, copy, evidence | Task 7 |
| 5: eight grouped features | Task 6 |
| 6: shared start, four journeys, screenshots | Task 5 |
| 7: 13 articles, roles, truthful behavior/recovery | Tasks 1–3 |
| 8: visual, responsive, keyboard design | Tasks 1, 4–9 |
| 9: component/data/head/history responsibilities | Tasks 1, 4–8 |
| 10: existing test migration and final acceptance | Tasks 1–9, final evidence in Task 9 |
| 11: review and implementation handoff | This plan and its execution-method gate |

**Planning verification (2026-10-12):** Specification approved; current source/fixtures/interfaces inspected. Self-review covered every spec section, task inputs/outputs, step decisions, the five Review Focus risks, and proportion. Structural checks passed for nine tasks, 64 ordered steps, all 13 matching topic IDs, code fences, local spec link, placeholder markers, and trailing whitespace; `git diff --check` passed. No app tests, build, or browser execution were performed during planning. Application execution results belong here only after implementation runs them.

### Content audit during implementation — 2026-10-12

- Manual listing works independently of farm records and AI analysis; optional-source instructions reflect the current form.
- Email verification and administrative farm/plot review are distinct; farm name is required and location fields are optional.
- Cash checkout creates pending receipt; partial/full approval records only money actually received.
- Accepted demand offers allocate quantity without payment; delivery and buyer completion are separate actions.
- Online future-harvest deposit follows current payment constants; balance settlement records receipt after delivery rather than starting another checkout.
- Price Guide reads YieldGrid → DA → AI with source/date context; an AI estimate is not a DA quotation.
- Insurance instructions cover preparation, external MAO submission and user-entered policy/claim records; trust scores make no lender-acceptance claim.
- Chat is linked to eligible purchases/accepted offers. No removed flood-risk capability or guaranteed-outcome claim was added.
