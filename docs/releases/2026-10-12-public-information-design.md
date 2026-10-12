# YieldGrid public information design description

**Date:** 2026-10-12  
**Direction:** Modern Minimalist Organic Web Design, using the approved Field & Linen system.  
**Audience:** Filipino farmers and buyers, including first-time visitors and people learning the app on a phone.

## Design intent

Help a visitor answer four questions in a clear order: What is YieldGrid? What can it help me do? How do I begin? Where can I find detailed instructions? The experience moves from a warm introduction to practical information and then to the appropriate existing app flow.

The homepage speaks to the creator's vision of making farmers' everyday lives better. It describes useful tools and supported workflows without presenting future aspirations as achieved results. Sources explain real agricultural needs; they do not imply government endorsement.

## Information architecture

| Destination   | Visitor need                                         | Design treatment                                                                             |
| ------------- | ---------------------------------------------------- | -------------------------------------------------------------------------------------------- |
| Home          | Understand the app and its purpose                   | Landscape opening, short definition, editorial need/response rows, vision and audience links |
| Features      | See the available tools                              | Three groups of calm capability cards with audiences, scope notes and guide links            |
| How It Works  | Follow a task from beginning to end                  | Shared account setup, separate farmer/buyer anchors and ordered journeys with outcomes       |
| Documentation | Find detailed instructions or recover from a problem | Grouped topic navigation, readable articles, prerequisites, notes and recovery steps         |
| About         | Understand the broader purpose                       | Existing purpose, principles and feature summary retained                                    |
| API Docs      | Read the technical API reference                     | Explicit separate external destination                                                       |

This division gives every page a clear job. Detailed feature cards move off Home into Features; the introduction gains a definition, useful farming context and the founder's vision. Tutorials provide a route through tasks, while documentation supports deeper reading and troubleshooting.

## Visual language

- **Moss** carries the brand, primary actions, active links and growth-oriented emphasis.
- **Harvest** remains available for restrained accents and existing financial/attention states.
- **Soil** provides warm structural depth, especially the existing footer.
- **Dew** supports informational context where the shared system uses it.
- **Stone** supplies warm backgrounds, text and quiet dividers; white surfaces keep articles and cards readable.

Actual palette values remain centralized in `frontend/src/constants/designTokens.js`. No replacement palette or raw template colors were introduced.

Self-hosted **Lora** gives page and section headings a warm editorial character; **Inter** keeps body text and controls practical. Clear heading order, normal body weight, relaxed line height and generous space carry the hierarchy. Eyebrows provide small orientation labels rather than competing with headings.

Shared cards use soft shadows and rounded surfaces. Static cards remain still. Buttons and links provide restrained feedback, with reduced-motion support. The existing hero wave separates major public sections; operational help uses whitespace and simple dividers. Additional decoration does not compete with instructions.

## Home composition

### 1. Landscape introduction

The retained farm image fills the opening, with a contrast scrim behind the headline and definition. The headline is **“Practical tools. Stronger farming futures.”** Get Started is the primary next step; How It Works provides a learning entry. Existing responsive image loading, mobile scrolling and reduced-motion behavior are preserved.

### 2. What YieldGrid is

A white editorial section pairs a strong left-hand heading with a concise explanation and Features link. On smaller screens the two columns stack naturally. The content describes connected growing-season work instead of listing every tool immediately.

### 3. Practical farming needs

Three divided rows pair a need with the app's relevant response:

| Need              | Response shown                                                                      | Context source            |
| ----------------- | ----------------------------------------------------------------------------------- | ------------------------- |
| Reach buyers      | Harvest listings, buyer demands and eligible trade conversations                    | World Bank                |
| Understand prices | Price Guide with source/freshness context and explicit estimate limits              | Department of Agriculture |
| Organize planning | Plot boundaries, soil details, advice and weather context alongside local knowledge | DOST-PAGASA               |

Small structural icons and quiet rules make the rows easy to scan. Source links sit beside the claim they support. No invented impact figures, testimonials or guaranteed earnings appear.

