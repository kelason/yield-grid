# frontend

This template should help get you started developing with Vue 3 in Vite.

## Recommended IDE Setup

[VS Code](https://code.visualstudio.com/) + [Vue (Official)](https://marketplace.visualstudio.com/items?itemName=Vue.volar) (and disable Vetur).

## Recommended Browser Setup

- Chromium-based browsers (Chrome, Edge, Brave, etc.):
  - [Vue.js devtools](https://chromewebstore.google.com/detail/vuejs-devtools/nhdogjmejiglipccpnnnanhbledajbpd)
  - [Turn on Custom Object Formatter in Chrome DevTools](http://bit.ly/object-formatters)
- Firefox:
  - [Vue.js devtools](https://addons.mozilla.org/en-US/firefox/addon/vue-js-devtools/)
  - [Turn on Custom Object Formatter in Firefox DevTools](https://fxdx.dev/firefox-devtools-custom-object-formatters/)

## Customize configuration

See [Vite Configuration Reference](https://vite.dev/config/).

## Project Setup

```sh
npm install
```

### Compile and Hot-Reload for Development

```sh
npm run dev
```

### Compile and Minify for Production

```sh
npm run build
```

### Run Unit Tests with [Vitest](https://vitest.dev/)

```sh
npm run test:unit
```

### Lint with [ESLint](https://eslint.org/)

```sh
npm run lint
```

## Redesign checks and visual baselines

### Brand assets

`BrandLogo` renders the YieldGrid field-and-leaf mark with the Lora wordmark;
`AppLogo` adds the translated home link. The mark geometry lives in
`src/constants/brand.js`, and colors come from `src/constants/designTokens.js`.
Run `npm run brand:generate` in the pinned Playwright container below after
changing either source, mounting the full repository so backend exports persist.
It exports the standalone SVG/PNG logo, browser and
touch icons, and the self-contained PNG used by both backend PDF headers.
Review the exports and affected visual baselines before committing them.

### Browser verification

YieldGrid uses the Field & Linen version of Modern Minimalist Organic Web Design.
See `../AGENTS.md` and `../docs/superpowers/specs/2026-10-07-modern-organic-redesign.md`
for component contracts, color tokens and accessibility rules.

Use a supported Node version (`^22.18.0` or `>=24.12.0`):

```sh
npm run lint:check
npm run format:check
npx prettier --check "e2e/**/*.js" playwright.config.js vite.config.js
npx vitest run --maxWorkers=2
npm run build
npm run test:e2e
```

Playwright builds and serves the production bundle at `127.0.0.1:4173` with a
local mock API URL. Fixtures use synthetic accounts, a fixed clock, `en-PH` and
`Asia/Manila`; unexpected API requests and uncaught browser errors fail the test.
Inter and Lora are self-hosted with their upstream OFL licenses. No live backend,
checkout provider or realtime service is needed for these checks.

Pixel comparisons run only on Linux Chromium and mobile Chromium. macOS runs
capture review evidence without comparing Linux pixels. The approved environment
is Ubuntu 24.04 / Playwright 1.63.0; CI's browser job is pinned to Ubuntu 24.04.
The matching container is `mcr.microsoft.com/playwright:v1.63.0-noble`, digest
`sha256:eff16c30e6f3f4af0a03fa4b706120d5e9b0891c344a27d64559aff5900a4a27`.
Use Linux-installed dependencies inside that environment, never a macOS
`node_modules` mount. To propose changed baselines there:

```sh
npx playwright test e2e/Visual/redesign.spec.js --project=chromium --project=mobile-chromium --update-snapshots
```

Review every changed image and diff against the intended design and behavior
before committing. Then run the same command without `--update-snapshots` and
run the complete browser suite. Regenerating an image alone is not approval.
For engine behavior smoke checks:

```sh
npx playwright install --with-deps firefox webkit
npx playwright test e2e/IdentityAndAccess e2e/Shared --project=firefox --project=webkit
```

The `Browser smoke` workflow also provides an explicit manual run.

## Admin release checks

Admin, trust, and safety release verification, against the production
build served by the Playwright webServer:

```sh
npx playwright test e2e/Admin e2e/Community e2e/Marketplace e2e/Shared --project=chromium --project=mobile-chromium
npx playwright test e2e/IdentityAndAccess e2e/Shared e2e/Admin/access.spec.js --project=firefox --project=webkit
```

### Public tutorial examples

`public/images/guides/*-en.png` are reviewed synthetic examples of the actual
English app, not real accounts, farms, yields or transactions. Written steps and
image captions are localized; captions identify the English interface. Images
are optional illustrations and all instructions remain usable if an image fails.

Reproduce proposals inside the pinned Linux Playwright environment described
above, with the production preview and fixed fixtures:

```sh
npx playwright test e2e/Public/tutorial-assets.spec.js --project=chromium --workers=1
```

The four files appear under `test-results`, never overwrite shipped assets, and
must be visually reviewed before copying into `public/images/guides`. The plot
example crops the unsaved form after drawing an illustrative polygon with mock
tiles; the listing example crops title, quantity and price fields. No scene saves,
publishes or pays. Update intrinsic dimensions in `constants/tutorialJourneys.js`
when accepting a different crop.

Public information pages use `/features`, `/how-it-works`, `/documentation` and
`/documentation/:topic`. Their copy is checked in for EN/TL/CEB. The guide registry
owns stable topic/section links; tutorials reuse its translated procedures.

Public visual cases in `e2e/Visual/redesign.spec.js` cover all four destinations,
the representative payments article, and Home/About/Contact/mobile navigation.
Review proposed full-page images and diffs in the pinned Linux environment before
accepting baselines. The article test also produces a scrolled cash-confirmation
evidence image. Behavior and enlarged-text cases live under `e2e/Public`.
