# YieldGrid Project Rules

## Frontend Design Direction and Redesign Plan

The frontend direction is **Modern Minimalist Organic Web Design**: calm natural
colors, clear hierarchy, generous whitespace, restrained decoration, rounded
surfaces, Lora headings, and Inter body text. Preserve Atomic Design and existing
business behavior while improving component reuse and accessibility.

- Read `docs/superpowers/specs/2026-10-07-modern-organic-redesign.md` for the frontend
  assessment, approved **Field & Linen** palette, component contracts, and design rules.
- Follow `docs/superpowers/plans/2026-10-07-modern-organic-redesign.md` for the ordered
  implementation tasks and Playwright coverage. Start with shared foundations,
  then farmer and buyer dashboards, then remaining route families.
- The design direction is requested by the user; the Field & Linen palette and detailed
  implementation plan were approved for execution on 2026-10-07. Follow the
  specification and the plan’s recorded verification results.
- Reuse and improve `AppButton`, `AppCard`, `AppInput`, `AppSelect`, `FormField`,
  `AppModal`, `ConfirmModal`, `SkeletonCard`, and `EmptyState`. Make confirmation
  dialogs compose the shared modal and button primitives. Add only the small
  textarea/spinner/skeleton/status compositions specified in the plan.
- Keep domain actions and confirmation ownership in pages/composables. Preserve
  native control semantics, validation limits, event contracts, and null safety.
- Render request errors inside the active native dialog; a global toast outside
  it is inert and cannot provide the only failure feedback. Preserve failed drafts
  and provide a retry for failed data loads before showing an empty state.
- Mobile navigation must expose the authenticated user’s own profile for both
  roles, including access to address management.
- Use existing `moss`, `harvest`, `soil`, `dew`, and `stone` token names with the
  approved palette. Keep actual color values centralized; never introduce raw
  colors in templates. Use SVG/Heroicons for structural UI icons.
- Prefer understated surfaces and subtle elevation. Use decorative gradients,
  glow, glass, and organic dividers sparingly; static cards should not lift on hover.
- Align the actual input/select boxes in multi-column rows, including wrapped
  labels and label help buttons. Keep shared control font/line heights instead of
  shrinking individual fields. Conditional action columns keep a consistent width
  and wrap within their card at smaller viewports.
- Every redesigned flow needs responsive and keyboard verification. Follow the
  plan's Playwright fixtures and visual-baseline policy; do not approve screenshot
  changes merely by regenerating them.
- Keep this file and `.agents/skills/yieldgrid-design-system/SKILL.md` synchronized.
  Palette values and shadows live in `frontend/src/constants/designTokens.js`;
  Tailwind and JS charts/maps consume that source.
- Browser checks serve the production build. Reviewed pixels use Chromium on
  Ubuntu 24.04 / Playwright 1.63.0 Noble, self-hosted Inter/Lora and fixed fixtures.
  Run Firefox/WebKit shared/auth smoke separately. See `frontend/README.md`
  for environment and baseline commands. Verify real map tiles separately.

## Code Review Guidelines

- For local reviews, use `/review` with uncommitted changes or the branch diff against the intended PR base. Review the latest revision after fixes.
- Review only issues introduced or worsened by the selected changes; trace related callers, policies, Actions, repositories, and tests before reporting a finding.
- Prioritize authorization/IDOR, server-side input limits, payment amounts and webhook idempotency, concurrent marketplace quantity updates, migration data loss, queue retries, and Vue null/XSS failures.
- Apply the architecture, confirmation, accessibility, design, and size rules below. Eloquent-in-Domain is intentional; do not propose an entity/mapper rewrite.
- Report actionable findings with a priority, precise file and line, triggering input or scenario, actual versus expected behavior, and the smallest appropriate fix. Separate explicit project-rule violations from runtime bugs.
- Prefer existing helpers and native features. Avoid speculative abstractions, unrelated refactors, and formatting comments already covered by linters.
- Verify existing PR Agent findings against the current code before repeating them; include any new evidence or unresolved issue.
- Reviews report findings without editing files, posting GitHub comments, or merging unless requested. State when no actionable issues were found and identify any checks that could not be run; never imply unrun tests passed.

## Architecture: Domain-Driven Design (Backend)

