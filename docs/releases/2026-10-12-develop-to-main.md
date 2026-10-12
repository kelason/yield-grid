# Release description — `develop` → `main`

**Date:** 2026-10-12  
**Suggested PR title:** `feat: add public learning pages and demo account seeding`  
**Verified integration revision:** `5fe158d` (public pages merged into `develop`, preserving demo seeding).  
**Comparison base:** remote `main` at `8248b49` (`Merge pull request #61 from kelason/develop`).

This document is ready to copy into the `develop` → `main` PR description. It describes the remaining diff against that remote base. The earlier platform/backend release is already on `main`. A subsequent demo-account seeder is included in this comparison. The October 10 release notes are historical; their test results do not describe this revision.

## Summary

YieldGrid now explains what the app is, why it exists, which tools it provides, and how farmers and buyers can use them. The homepage introduces the purpose and founder's vision. A dedicated Features page owns the complete capability inventory; How It Works presents practical journeys; Documentation provides detailed app guides. All new written content supports English, Tagalog and Cebuano.

## Public destinations

| Route                   | Result                                                                                             |
| ----------------------- | -------------------------------------------------------------------------------------------------- |
| `/`                     | Reworked introduction, farming context, creator vision and farmer/buyer next steps                 |
| `/features`             | Eight capability areas organized into three groups, each linked to a detailed guide                |
| `/how-it-works`         | Shared account setup and four independent step-by-step journeys                                    |
| `/documentation`        | Public help index grouped into getting started, growing, trading and support                       |
| `/documentation/:topic` | Detailed topic article, stable section links, related guides and appropriate dashboard entry links |

### Homepage: purpose, context and vision

- Retains the existing farm-landscape hero, responsive image preload, text scrim, mobile/reduced-motion background behavior and white wave divider.
- Introduces the headline **“Practical tools. Stronger farming futures.”** and explains YieldGrid as a shared place for crop planning, harvest trading and farming knowledge.
- Replaces Home's full feature-card inventory with an editorial purpose section and three practical needs: reaching buyers, understanding price context and organizing crop-planning decisions.
- Adds a clearly aspirational vision: helping make everyday life better for Filipino farmers through understandable information, easier buyer access and manageable decisions.
- Offers Get Started, How It Works, Explore Features, farmer/buyer tutorial links and Browse Market as useful next steps.
- Keeps About's existing purpose and feature summary.

