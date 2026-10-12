# YieldGrid Public Information and User Guides

**Date:** 2026-10-12
**Status:** Page structure, homepage vision, and organic direction approved in chat; written specification awaiting review.
**Requested deliverable:** A detailed implementation plan, not product implementation in this session.
**Design authority:** `AGENTS.md`, the approved 2026-10-07 Modern Organic specification and plan, and `.agents/skills/yieldgrid-design-system/SKILL.md`.

## 1. Intent and success

Explain what YieldGrid is, communicate its creator's vision of making life better for Filipino farmers, and help farmers and buyers understand and use the existing application. Replace the homepage's exhaustive feature inventory with a clear introduction and an evidence-informed purpose. Give capabilities, beginner tutorials, and detailed reference material distinct homes.

The user explicitly selected documentation for **farmers and buyers**, with the existing developer API documentation linked separately. They approved a story-led Home, grouped Features, step-by-step How It Works, and a practical Documentation help center. Preserve the Field & Linen identity, current business workflows, and English/Tagalog/Cebuano support.

Success means a visitor can answer what YieldGrid does and why it exists; locate each of the eight existing feature areas; follow the correct farmer or buyer starting path; and find precise help for an existing workflow without confusing guidance with a promise of income, approval, insurance coverage, or successful payment.

## 2. Current project evidence and boundaries

Planning inspection started on `develop` at `fb6c1a9`. The selected release notes predate recent overflow and baseline fixes; their recorded failures are historical, not verified failures on this tree. No application tests or live business transactions were run during this design stage.

| Existing implementation | Consequence for this change |
| --- | --- |
| `HomePage.vue` owns metadata/preloads and renders `HeroSection.vue`; the latter contains hero, feature grid, and final CTA | Keep the landscape hero; return whole-page composition to Home rather than extending the hero into an even larger page component |
| `constants/platformFeatures.js` is shared by Home's hero and About | Preserve all eight feature areas and About's current content; remove only Home's full inventory and render the expanded inventory on Features |
| `PublicLayout.vue` provides navbar, footer, skip link, main landmark, and route transition | Reuse this shell for the new routes |
| Navbar has Home/About/Contact links; desktop begins at 1280px | Rebalance links and verify expanded navigation in every locale; do not squeeze labels or wrap the mobile header |
| Existing locale catalogs and compile/parity tests cover EN/TL/CEB | New content ships in all three languages, including metadata, alt text, error help, and navigation |
| API reference is served at the API host's `/docs` | Use `/documentation` for user help and preserve the distinct external API Docs destination |
| Authenticated navigation to `/` currently redirects to the user's dashboard | Preserve this behavior; the three new information pages remain public and accessible when signed in |

Existing uncommitted work must be preserved: `AppFooter.vue`, `locales/{en,tl,ceb}.js`, `constants/apiDocs.js`, `constants/__tests__/apiDocs.spec.js`, and `organisms/__tests__/AppFooter.spec.js`. The pending footer adds an API Docs link; its exact-order test will need an intentional update when navigation expands.

Public Home/About/Contact/marketplace snapshot modifications also appeared during planning from concurrent workspace activity. They are not outputs of this specification and must be preserved and reassessed at implementation time.

Scope is public content, its presentation, routing, navigation, localization, metadata, and regression coverage. No new backend endpoints, database changes, payment behavior, account roles, CMS, external search service, in-app tour, or new runtime dependency is required.

## 3. Chosen approach and information architecture

The selected approach is a story-led homepage with task-focused learning pages. A feature-heavy marketing approach would retain repetition; a documentation-first homepage would reduce the presence of the creator's vision. The chosen approach separates four visitor needs while retaining one visual identity.

| Route | Visitor question | Content owner |
| --- | --- | --- |
| `/` | What is YieldGrid, and why does it matter? | Home |
| `/features` | What can I do with it? | Capability summaries and links to guides |
| `/how-it-works` | How do I begin? | Short ordered journeys for farmers and buyers |
| `/documentation` | Where can I find detailed help? | Guide index and topic navigation |
| `/documentation/:topic` | How does this specific workflow work? | One reusable article page driven by a known topic |
| `/about` | What principles guide YieldGrid? | Existing About page, preserved |
| `/contact` | How do I reach the team? | Existing Contact page, preserved |

The topic URL is a reading affordance within Documentation, not a separate documentation application. Use stable, locale-independent slugs and section IDs; translated headings must not change saved links. Features and tutorials cross-link to the relevant topic and section.