All backend code follows DDD with bounded contexts. **Never** use standard Laravel structure.

### Namespace Conventions

| Layer | Pattern | Example |
|-------|---------|---------|
| Models | `App\Domain\{Context}\Models\*` | `App\Domain\Farming\Models\Plot` |
| DTOs | `App\Domain\{Context}\DTOs\*` | `App\Domain\CropRecommendation\DTOs\CropRecommendationData` |
| Actions | `App\Domain\{Context}\Actions\*` | `App\Domain\Farming\Actions\CreatePlotAction` |
| Enums | `App\Domain\{Context}\Enums\*` | `App\Domain\Marketplace\Enums\ContractStatus` |
| Events | `App\Domain\{Context}\Events\*` | `App\Domain\CropRecommendation\Events\AnalysisCompleted` |
| Controllers | `App\{Context}\Controllers\*` | `App\Farming\Controllers\PlotController` |
| Form Requests | `App\{Context}\Requests\*` | `App\Farming\Requests\StorePlotRequest` |
| API Resources | `App\{Context}\Resources\*` | `App\Farming\Resources\PlotResource` |
| Services | `App\Infrastructure\Services\*` | `App\Infrastructure\Services\WeatherService` |
| Jobs | `App\Jobs\*` | `App\Jobs\AnalyzePlotJob` |
| Policies | `App\Policies\*` | `App\Policies\PlotPolicy` |

### Bounded Contexts

`AIPlanner`, `Auth`, `Chat`, `Community`, `Contact`, `CreditScoring`, `CropRecommendation`, `Farming`, `Insurance`, `Marketplace`, `Shared`, `Users`

### Domain Layer Purity

Eloquent-in-Domain is a deliberate architecture choice (ActiveRecord-style
DDD), not a violation: models stay in `App\Domain\{Context}\Models` and
Actions may persist via Eloquent. Do NOT propose clean-entity/mapper
restructuring. What the Domain layer must NEVER do is reach outside the
application:

| Concern | Rule |
|---------|------|
| External HTTP/APIs | No `Http` calls, SDK clients, or third-party API logic in models or Actions — put them in `App\Infrastructure\Services\*` and inject the service |
| API response caching | Lives with the Infrastructure service that fetches the data, using `*Cache` TTL constants |
| Repositories | If a `*RepositoryInterface` exists for the aggregate, Actions and Controllers MUST query through it — extend the interface for new shapes (e.g. locked reads) instead of bypassing it with direct Eloquent |
| New aggregates | Mirror the Purchase precedent: interface in `App\Domain\{Context}\Repositories\*`, Eloquent implementation in `App\Infrastructure\{Context}\Repositories\*`, bound in `AppServiceProvider` |
| Cache keys | When moving cached logic, keep existing cache-key formats stable so deployed cache rows keep working |

```php
// ✅ ALWAYS: external APIs behind an injected Infrastructure service
$geo = app(ReverseGeocodeService::class)->reverseGeocode($lat, $lon);
$purchase = $this->purchaseRepository->findLockedById($id);

// ❌ NEVER: HTTP calls or bypassed repositories in Domain code
$response = Http::get('https://api.example.com/...'); // inside a model or Action
$purchase = Purchase::where('id', $id)->lockForUpdate()->firstOrFail(); // when the repository exists
```

### Backend Anti-Patterns (NEVER DO)

- ❌ `App\Models\*` — always `App\Domain\{Context}\Models\*`
- ❌ `App\Http\Controllers\*` — always `App\{Context}\Controllers\*`
- ❌ `App\Http\Requests\*` — always `App\{Context}\Requests\*`
- ❌ `App\Http\Resources\*` — always `App\{Context}\Resources\*`
- ❌ Blade templates, Livewire, or Inertia — frontend is a separate Vue SPA
- ❌ Business logic in controllers — extract to Actions or Services
- ❌ Raw queries without parameter binding
- ❌ `Http::`, external SDKs, or third-party API calls in models or Actions — always `App\Infrastructure\Services\*`
- ❌ Direct Eloquent queries that bypass an existing `*RepositoryInterface`

---

## Architecture: Atomic Design (Frontend)

All Vue components follow Atomic Design hierarchy. **Never** use feature-folder organization.

