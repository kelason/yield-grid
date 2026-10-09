<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { ChevronDoubleLeftIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import { useDashboardNavigation } from '@/composables/useDashboardNavigation'
import AppLogo from '../atoms/AppLogo.vue'
import AppButton from '../atoms/AppButton.vue'
import AppModal from '../molecules/AppModal.vue'
import LanguageDropdown from '../molecules/LanguageDropdown.vue'
import SidebarNavigation from '../molecules/SidebarNavigation.vue'
defineProps({ mobileOpen: Boolean })
const emit = defineEmits(['close'])
const auth = useAuthStore()
const route = useRoute()
const isCollapsed = ref(false)
const { navigation, openGroups, toggleGroup } = useDashboardNavigation()
const DESKTOP_QUERY = '(min-width: 768px)'
let breakpoint
function closeMobile() {
  emit('close')
}
function onBreakpoint(event) {
  if (event.matches) closeMobile()
}
watch(() => route.fullPath, closeMobile)
onMounted(() => {
  breakpoint = window.matchMedia?.(DESKTOP_QUERY)
  breakpoint?.addEventListener('change', onBreakpoint)
})
onBeforeUnmount(() => breakpoint?.removeEventListener('change', onBreakpoint))
</script>
<template>
  <aside
    class="hidden h-full shrink-0 flex-col border-r border-soil-700 bg-gradient-to-b from-soil-900 to-soil-800 text-stone-100 md:flex"
    :class="isCollapsed ? 'w-20' : 'w-64'"
  >
    <div class="flex min-h-20 items-center justify-center border-b border-soil-700 px-4">
      <AppLogo dark :collapsed="isCollapsed" />
    </div>
    <div class="flex justify-end p-3">
      <AppButton
        variant="ghost-dark"
        size="sm"
        :aria-label="isCollapsed ? 'Expand navigation' : 'Collapse navigation'"
        @click="isCollapsed = !isCollapsed"
        ><ChevronDoubleLeftIcon
          class="h-5 w-5"
          :class="{ 'rotate-180': isCollapsed }"
          aria-hidden="true"
      /></AppButton>
    </div>
    <SidebarNavigation
      class="min-h-0 flex-1 overflow-y-auto px-3 pb-4"
      :items="navigation"
      :collapsed="isCollapsed"
      :open-groups="openGroups"
      @toggle="toggleGroup"
    />
    <RouterLink
      v-if="auth.user?.id"
      :to="{ name: 'user-profile', params: { userId: auth.user.id } }"
      class="m-3 rounded-xl border border-soil-600 px-3 py-3 text-sm transition-colors hover:bg-soil-700"
      :aria-label="`${auth.user.name} profile`"
      ><span v-if="isCollapsed" aria-hidden="true">{{ auth.user.name?.charAt(0) }}</span
      ><span v-else class="block break-words"
        >{{ auth.user.name
        }}<span class="mt-1 block capitalize text-stone-300">{{ auth.userRole }}</span></span
      ></RouterLink
    >
  </aside>
  <AppModal
    :is-open="mobileOpen"
    title="Navigation"
    placement="left"
    size="sm"
    @close="closeMobile"
  >
    <div v-if="mobileOpen" class="rounded-2xl bg-soil-900 p-3">
      <SidebarNavigation
        :items="navigation"
        :open-groups="openGroups"
        @toggle="toggleGroup"
        @navigate="closeMobile"
      />
      <RouterLink
        v-if="auth.user?.id"
        :to="{ name: 'user-profile', params: { userId: auth.user.id } }"
        :aria-label="`${auth.user.name} profile`"
        class="mt-3 block min-h-11 rounded-xl border border-soil-600 px-3 py-3 text-sm text-stone-100 transition-colors hover:bg-soil-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-300"
        @click="closeMobile"
      >
        <span class="block break-words">{{ auth.user.name }}</span>
        <span class="mt-1 block text-stone-300">Profile</span>
      </RouterLink>
      <div class="mt-3">
        <LanguageDropdown />
      </div>
    </div>
  </AppModal>
</template>
