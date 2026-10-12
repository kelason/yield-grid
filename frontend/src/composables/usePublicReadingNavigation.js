import { nextTick, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const READING_GAP_PX = 16
const SCROLL_ORIGIN = 0
const positions = new Map()

function entryId(route) {
  // Vue Router assigns a distinct position to each browser history entry.
  return window.history.state?.position ?? route.fullPath
}

function savePosition(state) {
  if (!state.activePath || state.pending) return
  positions.set(state.entry, { path: state.activePath, top: window.scrollY, left: window.scrollX })
}

function headingTarget(main, hash) {
  try {
    const target = hash && document.getElementById(decodeURIComponent(hash.slice(1)))
    if (target && main.contains(target)) return target
  } catch {
    // A malformed fragment still leaves the article readable.
  }
  return main.querySelector('h1')
}

function isTransitioning(main) {
  return (
    main.parentElement?.classList.contains('page-enter-active') ||
    main.querySelector('.fade-leave-active, .fade-enter-active')
  )
}

function scrollToHeading(state, main) {
  const target = headingTarget(main, state.route.hash)
  if (!target) return
  target.setAttribute('tabindex', '-1')
  target.classList.add('public-reading-heading')
  target.focus({ preventScroll: true })
  const header = main.parentElement?.querySelector(':scope > header')
  const offset = (header?.getBoundingClientRect().height ?? SCROLL_ORIGIN) + READING_GAP_PX
  const top =
    !state.route.hash || state.route.name === 'home'
      ? SCROLL_ORIGIN
      : Math.max(SCROLL_ORIGIN, target.getBoundingClientRect().top + window.scrollY - offset)
  window.scrollTo({ top, left: SCROLL_ORIGIN, behavior: 'instant' })
}

async function onPageReady(state) {
  const pending = state.pending
  await nextTick()
  await document.fonts?.ready
  const main = state.mainRef.value
  if (!pending || pending !== state.pending || !main || state.disposed) return
  if (isTransitioning(main) || document.querySelector('dialog[open]')) return
  if (pending.saved)
    window.scrollTo({ top: pending.saved.top, left: pending.saved.left, behavior: 'instant' })
  else scrollToHeading(state, main)
  state.pending = null
  savePosition(state)
}

function requestReading(state, restoring = false) {
  state.entry = entryId(state.route)
  state.activePath = state.route.meta.publicReading ? state.route.fullPath : null
  const saved = positions.get(state.entry)
  state.pending = state.activePath
    ? {
        saved: restoring && saved?.path === state.activePath ? saved : null,
      }
    : null
  onPageReady(state)
}

function installNavigation(state, router) {
  const onPop = () => {
    savePosition(state)
    state.restoring = true
  }
  const onScroll = () => {
    if (entryId(state.route) === state.entry) savePosition(state)
  }
  const removeBefore = router.beforeEach(() => {
    if (!state.restoring) savePosition(state)
  })
  const removeAfter = router.afterEach((_to, _from, failure) => {
    if (!failure) requestReading(state, state.restoring)
    state.restoring = false
  })
  window.addEventListener('popstate', onPop)
  window.addEventListener('scroll', onScroll, { passive: true })
  return () => disposeNavigation(state, removeBefore, removeAfter, onPop, onScroll)
}

function disposeNavigation(state, removeBefore, removeAfter, onPop, onScroll) {
  savePosition(state)
  state.disposed = true
  state.pending = null
  removeBefore()
  removeAfter()
  window.removeEventListener('popstate', onPop)
  window.removeEventListener('scroll', onScroll)
}

export function usePublicReadingNavigation({ mainRef }) {
  const route = useRoute()
  const state = {
    mainRef,
    route,
    entry: entryId(route),
    pending: null,
    activePath: null,
    restoring: false,
    disposed: false,
  }
  const dispose = installNavigation(state, useRouter())
  onMounted(() => requestReading(state, positions.has(entryId(route))))
  onBeforeUnmount(dispose)
  return { onPageReady: () => onPageReady(state) }
}