| Level | Directory | Purpose | Example |
|-------|-----------|---------|---------|
| Atoms | `src/components/atoms/` | Smallest reusable UI elements | `AppButton.vue`, `AppCard.vue`, `AppInput.vue` |
| Molecules | `src/components/molecules/` | Compositions of atoms | `FormField.vue`, `SearchBar.vue`, `StatCard.vue` |
| Organisms | `src/components/organisms/` | Complex, self-contained UI sections | `HeroSection.vue`, `DashboardStats.vue` |
| Templates | `src/components/templates/` | Page layouts (shell, sidebar, nav) | `DashboardLayout.vue`, `AuthLayout.vue` |
| Pages | `src/pages/` | Route-level views composing templates + organisms | `DashboardPage.vue`, `LoginPage.vue` |

### Composables & State

- Composables: `src/composables/use*.js` — reusable stateful logic
- Stores: `src/stores/*Store.js` — Pinia stores for global state

### Frontend Anti-Patterns (NEVER DO)

- ❌ `components/<feature>/` folders — always use `atoms/`, `molecules/`, `organisms/`, `templates/`
- ❌ Options API — always use `<script setup>` with Composition API
- ❌ Raw hex/rgb colors in templates — always use Tailwind design tokens (`moss-*`, `harvest-*`, `soil-*`, `dew-*`, `stone-*`)
- ❌ Inline styles in Vue templates

---

## Code Quality & Linting

### PHP (Backend)

- **Formatter**: Laravel Pint (PSR-12) — `./vendor/bin/pint`
- **Static Analysis**: PHPStan level 6 — `./vendor/bin/phpstan analyse --level=6`
- Use `declare(strict_types=1)` in every PHP file
- Type hint ALL method parameters and return types
- Use PHP 8.2+ features: readonly properties, enums, typed properties, named arguments
- Mark classes as `final` unless explicitly designed for extension
- Use Eloquent eager loading — never allow N+1 queries

### Vue/JS/TS (Frontend)

- **Linter**: ESLint — `npm run lint`
- **Formatter**: Prettier — `npx prettier --check "src/**/*.{vue,js,ts}"`
- All code must pass both ESLint and Prettier before work is considered complete

### Function & Class Size Limits

Long functions and god classes are a code review violation. Split logic into small, single-purpose functions instead of letting one function grow.

- **Backend (PHP)**: functions/methods must be **≤30 lines**, classes must be **≤300 lines**
- **Frontend (Vue/JS)**: functions must be **≤30 lines**; components showing god-component symptoms (~200+ lines) must be decomposed into smaller components/composables
- When a function approaches the limit, extract private methods (grouped by context or step) — never keep appending to it
- When a class approaches the limit, extract a new Action, service, or composable — never keep adding methods to it

```php
// ✅ ALWAYS: small functions composed together
public function execute(User $user): array
{
    return array_merge(
        $this->gatherFarmingMetrics($user->id),
        $this->gatherContractMetrics($user->id),
        $this->gatherVolumeMetrics($user->id),
    );
}

// ❌ NEVER: one 100+ line function doing everything
public function execute(User $user): array
{
    // ... 120 lines of farming + contract + volume queries ...
}
```

```js
// ✅ ALWAYS: extract logic into a composable
import { useCreditScore } from '@/composables/useCreditScore'
const { score, history, fetchScore } = useCreditScore()

// ❌ NEVER: 200+ lines of data-fetching logic inside <script setup>
```

### Pre-Commit Checklist

1. `./vendor/bin/pint` — PHP formatting passes
2. `./vendor/bin/phpstan analyse --level=6` — static analysis passes
3. `npm run lint` — ESLint passes
4. `npx prettier --check "src/**/*.{vue,js,ts}"` — Prettier passes
5. `php artisan test` — all tests pass
6. `npx vitest run` — frontend tests pass

### No Magic Numbers

**NEVER** use raw numeric literals, HTTP status codes, or unexplained string values inline. Extract them to named constants.

#### Backend (PHP)

- Global constants go in `App\Constants\*` (e.g., `HttpCode`, `PaginationConstants`, `PaymentConstants`)
- Feature-specific constants go in the relevant domain class or a dedicated constants class
- Use enums (`ContractStatus`, `PaymentMethod`) instead of raw strings for status/type values

