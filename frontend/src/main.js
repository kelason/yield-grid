import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createHead } from '@unhead/vue/client'
import * as Sentry from '@sentry/vue'

import App from './App.vue'
import router from './router'

const app = createApp(App)
const head = createHead()

Sentry.init({
  app,
  dsn: import.meta.env.VITE_SENTRY_DSN || '',
  integrations: [Sentry.browserTracingIntegration({ router }), Sentry.replayIntegration()],
  tracesSampleRate: 1.0,
  replaysSessionSampleRate: 0.1,
  replaysOnErrorSampleRate: 1.0,
})

app.use(createPinia())
app.use(router)
app.use(head)

app.mount('#app')