### 4. Vision and next steps

The vision states the aspiration to improve everyday life for Filipino farmers. Farmer and buyer links lead to their tutorial sections, and Browse Market remains available. This turns the mission into a practical next action without forcing every visitor to register first.

## Features composition

Eight existing capabilities are grouped into **Plan & grow**, **Trade & connect**, and **Farm support**. Cards identify the audience, explain the capability and give a short scope note. A consistent Read the Full Guide link opens the relevant documentation section.

Cards form responsive columns with equal spacing. Reading order stays logical when they stack. Descriptions are not truncated; large text and long translated labels wrap. About's summary remains available, so moving Home's inventory does not remove broader product coverage.

## Tutorial composition

The page begins with account/role setup and links to the shared registration, profile and language guidance. Farmer and buyer anchors help readers jump to the right part of a longer page.

Each journey is an ordered sequence. A step has a clear title, a concrete instruction, its expected result and a full-guide link. The four paths are independently useful: planning a crop, selling an existing harvest, buying a harvest and requesting a crop. The manual-selling path does not imply that mapping or AI analysis is compulsory.

Four synthetic app examples show recognizable controls at readable sizes. Captions distinguish an unsaved draft or checkout review from a completed action. Examples use the English interface, explicitly labelled with localized captions/alternative text; prose is available in all three languages. If an image is unavailable, written instructions and recovery text remain visible.

## Documentation composition

The index groups 13 topics under starting, growing, trading and support. On desktop, a compact contents column sits beside the article; on mobile, a native disclosure exposes the same navigation without dominating the reading area.

Articles use a readable width of approximately 70 characters, one page heading and consistent section headings. Prerequisites come before the procedure. Ordered instructions show what to do and what happens next; limitations and recovery advice appear beside the relevant workflow. Related guides help the reader continue.

Audience labels explain relevance, not access restrictions. A buyer can read farmer instructions and a guest can read payment guidance. Existing dashboard actions appear only when appropriate for the signed-in role; reading never mutates a business record. Unknown topics provide a clear return to the index.

## Navigation and interaction

The public shell exposes Features, How It Works, Documentation and About. Home is available through the brand and the mobile/footer links; Contact remains in the drawer/footer. API Docs keeps its distinct technical label and protected external link behavior.

At desktop widths the navbar measures whether the real labels and account/language controls fit. If they do not, it uses the existing drawer. Narrow headers remain one row; labels are not squeezed into smaller fonts. Open menus keep their focus behavior during language changes, with profile access preserved for both roles.

Section links account for the sticky header. Fresh articles focus their heading, repeated Back/Forward visits restore their reading position, and rapid navigation cancels stale focus work. Actual history entries remain distinct when Forward history is replaced. User interaction takes precedence over delayed reading focus.

## Accessibility and localization

- Semantic headings, navigation landmarks, native links/disclosures/dialogs and the existing skip link.
- At least 44px interactive targets, visible keyboard focus and shared control semantics.
- Responsive checks at 360, 390, 768, 1024, 1280 and 1440px, including 200% text and long translations.
- English, Tagalog and Cebuano copy with matching guide structure and actual localized control names.
- Reactive document language and page metadata; locale changes preserve the current article and section.
- Reduced-motion behavior; no geometry animation that interferes with the measured header layout.
- Complete written guidance, meaningful image alternatives and a usable image-failure state.

## Design verification and boundaries

The accepted pixels use Linux Chromium on Ubuntu 24.04 / Playwright 1.63.0 Noble, fixed fixtures and self-hosted fonts. Every changed/new public baseline and available diff was inspected, including the full mobile tutorial flow and four example captures. Existing About, Contact and marketplace changes were checked for intended shared navigation changes. Cross-engine smoke verifies behavior separately; it does not create alternative pixel baselines.

The [implementation plan](../superpowers/plans/2026-10-12-public-information-and-guides.md) records final test counts and reviewed fixes. The [release description](2026-10-12-develop-to-main.md) records deployment considerations. Live provider availability and native-speaker editorial certification are outside this frontend verification.