```php
// ✅ ALWAYS
$amountPaid = $total * PaymentConstants::DOWNPAYMENT_PERCENTAGE;
return response()->json(['message' => '...'], HttpCode::CONFLICT);
$query->where('status', ContractStatus::SOLD->value);

// ❌ NEVER
$amountPaid = $total * 0.10;
return response()->json(['message' => '...'], 409);
$query->where('status', 'sold');
```

#### Frontend (Vue/JS)

- Global constants (HTTP codes, shared values) go in `src/constants/` (e.g., `http.js`, `pagination.js`, `payment.js`)
- Feature-specific constants (map config, timeouts) are declared as `const` at the top of the `<script setup>` block or in a dedicated constants object
- Use descriptive names: `REDIRECT_DELAY_MS`, `MAP_CONSTANTS.DEFAULT_ZOOM`, `PAYMENT_CONSTANTS.DOWNPAYMENT_PERCENTAGE`

```js
// ✅ ALWAYS
import { HTTP_STATUS } from '@/constants/http'
import { PAYMENT_CONSTANTS } from '@/constants/payment'

const REDIRECT_DELAY_MS = 2000
if (error.response?.status === HTTP_STATUS.FORBIDDEN) { ... }
const amount = total * PAYMENT_CONSTANTS.DOWNPAYMENT_PERCENTAGE

// ❌ NEVER
if (error.response?.status === 403) { ... }
setTimeout(() => { ... }, 2000)
const amount = total * 0.1
```

---

## Input Limits & Action Confirmations

Every user input must be bounded on BOTH ends, and every state-changing button must confirm. Frontend limits are UX only — the backend is the real guard and must reject what the frontend blocks.

### Frontend: Bound Every Input & Textarea

- Numeric `FormField`s get `:min` / `:max` (e.g. quantity capped at kg left); digit-capped numbers get `:maxlength` (`AppInput` clamps input, including `type="number"` which browsers ignore `maxlength` on)
- Textareas get `:maxlength` plus a live `n / MAX` counter
- Submit handlers re-validate all limits with friendly messages before calling the API (native attributes can be bypassed)
- Feature limits live as `const` at the top of `<script setup>` (e.g. `OFFER_PRICE_MAX`, `OFFER_MESSAGE_MAX_LENGTH`)

```vue
<!-- ✅ ALWAYS -->
<FormField id="offer-qty" type="number" v-model="qty"
  :min="OFFER_QTY_MIN_KG" :max="demand.remaining_quantity_kg" />
<textarea v-model="message" :maxlength="OFFER_MESSAGE_MAX_LENGTH" />
<p>{{ message.length }} / {{ OFFER_MESSAGE_MAX_LENGTH }}</p>

<!-- ❌ NEVER: unbounded input, no submit-time check -->
<FormField id="offer-qty" type="number" v-model="qty" />
```

### Backend: Enforce the Same Limits

- Every `FormRequest` rule uses `min:` / `max:` with values from a named constants class (e.g. `MarketplaceConstants::OFFER_PRICE_MAX`), never literals
- Cross-field guards that single rules can't express (e.g. `qty × price` exceeding `decimal(12,2)` column capacity) go in `withValidator()` and return **422**
- Domain guards that need current DB state (e.g. quantity vs live `remaining_quantity_kg`) go in the Action, throwing `LogicException` which the controller maps to **409 Conflict**
- Every limit gets Pest boundary tests on both sides (e.g. 99999999 → 201, 100000000 → 422; 1000 chars → 201, 1001 → 422)

### Buttons: Confirm Every State-Changing Action

- ALL buttons that create, update, delete, pay, accept, reject, withdraw, cancel, approve, or otherwise mutate state MUST open `ConfirmModal` (`components/molecules/ConfirmModal.vue`) first — no direct-fire mutations
- One shared modal per page driven by a `pendingConfirm` + `confirmConfig` pair (reference: `MyOffersPage.vue`); `type="danger"` for destructive actions
- Read-only navigation (links, collapse toggles, pagination, filters) does not confirm