### Navigation contract

- Desktop: brand link to Home; primary links **Features**, **How It Works**, **Documentation**, **About**; existing language and authentication controls.
- Footer: Home, Features, How It Works, Documentation, About, Contact, and the existing external API Docs link, grouped as product/help information where space permits.
- Mobile menu: Home and all public destinations, including About and Contact, followed by existing authentication/profile controls and language choice. Keep one header row at 360–390px.
- Keep Contact easy to find in Documentation's support ending and the mobile menu. Do not remove either existing route.
- Preserve native dialog focus containment, Escape, close button, route-change dismissal, focus restoration, short-screen scrolling, own-profile access for both roles, and logout confirmation.
- New learning pages are readable without login. Links to protected workflows use existing routes and guards. Guests see a clear sign-in/register next step; signed-in users are not encouraged into the other role's protected workflow. Do not invent post-login return behavior or auto-select a registration role unless the existing flow supports it.

## 4. Home content and composition

Retain the existing landscape asset, responsive preload, mobile background behavior, contrast scrim, and restrained white wave. Simplify the repeated supporting copy inside the hero so the headline, explanation, and primary action carry the hierarchy.

### Section order

1. **Hero:** one clear promise of purpose; a concrete one-paragraph definition; Get Started and How It Works links; a brief Filipino farmer/buyer audience label.
2. **What YieldGrid is:** a short editorial introduction explaining the connection between crop planning, harvest trading, and farming knowledge. Link to the complete Features page.
3. **Why this matters:** three compact problem-and-response rows, covering access to buyers, price context, and informed crop planning. Use prose with restrained icons and dividers, not another eight-card feature catalog.
4. **Our vision:** an intentional linen or moss-tinted section with a strong serif statement and brief supporting paragraph about farmers' livelihoods. Treat this as an aspiration from the creator, not measured impact or a testimonial.
5. **Choose your next step:** farmer and buyer starting links to the corresponding How It Works anchors; retain a clear Browse Market destination in the closing action area.

### Approved draft message direction

**Headline:** Practical tools. Stronger farming futures.

**Definition:** YieldGrid brings crop planning, harvest trading, and farming knowledge into one place for Filipino farmers and buyers.

**Vision:** YieldGrid began with a simple vision: to help make life better for Filipino farmers—by making useful information easier to understand, buyers easier to reach, and everyday farming decisions easier to manage.

Final EN/TL/CEB copy should preserve this meaning and use natural language rather than a literal word-for-word translation. Do not invent a founder name, biography, farmer quote, customer count, income improvement, adoption statistic, or partner endorsement.

### Evidence and wording boundaries

