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