```vue
<!-- ✅ ALWAYS -->
<AppButton @click="pendingConfirm = { action: 'withdraw', id: offer.id }">Withdraw</AppButton>
<ConfirmModal :is-open="pendingConfirm !== null" :title="confirmConfig.title"
  :message="confirmConfig.message" type="danger" @confirm="confirmPending" @cancel="pendingConfirm = null" />

<!-- ❌ NEVER: mutation without confirmation -->
<AppButton @click="withdrawOffer(offer.id)">Withdraw</AppButton>
```

---

## Security (OWASP Essentials)

These rules apply to ALL controller, middleware, request, and policy code:

### Access Control (A01)

- All endpoints must check authorization via policies (`$this->authorize()`)
- Scope all queries to `auth()->user()` or use policies to prevent IDOR
- Use role middleware on route groups (farmer-only, buyer-only)
- Email verification required before accessing protected features

### Injection Prevention (A03)

- Use Eloquent for all standard queries (parameterized by default)
- PostGIS spatial queries must use `?` placeholders, never string interpolation
- No `eval()`, `exec()`, or `shell_exec()` with user input

### Authentication (A07)

- Sanctum token expiration must be configured
- Rate-limit auth endpoints to prevent brute force
- Rate-limit analysis endpoint (5/hour/user via `throttle` middleware)

### Data Integrity (A08)

- Stripe webhook signatures must be verified before processing
- Implement idempotent webhook handling (duplicate detection)
- Payment amounts validated server-side, never trusted from client

### Secure Controller Pattern

```php
// ✅ ALWAYS: Policy-protected, validated, scoped
public function update(UpdatePlotRequest $request, Plot $plot): PlotResource
{
    $this->authorize('update', $plot);
    $plot->update($request->validated());
    return new PlotResource($plot);
}

// ❌ NEVER: No authorization, raw input, no validation
public function update(Request $request, int $id): JsonResponse
{
    $plot = Plot::findOrFail($id);
    $plot->update($request->all());
    return response()->json($plot);
}
```

### Vue XSS Prevention

- Use `{{ }}` interpolation (auto-escaped), never `v-html` with user/LLM content
- If `v-html` is required, sanitize with DOMPurify first

### Vue Template Null Safety

Object props with a `default` (instead of `required: true`) can still arrive as `null` when a parent binds `:prop="null"`. Every template read of a nested prop field must use optional chaining or sit inside a `v-if` guard — an unguarded `{{ farm.city }}` throws a TypeError and crashes rendering.

```vue
<!-- ✅ ALWAYS -->
<p>Zooming the map to {{ farm?.city }}</p>
<div v-if="farm?.city"><strong>{{ farm.city }}</strong></div>

<!-- ❌ NEVER: unguarded nested prop access -->
<p>Zooming the map to {{ farm.city }}</p>
```

Mirror the same defensiveness in `<script setup>` (`props.farm?.city`), and add a Vitest case rendering the component with the prop set to `null` (including mid-state transitions like a visible loading overlay).

---

## Tech Stack Reference

- **Backend**: Laravel 10+, PHP 8.2+, PostgreSQL 16 + PostGIS 3.4, Redis, Laravel Reverb
- **Frontend**: Vue 3 (Composition API), Vite, Pinia, Vue Router, Tailwind CSS
- **Payments**: Stripe Checkout + Webhooks
- **AI**: OpenAI / Anthropic APIs via queued jobs
- **Maps**: Leaflet.js + Leaflet.Draw
- **Testing**: Pest (backend), Vitest (frontend)
- **CI/CD**: GitHub Actions
- **Dev Environment**: Docker Compose (PostGIS, Redis, Mailpit)

---

## Design System: Modern Minimalist Organic Web Design

YieldGrid uses a **Modern Minimalist Organic Web Design** language throughout the entire frontend. Follow it for all new components, pages, and modifications, using the approved Field & Linen specification above for palette and migration status.

### Philosophy

> Natural warmth with practical clarity: muted earthy colors, breathing whitespace, soft shapes, readable information, and restrained motion.

### Color Palette (Tailwind Tokens)

| Palette | Token | Purpose |
|---------|-------|---------|
| Moss (greens) | `moss-*` | Primary brand: CTAs, active states, success, growth indicators |
| Harvest (ambers) | `harvest-*` | Accents: badges, financial figures, attention-needed states |
| Soil (browns) | `soil-*` | Structural: sidebar, footer, borders, earth-toned depth |
| Dew (blues) | `dew-*` | Informational: weather, AI hints, sky-inspired data |
| Stone (neutrals) | `stone-*` | Surfaces: page backgrounds, cards, text, dividers |

