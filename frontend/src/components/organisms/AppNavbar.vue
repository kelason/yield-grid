<script setup>
import { ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { Bars3Icon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import { useConfirmModal } from '@/composables/useConfirmModal'
import AppLogo from '../atoms/AppLogo.vue'
import AppButton from '../atoms/AppButton.vue'
import AppModal from '../molecules/AppModal.vue'
import ConfirmModal from '../molecules/ConfirmModal.vue'
import NavLink from '../molecules/NavLink.vue'
const authStore = useAuthStore()
const route = useRoute()
const menuOpen = ref(false)
const { isOpen, isExecuting, config, confirm, execute, cancel } = useConfirmModal()
watch(
  () => route.fullPath,
  () => {
    menuOpen.value = false
  },
)
function requestLogout() {
  menuOpen.value = false
  confirm(
    { title: 'Log out?', message: 'End your current YieldGrid session?', confirmText: 'Log out' },
    () => authStore.logout(),
  )
}
</script>
<template>
  <header class="sticky top-0 z-50 border-b border-stone-200 bg-white shadow-soft">
    <div
      class="mx-auto flex min-h-20 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8"
    >
      <AppLogo />
      <nav aria-label="Main navigation" class="hidden items-center gap-7 md:flex">
        <NavLink to="/" label="Home" /><NavLink to="/about" label="About" /><NavLink
          to="/contact"
          label="Contact"
        />
      </nav>
      <div class="hidden items-center gap-3 md:flex">
        <template v-if="!authStore.isAuthenticated"
          ><RouterLink
            to="/auth/login"
            class="rounded-xl px-3 py-3 text-sm font-medium text-stone-700 transition-colors hover:text-moss-700"
            >Log in</RouterLink
          ><RouterLink
            to="/auth/register"
            class="inline-flex min-h-11 items-center rounded-xl bg-gradient-to-br from-moss-500 to-moss-600 px-5 text-sm font-medium text-white transition-all duration-300 hover:scale-[1.02] hover:from-moss-600 hover:to-moss-700 motion-reduce:transform-none"
            >Get Started</RouterLink
          ></template
        ><template v-else
          ><RouterLink
            to="/dashboard"
            class="rounded-xl px-3 py-3 text-sm font-medium text-moss-700"
            >Dashboard</RouterLink
          ><AppButton variant="ghost" size="sm" @click="requestLogout">Logout</AppButton></template
        >
      </div>
      <AppButton
        variant="ghost"
        size="sm"
        class="md:hidden"
        aria-label="Open menu"
        @click="menuOpen = true"
        ><Bars3Icon class="h-6 w-6" aria-hidden="true"
      /></AppButton>
    </div>
    <AppModal :is-open="menuOpen" title="Menu" size="sm" @close="menuOpen = false"
      ><nav aria-label="Mobile navigation" class="flex flex-col gap-3">
        <NavLink to="/" label="Home" /><NavLink to="/about" label="About" /><NavLink
          to="/contact"
          label="Contact"
        /><template v-if="!authStore.isAuthenticated"
          ><RouterLink to="/auth/login" class="rounded-xl p-3 text-moss-700">Log in</RouterLink
          ><RouterLink to="/auth/register" class="rounded-xl p-3 text-moss-700"
            >Get Started</RouterLink
          ></template
        ><template v-else
          ><RouterLink to="/dashboard" class="rounded-xl p-3 text-moss-700">Dashboard</RouterLink
          ><AppButton variant="ghost" @click="requestLogout">Logout</AppButton></template
        >
      </nav></AppModal
    >
    <ConfirmModal
      :is-open="isOpen"
      :title="config.title"
      :message="config.message"
      :confirm-text="config.confirmText"
      :loading="isExecuting"
      @confirm="execute"
      @cancel="cancel"
    />
  </header>
</template>