| Theme | Source and evidence | Appropriate YieldGrid connection |
| --- | --- | --- |
| Market access | [World Bank, 7 April 2026: Scaling Rural Transformation](https://www.worldbank.org/en/results/2026/04/07/scaling-rural-transformation-in-the-philippines-connecting-farmers-to-markets-jobs-and-opportunity) identifies limited market access and low productivity among rural challenges | Listings, buyer demands, and eligible transaction conversations help users connect; do not claim that software replaces roads, logistics, cooperatives, or guarantees buyers |
| Price information | [Department of Agriculture: Price Monitoring](https://www.da.gov.ph/price-monitoring/) publishes price references | Explain the app's Price Guide as context with source/date labels; retail monitoring, local trade prices, and AI estimates are not interchangeable or guaranteed sale prices |
| Crop planning | [DOST-PAGASA: Ten-Day Regional Agri-Weather](https://www.pagasa.dost.gov.ph/ten-day-regional-agri-weather) provides agricultural weather guidance and connects weather information to field planning | Explain plot, soil, crop, and weather information as decision support; do not imply that YieldGrid is PAGASA, uses its feed, or provides its warnings unless separately verified |

Sources were consulted on 2026-10-12. Use modest linked source notes beside/below the factual section. Avoid transient weather alerts or numerical statistics in evergreen homepage copy. Cite the problem context; describe the software's response from repository evidence. Do not attribute another project's measured results to YieldGrid.

## 5. Features

Move the full Home feature inventory to Features, keeping the eight existing identities and localization keys where their wording remains accurate. Group them by user purpose:

| Group | Feature areas | Required explanation |
| --- | --- | --- |
| Plan & grow | Farm & Plot Planning; AI Crop Advisor; Weather Insights | What users enter, what the app shows, what is advisory, and where to learn the workflow |
| Trade & connect | Harvest Marketplace; Buyer Demands & Offers; Community & Messaging | Existing harvest versus future contract; buyer-request alternative; payment/fulfillment states; chat eligibility |
| Farm support | Crop Insurance & RSBSA; Farmer Trust Score | Preparation and records; internal activity-based score and report; external decisions remain external |

Each feature summary has a title, plain-language benefit, a short description of current behavior, audience label, and a specific guide link. Use focused card groups with heading/description links, not entire clickable containers containing other links. Desktop can use two or three columns where text remains readable; mobile stacks cards.

Explain Price Guide as a supporting marketplace capability and crop compatibility under advisory tools. Do not advertise nonexistent standalone routes for either embedded weather or Price Guide. About retains its existing summary grid in this scope; a broader About rewrite is not required.

## 6. How It Works

Use a brief introduction and ordinary farmer/buyer anchor links, followed by semantic ordered lists. All instructions remain available as text; screenshots supplement them. Avoid custom tabs, progress tracking, carousels, or a mandatory wizard.

Each step specifies **where to go**, **what to do**, **what should happen**, and a **read-more destination**. Use exact existing localized control labels. Show confirmation as a step wherever the actual workflow asks for it. Mark optional branches and asynchronous waiting explicitly.

### Shared start

Choose Farmer or Buyer when registering, create the account, verify the email address, and use the role dashboard. Explain profile/address and language access. An optional address during registration is not a universal prerequisite; saved addresses are required for the demand/offer operations that enforce them.

### Farmer path A: plan a growing area

1. Open My Farms and create a farm with a required name and optional location details.
2. Open its plots, draw a boundary, enter the plot name and soil type, review calculated area, and confirm saving.
3. Open Recommendations for a selected plot; set the available crop preferences and confirm analysis.
4. Wait for results; explain the visible processing state and actual retry path on failure.
5. Review crop options and projected information. Publishing is a separate choice: Accept & Create Contract opens terms for review and confirmation.
6. Find a published contract in My Contracts; follow purchase/cash handling in the detailed guide.

### Farmer path B: sell an existing harvest

1. Open Post Harvest after email verification.
2. Enter the crop, quantity, price, availability, and other required listing details; consult Price Guide if useful.
3. Optionally link an owned farm/plot. Neither plot drawing nor AI analysis is required.
4. Review and confirm the listing, then manage it under My Contracts.
5. Follow eligible buyer conversations and record cash receipt only after the money is actually received.

Link to the separate responding-to-demands article as another selling option; do not make it a prerequisite for either path.

### Buyer path A: find and purchase a harvest

1. Browse Market; guests can browse the public marketplace, while checkout uses the authenticated buyer flow.
2. Search/filter and inspect listing quantity, availability, price, and source details.
3. Choose quantity and an available payment option, review the displayed amount, and confirm checkout.
4. Follow the online checkout or cash instructions. Choosing cash is not confirmation of receipt.
5. Check My Purchases for the current status and use eligible transaction chat for coordination.

### Buyer path B: request a crop

1. Add a saved marketplace address through the buyer's own profile.
2. Open Post Demand and enter the required crop, quantity, price, address, and dates.
3. Review and confirm; follow responses under My Demands.
4. Inspect and accept/reject farmer offers. Accepting allocates quantity; it does not pay the offer.
5. Follow payment, delivery, and completion actions in order; the detailed guide distinguishes each state.

### Screenshot policy

Use current production-build UI with synthetic fixtures, not invented app mockups or personal account data. Capture at least farm/plot setup, recommendation review, manual listing, and buyer checkout; crop enough to keep important controls legible on mobile. Add localized captions/alt text and text equivalents for every action. If English UI captures are reused in TL/CEB articles, visibly label them as English examples; localized prose must use the user's actual UI labels. Reserve image space and lazy-load below-fold captures.

## 7. Documentation content contract

Documentation is a public, task-oriented reference. The root shows a compact introduction, starting guides, grouped topic links, and support. A topic route shows the same topic navigation with a single readable article. No backend content fetch, external search index, or account state is needed to read a guide.

### Required topic inventory

| Stable slug | Audience | Required coverage |
| --- | --- | --- |
| `getting-started` | Both | Role choice; registration; email verification/resend; login/password recovery; what the dashboard provides; links to quick starts |
| `profile-addresses-language` | Both | Own profile on desktop/mobile; saved addresses and when they are required; language selection/persistence |
| `farms-and-plots` | Farmer | Farm creation; plot drawing/name/soil; area; city/restricted-area rejection help; creation confirmation and discarding an unsaved drawing; email versus farm verification |
| `crop-advisor` | Farmer | Plot selection; analysis inputs; queued/loading/error/retry; reading recommendations; projections/limitations; explicit publishing; crop compatibility |
| `selling-harvests` | Farmer | Manual listing and optional source; required fields; availability; review/confirmation; manage listings/contracts; why AI analysis is optional |
| `buying-harvests` | Buyer | Search/filter/details; available versus future harvest; quantity; checkout review; where purchases appear |
| `payments-and-purchases` | Both | Actual online/cash options; downpayment/full-payment display; pending/failed/completed states; farmer cash approval; supported cancellation; coordination without delivery promises |
| `demands-and-offers` | Both | Saved address; posting demand; submitting/withdrawing offers; limits/live quantities; accept/reject; allocation versus payment; delivery/completion; balance recording |
| `price-guide` | Both | Where the embedded guide appears; YieldGrid/DA/AI sources; dates/stale/unavailable information; AI label; reference versus negotiated price |
| `verification-and-trust-score` | Both/Farmer | Provenance badges and limits; account email versus farm/plot admin decisions; internal score factors, tips/history/report; no lender-acceptance promise |
| `insurance-and-rsbsa` | Farmer | RSBSA record guidance; rice/corn enrollment preparation; selecting a plot in the UI; printable pack; external MAO submission; policy/CIC records; claims/reminders; no automatic coverage or claim decision |
| `community-and-messaging` | Both | Transaction chat eligibility; conversations/attachments; forum threads/replies/votes/accepted answers; reporting and practical conduct |
| `troubleshooting-and-support` | Both | Unverified email; missing address; rejected plot; failed analysis; unavailable Price Guide; pending payment; unavailable chat; failed downloads; Contact/Report Issue routes |

Each article has one topic h1, audience, short outcome, prerequisites, task sections with numbered steps, expected results, relevant states/recovery, related guides, and a support link. Explain the controls and state labels visible to the user; omit implementation jargon such as repositories, queue class names, and SDKs. Do not repeat numeric limits in prose when a link to the current form communicates them better; any necessary value must be checked against current constants during implementation.

Long articles have an on-page section list. Desktop topic navigation is sticky below the public header; mobile uses a native collapsible contents region with an accessible label. Article text stays near 65–70 characters per line. Use headings, lists, restrained informational callouts, and occasional screenshots rather than enclosing every paragraph in a card.

Unknown topic slugs show a clear Guide not found state with a route back to the documentation index. Unknown hashes leave the article usable. Direct loading, refresh, Back/Forward, locale switching, and fragment links must preserve the topic and bring valid headings into view below the sticky header. Navigation must not change dashboard scrolling behavior.

### Verified behavior that copy must preserve

- Email verification enables protected mutations. Administrative farm/plot review provides provenance status and is independent; it is not an enforced prerequisite for analysis, manual listing, or publishing. Do not tell users that every account must wait for admin approval. Do not invent a user-facing verification request button.
- Manual Post Harvest can omit farm/plot links. Recommendations are asynchronous; analysis does not publish a listing.
- The current gateway binding is PayMongo, with cash also supported. The older Stripe stack note is not evidence for current UI instructions. Exact amounts come from the app's current payment constants and checkout display.
- Future-harvest checkout currently uses a 10% downpayment; available harvest uses full payment. Cash selection is pending receipt. Payment completion and physical delivery are different concepts.
- Accepted demand offers allocate quantities. Farmer delivery and buyer completion are separate actions. The supported balance-settlement action records full receipt; do not describe it as another automatic online checkout.
- Chat requires a qualifying non-failed purchase or accepted demand offer, not merely knowing another user's profile.
- Price Guide identifies source and freshness; an AI estimate is not an official DA quotation.
- Insurance tools prepare and track records around external submission; no automatic insurer enrollment/approval/payment is established by the inspected flow. Use **PCIC**, not the release notes' “PICC.”
- Trust Score is based on YieldGrid activity and offers a report. Bank integration, loan eligibility, and guaranteed lender acceptance are not established.
- The removed flood-risk mapping feature is not part of the new feature list or guide.

Primary repository evidence includes `RegisterForm.vue`, `router/index.js`, `useRecommendationAnalysis.js`, `ManualListingPage.vue`, `CheckoutSummary.vue`, `usePayment.js`, `EnrollmentGuide.vue`, backend marketplace Actions, `AssertTransactionExistsAction.php`, and the `AppServiceProvider` payment binding. Implementation must recheck labels and interactions on the revision actually being built.

## 8. Visual and accessibility design

- Preserve centralized `moss`, `harvest`, `soil`, `dew`, and `stone` tokens and current shadows. No palette replacement or new design framework.
- Use self-hosted Lora headings and Inter body text; one h1 per page/article; orderly h2/h3 hierarchy. Keep existing 16px body/control typography, relaxed line-height, 44px targets, visible focus, and reduced-motion behavior.
- Home: landscape-led opening, generous editorial sections, one wave, restrained mission emphasis. Features: compact grouped summaries. Tutorial: visible numbers and explanatory screenshots. Documentation: clear navigation and a quiet reading column.
- Keep 16px/24px/32px page gutters across mobile/tablet/desktop, existing `max-w-7xl`, warm backgrounds, rounded shared surfaces, and subtle elevation. Static cards remain still.
- All grids use shrinkable content columns; long translations, URLs, link labels, headings, and captions wrap. At 200% text, navigation can use the mobile menu and reading columns can stack.
- Use Heroicons/SVG for structural icons. Decorative icons are hidden from assistive technology; images have meaningful localized alt text or are explicitly decorative.
- Use real links for navigation; preserve the shared modal/button contracts for existing actions. Reading a guide never mutates farm, listing, payment, or account data.
- Hash destinations need a header offset and usable keyboard focus. Topic changes update title and focus a meaningful heading; Back/Forward restores reading position appropriately.

## 9. Frontend architecture and data flow

Keep the existing Atomic Design dependency direction and Composition API. Content is trusted, checked-in, localized data rendered through Vue interpolation and existing primitives; do not introduce `v-html`, a Markdown runtime, or an extensible content engine.

### Proposed responsibility boundaries

| File or area | Responsibility |
| --- | --- |
| `pages/public/HomePage.vue` | Home composition and reactive metadata |
| `organisms/HeroSection.vue` | Only the landscape opening; existing assets/preload contract retained |
| `organisms/HomePurposeSection.vue` | What YieldGrid is and the three sourced needs |
| `organisms/HomeVisionSection.vue` | Mission statement and audience starting actions |
| `pages/public/FeaturesPage.vue` | Grouped feature catalog and guide links |
| `pages/public/HowItWorksPage.vue` | Shared start and farmer/buyer journey composition |
| `organisms/TutorialJourney.vue` | A repeated semantic sequence with expected results and optional screenshots |
| `pages/public/DocumentationPage.vue` | Index or selected known article, unknown-topic state, page metadata |
| `organisms/DocumentationNavigation.vue` | Shared desktop/mobile topic list and current-topic indication |
| `organisms/DocumentationArticle.vue` | Fixed article structure for steps, notes, troubleshooting, and related guides |
| `constants/platformFeatures.js` | Existing feature identities/icons/locale keys plus small group/guide references |
| `constants/userGuides.js` | Stable topic/section/journey IDs, order, roles, links, and screenshot references |
| `locales/{en,tl,ceb}.js` | All user-facing copy; approved catalog structure retained |
| `router/index.js`, `AppNavbar.vue`, `AppFooter.vue` | Public routes/navigation and narrowly scoped reading navigation behavior |

New organisms are justified by distinct responsibilities or repeated rendering, not by a requirement to create a component for every section. The implementation plan may fold a small single-use section into its page if it remains cohesive and within project size rules. Reuse AppCard/AppButton/PageHeader/NavLink/AppModal where their contracts fit. Use ordinary links and native details/summary for simple read-only navigation.

The article structure is deliberately fixed: introduction, prerequisites, task sections, notes/recovery, and related links. It has no plugin registry, arbitrary block types, or remote content. Known topic IDs resolve to known content; neither route parameters nor translated strings become raw HTML or arbitrary component names.

State is limited to the current route/topic and existing locale/auth state. No new Pinia store or content API. Dynamic workflow links that require a farm/plot/user ID must lead to the existing selector/index or derive an authenticated own-profile destination safely; never hardcode another user's ID or an example record ID.

### Metadata and reading navigation

Use the installed `@unhead/vue` with reactive localized titles/descriptions and appropriate Open Graph text for Home and new routes. Set the document language from the supported active locale and test locale changes. Use confirmed deployment origins if canonical links are added; do not guess them. No SSR migration, sitemap infrastructure, or structured-data project is part of this change.

Handle public content route changes, fragments, and saved positions without disrupting the dashboard's `#main-content` scrolling. Coordinate hash scrolling with lazy route rendering/page transitions, rather than arbitrary sleep timers. Browser checks must include direct hash loads and navigation from a scrolled article.

Static articles have no request loading/error/empty states. Preserve existing auth/bootstrap behavior. Screenshots reserve space; failures must leave complete text instructions. Unknown topics and invalid navigation have explicit recovery as described above.

## 10. Verification and acceptance

The detailed implementation plan must turn these contracts into concrete changes and commands. This specification does not claim any test has passed on the proposed implementation.

### Existing coverage that must move with the content

- `e2e/Public/design.spec.js` currently requires all eight feature headings/descriptions on Home and About in every locale. Move the Home inventory assertions to Features and assert Home's new purpose/vision content; retain About coverage.
- `e2e/Public/navigation.spec.js` contains the exact current Home headline and a button-role Get Started selector. Update the intended semantic/copy changes and preserve real navigation assertions.
- `e2e/Public/language-switch.spec.js` depends on About navigation in desktop/mobile layouts. Keep it reachable and add the new pages.
- `organisms/__tests__/AppNavbar.spec.js` checks Home current state and Contact-before-auth order; adapt deliberately to the approved navigation while keeping keyboard/profile/logout behavior covered.
- Preserve and extend pending `AppFooter.spec.js` and `apiDocs.spec.js`; API Docs remains external and user Documentation remains internal.
- Maintain locale key/placeholder parity and compile every catalog entry, including literal special characters.

### Required checks

1. All public routes and known guide topics load directly without requiring authentication; unknown topics recover; existing role/auth guards remain intact.
2. All eight features appear on Features, and Home no longer repeats the full grid. About remains intact.
3. Farmer/buyer tutorials link to actual routes and detailed topics; manual listing remains explicitly independent of analysis; prerequisites and outcomes match current code.
4. All three locales have complete copy, accessible names, captions, metadata, and working topic/fragment links. Language switching does not lose the article.
5. Keyboard users can operate navigation, mobile contents, role anchors, guide links, and the existing menu. Main/heading focus and sticky offsets work on direct loads and transitions.
6. Check 360, 390, 768, 1024, 1280, and 1440px; 100%/200% text; long translations; short-height menus; reduced motion. No unintended horizontal overflow or second mobile header row.
7. Small meaningful unit tests cover guide ID/link integrity, unknown topic behavior, reactive metadata/locale behavior, and modified navigation contracts. Do not mirror static markup in redundant tests.
8. Run frontend lint checks, source and e2e/config Prettier, full Vitest, production build, and full Chromium/mobile Playwright. Run Firefox/WebKit shared/auth smoke plus targeted public reading-navigation behavior.
9. Generate/review/replay Linux baselines in the pinned Ubuntu 24.04 / Playwright 1.63.0 Noble environment from `frontend/README.md`. Add desktop/mobile Features, How It Works, Documentation index, and one representative article; review Home and every existing public page snapshot affected by navbar/footer.
10. Use deterministic API fixtures and synthetic screenshot data. Wait for real fonts/images; reset window and `#main-content` scroll before captures. Inspect every changed image/diff; replay without snapshot updates and then run the full browser suite.

Public chrome affects existing Home/About/Contact/marketplace/mobile-menu baselines. It does not inherently alter dashboard/auth chrome. Sweep all of `frontend/e2e` for stale routes, exact copy, feature assertions, and selectors before claiming completion. Baseline regeneration cannot serve as proof of correctness by itself.

## 11. Review and implementation handoff

The written-spec review should resolve any requested changes to page ownership, navigation, article inventory, or message. After approval, write the detailed implementation plan at `docs/superpowers/plans/2026-10-12-public-information-and-guides.md` using the writing-plans workflow.

The plan must specify exact files, ordered independently verifiable tasks, content/translation work, screenshot fixtures/assets, regression ownership, commands, and acceptance evidence. Shared routes/content IDs/navigation need a single owner before independent page tasks begin. Preserve the pending footer/API Docs changes, and inspect the current revision again before implementation.

Planning does not include deployment, app implementation, or claims of completed UI verification. New marketing claims about provider coverage, government affiliation, lender acceptance, legal insurance rules, or measured farmer outcomes require separate evidence; this design only describes verified app behavior and the creator's stated aspiration.