### Color Usage Rules

| Purpose | Token to Use |
|---------|-------------|
| Primary CTA buttons | `bg-gradient-to-br from-moss-500 to-moss-600` |
| CTA hover state | `hover:from-moss-600 hover:to-moss-700` |
| Page background (public) | `bg-stone-100` |
| Page background (dashboard) | `bg-stone-50` |
| Card surface | `bg-white border-stone-200` |
| Sidebar / Footer | `bg-soil-900`; use a subtle gradient only where justified |
| Accent badges, financial figures | `harvest-*` |
| Weather / AI / Info cards | `dew-50` to `dew-100` |
| Body text (primary) | `text-stone-900` |
| Body text (secondary) | `text-stone-600` |
| Labels | `text-soil-700` |
| Text on dark backgrounds | `text-stone-300` / `text-stone-400` |
| Error states | Semantic `red-*`; use `DESIGN_STATUS_COLORS` in JS charts/maps |
| Focus rings | `focus:ring-moss-500` |

### Shape Language

- **NEVER** use sharp corners (`rounded-none`, `rounded-sm`) for any UI element
- **Inputs / Selects**: `rounded-xl`
- **Cards**: `rounded-2xl`
- **Modals**: `rounded-3xl`
- **Buttons (default)**: `rounded-xl`; pill variant: `rounded-full`
- **Badges / Tags**: `rounded-full`
- **Search bars**: `rounded-full` (organic pill)

### Typography

- **Display / Page Headings**: `font-serif` (Lora) — adds organic warmth and authority
- **Body text**: `font-sans` (Inter)
- **Page title h1**: `font-serif text-3xl sm:text-4xl font-bold tracking-tight text-stone-900`; public hero headings may reach `text-5xl`
- **Card/Section title**: `font-serif text-2xl font-bold text-stone-900`
- **Body**: `text-base text-stone-600 font-normal leading-relaxed`; reserve light weight for large supporting marketing copy
- **Labels**: `text-sm font-medium text-soil-700`
- **KPI numbers**: `text-3xl font-semibold text-moss-700`

### Shadows & Elevation

| Level | Token | Usage |
|-------|-------|-------|
| Flat | `shadow-soft` | Default cards, nav bar |
| Raised | `shadow-organic` | Hover states, modals, important cards |
| Harvest glow | `shadow-harvest-glow` | Financial/CTA accent elements |

### Motion (Micro-Animations)

All interactive elements MUST have motion feedback:

| Interaction | Animation |
|-------------|-----------|
| Button hover | `hover:scale-[1.02] transition-all duration-300`, disabled under reduced motion |
| Interactive card hover | Subtle border/elevation feedback with `transition-all duration-300`; static cards remain still |
| Modal open | `scale-95 → scale-100` with `duration-300 ease-out` |
| Page transition | Fade + slight Y translate (`opacity-0 translateY(6px)`) |
| Link hover | `transition-colors duration-200` |

### Organic Shape Dividers

Use a restrained SVG wave divider where it helps separate major public sections. Blend it with adjacent surfaces. Operational dashboard sections use whitespace and simple dividers instead of decorative waves.

### Anti-Patterns (NEVER DO)

- ❌ Sharp corners (`rounded`, `rounded-sm`, `rounded-none`) on any user-facing element
- ❌ Generic grays (`gray-*`) for borders or text — always use `stone-*` for warm neutrals
- ❌ Raw green (`green-*`) for primary actions — always `moss-*`
- ❌ Raw yellow (`yellow-*`) for warnings — always `harvest-*`
- ❌ Raw blue (`blue-*`) for info — always `dew-*`
- ❌ Raw brown (`brown-*`) — use `soil-*` or `stone-*`
- ❌ Missing `font-serif` on display headings (h1, card titles, modal titles)
- ❌ `shadow-md` or `shadow-lg` without organic context — use `shadow-soft` or `shadow-organic`
- ❌ Buttons without hover scale and transition
- ❌ Cards without `rounded-2xl` and `shadow-soft`
- ❌ Modals without `rounded-3xl` and `shadow-organic`
- ❌ Inline styles in Vue templates