Context links support the farming needs, rather than claiming measured YieldGrid outcomes or official partnerships: [World Bank market-access context](https://www.worldbank.org/en/results/2026/04/07/scaling-rural-transformation-in-the-philippines-connecting-farmers-to-markets-jobs-and-opportunity), [Department of Agriculture price monitoring](https://www.da.gov.ph/price-monitoring/), and [DOST-PAGASA agricultural weather guidance](https://www.pagasa.dost.gov.ph/ten-day-regional-agri-weather).

### Features: complete capability inventory

| Group           | Capabilities                                                       |
| --------------- | ------------------------------------------------------------------ |
| Plan & grow     | AI Crop Advisor; Farm & Plot Planning; Weather Insights            |
| Trade & connect | Harvest Marketplace; Buyer Demands & Offers; Community & Messaging |
| Farm support    | Crop Insurance & RSBSA; Farmer Trust Score                         |

Each area includes its existing explanation, intended audience, an honest scope note and a relevant documentation section. Copy distinguishes recommendations from guaranteed yields, reference prices from guaranteed sale prices, internal trust scores from loan approval, and insurance records from external eligibility decisions.

### How It Works: four complete journeys

| Journey        | Steps | Coverage                                                                                                        |
| -------------- | ----- | --------------------------------------------------------------------------------------------------------------- |
| Plan a crop    | 7     | Create a farm, draw and save a plot, request/read crop advice, decide about a forward contract, manage listings |
| Sell a harvest | 4     | Start a manual listing, optionally associate its source, review/publish and manage it                           |
| Buy a harvest  | 4     | Browse/filter, inspect a listing, review checkout and track the purchase                                        |
| Request a crop | 5     | Prepare an address, post a demand, decide offers, follow payment/delivery and complete/settle                   |

Every step explains the action and expected result, and links to the detailed article. Manual selling explicitly works without a saved farm or AI recommendation. Guest entry links lead to registration; dashboard entry links match the signed-in user's role.

Four real app screenshots use synthetic fixtures: plot setup, crop recommendations, a manual listing draft and cash checkout review. Captions identify examples and English interface text; no illustrated record, contract or payment was submitted. Written steps remain usable if an image fails to load. Reproduction instructions are in `frontend/README.md`.

### Documentation: 13 detailed topics

1. Getting started: roles, registration, email verification, sign-in and recovery.
2. Profile, addresses and language.
3. Farms and plots: drawing, saving, prerequisites and map recovery.
4. Crop Advisor: analysis, results, limitations and contract publication.
5. Selling harvests: manual listings, optional sources and listing management.
6. Buying harvests: filtering, inspection, checkout and purchase tracking.
7. Payments and purchases: online/cash flows, receipts, approvals and balances.
8. Demands and offers: requests, offers, acceptance, delivery and completion.
9. Price Guide: source/freshness context and DA versus AI estimates.
10. Verification and farmer trust score.
11. Insurance and RSBSA: preparation, external submission and recorded follow-up.
12. Community and messaging: discussions and eligible trade conversations.
13. Troubleshooting and support.

Articles contain prerequisites, ordered actions/results, relevant limitations, recovery advice and related destinations. All topics are publicly readable, including guides for the other role. Unknown topics recover to the help index; invalid section links recover to a usable article heading.

## Demo-account seeding (concurrent `develop` change)

Commit `e8f87d9` adds `DemoUsersSeeder` to the existing database seeding sequence. It creates or updates a farmer, buyer and admin account by email, applies their intended roles and a shared known password, and verifies their email addresses if they were unverified. Re-running the seeder resets those accounts' passwords and role/name fields; it does not merely insert missing users.

This is separate from the public-page implementation and introduces no migration. Do not run the standard seed sequence against production accounts without reviewing this behavior. Production deployments for this PR need the frontend build, not a demo seed run. Syntax/format/static results are recorded with verification below; database seeding was not exercised against a live database.

## Navigation, accessibility and reading behavior

- Adds Features, How It Works and Documentation to the public navbar and footer. API Docs remains a separate external reference, derived from `VITE_API_URL`, with new-tab protection.
- Preserves login, registration, profile access, logout confirmation and role/email-verification guards.
- Uses a measured desktop-header fit check; long translations or enlarged text fall back to the existing drawer. The header stays on one row at narrow mobile widths.
- Keeps language changes inside the open mobile dialog and returns focus to the language control after selection.
- Updates document language, page title, description and Open Graph text with the active locale.
- Provides desktop contents navigation, a mobile native disclosure, readable article widths, visible focus, semantic headings, large controls and reduced-motion behavior.
- Restores the correct reading position across repeated topics, hashes and Back/Forward. Actual history entries have distinct identities even when a new push discards Forward history. Pending work is cancelled during rapid navigation or user interaction; dashboard scrolling is preserved.

## Design description

The public experience follows the approved Modern Minimalist Organic / Field & Linen direction: self-hosted Lora headings, Inter body text, warm stone surfaces, moss actions, soft rounded cards and generous whitespace. Static cards stay still; decoration is restrained, with the existing landscape/wave reserved for the homepage opening.

| Page          | Design choice                                                                                      | Purpose                                                              |
| ------------- | -------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------- |
| Home          | Landscape opening, short definition, divided need/response rows, founder vision and audience links | Explain the app and its relevance before presenting every capability |
| Features      | Three responsive groups of capability cards with audience labels and scope notes                   | Make tools easy to compare and connect them to useful instructions   |
| How It Works  | Shared start, farmer/buyer anchors, ordered steps, explicit outcomes and readable examples         | Help visitors complete a specific task with the actual app controls  |
| Documentation | Desktop contents column, mobile disclosure and approximately 70-character article measure          | Support longer reading, direct section links and troubleshooting     |

The design supports visible keyboard focus, 44px targets, semantic headings, 200% text, long translations and reduced motion. Header navigation falls back to the existing drawer when the real labels cannot fit, preserving a single mobile row. Examples supplement complete instructions rather than replacing them. The separate design document provides the full rationale and page-by-page description.

## Implementation and regression coverage

The implementation uses the existing Vue router, i18n catalogs, auth state, Unhead and shared Field & Linen components. New UI remains in Atomic Design directories. Trusted checked-in text renders through Vue interpolation. Tutorials reuse guide instructions/results so the two destinations stay consistent. The public-page work adds no runtime dependency, backend endpoint, migration, CMS or search service. The separate seeder change is described above.

Coverage includes guide/locale shape and compilation, section/route integrity, metadata, role-appropriate links, image failure, invalid URLs, history entry reuse, HTTP development origins, menu focus and responsive layouts. Existing farming fixtures are shared with screenshot reproduction. Visual captures explicitly reset window/dashboard scroll before taking pixels.

## Verification

| Check                                       | Result                                                                                          |
| ------------------------------------------- | ----------------------------------------------------------------------------------------------- |
| ESLint/Oxlint                               | Passed (`npm run lint:check`)                                                                   |
| Source and e2e/config Prettier              | Passed                                                                                          |
| Full Vitest                                 | 128 files / 745 tests passed; repeated successfully after the `develop` merge                   |
| Production build                            | Passed before and after the `develop` merge; existing chunk-size/dynamic-import warnings remain |
| Focused responsive/navigation               | 30 passed on Chromium desktop/mobile                                                            |
| Reviewed Linux visual replay                | 43 passed / 1 intentional skip                                                                  |
| Full Chromium/mobile suite                  | 288 passed / 6 intentional skips, without updates or retries                                    |
| Firefox/WebKit shared/auth and guides smoke | 64 passed                                                                                       |
| Demo seeder syntax/Pint/PHPStan L6          | Passed on both seeder files                                                                     |

Linux pixels use Playwright 1.63.0 / Ubuntu 24.04 Noble with the README's pinned official base, self-hosted fonts, Linux dependencies and synthetic fixtures. Host checks use supported Node 24.19.0. Reviewed baseline changes cover Home, About, Contact, marketplace, mobile menu and the four new page/article states; two confirmation backgrounds were updated after proving the scroll-reset difference, with dialog pixels unchanged.

All 13 topics, four journeys and eight feature areas were checked against current app controls and permissions. No actionable introduced review finding remains open. Six full-suite skips are deliberate desktop/mobile scope cases, not suppressed failures. Full backend tests, live database seeding, real transactions/provider responses and native-speaker editorial certification were not performed.

## Deployment checklist

- [ ] Review CI for the pushed `develop` revision and open the `develop` → `main` PR with this description.
- [ ] After approval, merge the PR and deploy the production frontend build using the existing process.
- [ ] Include `public/images/guides/*-en.png` and the retained landscape/font assets in deployment.
- [ ] Confirm the SPA fallback serves direct `/documentation/:topic` URLs.
- [ ] Confirm `VITE_API_URL` resolves API Docs to the intended API host's `/docs`.
- [ ] Review demo-account behavior before any database seed command; this frontend deployment does not require seeding.
- [ ] Check guest Home → Features → tutorial → article, direct section links, Back/Forward and language switching.
- [ ] Check farmer/buyer dashboard entry links, existing profile/menu behavior and mobile header fit.

This diff requires no database migration or backend configuration change. The demo seeder is a separate optional database operation with the account-update behavior described above. It does not certify live payment, insurer or map-provider availability. Translations have structural/control-label checks and manual content review; native-speaker editorial review remains useful.

## Related documents

- [Design description](https://github.com/kelason/yield-grid/blob/develop/docs/releases/2026-10-12-public-information-design.md)
- [Approved specification](https://github.com/kelason/yield-grid/blob/develop/docs/superpowers/specs/2026-10-12-public-information-and-guides-design.md)
- [Implementation plan and execution evidence](https://github.com/kelason/yield-grid/blob/develop/docs/superpowers/plans/2026-10-12-public-information-and-guides.md)

Merge and deployment follow PR review and the existing release process.
