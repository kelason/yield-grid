import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { FARMER_NAVIGATION, BUYER_NAVIGATION, RESTRICTED_FEATURES } from '@/constants/navigation'

export function isNavigationActive(to, route) {
  if (to.name === 'recommendations')
    return ['recommendations', 'crop-recommendations'].includes(route.name)
  if (to.name === 'farm-manager') return ['farm-manager', 'plot-planner'].includes(route.name)
  return route.name === to.name || route.path.startsWith(to.path || '/not-a-path')
}
function visibleEntry(entry, verified) {
  if (verified) return entry
  if (!entry.children) return RESTRICTED_FEATURES.includes(entry.name) ? null : entry
  const children = entry.children.filter((child) => !RESTRICTED_FEATURES.includes(child.name))
  return children.length ? { ...entry, children } : null
}
function navigationFor(auth, route) {
  const entries = auth.userRole === 'buyer' ? BUYER_NAVIGATION : FARMER_NAVIGATION
  return entries
    .map((entry) => visibleEntry(entry, auth.isEmailVerified))
    .filter(Boolean)
    .map((entry) => ({
      ...entry,
      active:
        entry.children?.some((child) => isNavigationActive(child.to, route)) ??
        isNavigationActive(entry.to, route),
    }))
}
function expandActiveGroups(navigation, expanded) {
  for (const group of navigation)
    if (group.children && group.active) expanded.value[group.name] = true
}
export function useDashboardNavigation() {
  const auth = useAuthStore()
  const route = useRoute()
  const expanded = ref({})
  const navigation = computed(() => navigationFor(auth, route))
  const isActive = (to) => isNavigationActive(to, route)
  const isGroupOpen = (group) => expanded.value[group.name] ?? group.active
  const toggleGroup = (group) => {
    expanded.value[group.name] = !isGroupOpen(group)
  }
  const openGroups = computed(() =>
    Object.fromEntries(
      navigation.value
        .filter((entry) => entry.children)
        .map((entry) => [entry.name, isGroupOpen(entry)]),
    ),
  )
  watch(
    () => route.name,
    () => expandActiveGroups(navigation.value, expanded),
  )
  return { navigation, openGroups, isActive, isGroupOpen, toggleGroup }
}
