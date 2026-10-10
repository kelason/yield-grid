<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ArrowUpRightIcon, Bars3Icon, UserCircleIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import { useConfirmModal } from '@/composables/useConfirmModal'
import AppLogo from '../atoms/AppLogo.vue'
import AppButton from '../atoms/AppButton.vue'
import AppModal from '../molecules/AppModal.vue'
import ConfirmModal from '../molecules/ConfirmModal.vue'
import LanguageDropdown from '../molecules/LanguageDropdown.vue'
import NavLink from '../molecules/NavLink.vue'
const authStore = useAuthStore()
const route = useRoute()
const { t } = useI18n()
const DESKTOP_QUERY = '(min-width: 1280px)'
const publicLinks = [
  { to: '/', labelKey: 'common.nav.home' },
  { to: '/about', labelKey: 'common.nav.about' },
  { to: '/contact', labelKey: 'common.nav.contact' },
]
const menuOpen = ref(false)
const { isOpen, isExecuting, config, confirm, execute, cancel } = useConfirmModal()
let breakpoint

function closeMenu() {
  menuOpen.value = false
}
function onBreakpoint(event) {
  if (event.matches) closeMenu()
}
watch(() => route.fullPath, closeMenu)
onMounted(() => {
  breakpoint = window.matchMedia?.(DESKTOP_QUERY)
  breakpoint?.addEventListener('change', onBreakpoint)
})
onBeforeUnmount(() => breakpoint?.removeEventListener('change', onBreakpoint))
function requestLogout() {
  closeMenu()
  confirm(
    { title: 'Log out?', message: 'End your current YieldGrid session?', confirmText: 'Log out' },
    () => authStore.logout(),
  )
}
</script>
<template>
  <header class="sticky top-0 z-50 border-b border-stone-200 bg-white shadow-soft">
    <div
      class="mx-auto flex min-h-16 max-w-7xl items-center justify-between gap-4 px-4 py-2 sm:px-6 xl:min-h-20 xl:px-8 xl:py-0"
    >
      <AppLogo />
      <nav :aria-label="t('common.nav.main_label')" class="hidden items-center gap-7 xl:flex">
        <NavLink
          v-for="link in publicLinks"
          :key="link.to"
          :to="link.to"
          :label="t(link.labelKey)"
        />
      </nav>
      <div class="hidden items-center gap-3 xl:flex">
        <LanguageDropdown />
        <template v-if="!authStore.isAuthenticated"
          ><RouterLink
            to="/auth/login"
            class="rounded-xl px-3 py-3 text-sm font-medium text-stone-700 transition-colors hover:text-moss-700"
            >{{ t('common.auth.login') }}</RouterLink
          ><RouterLink
            to="/auth/register"
            class="inline-flex min-h-11 items-center rounded-xl bg-gradient-to-br from-moss-500 to-moss-600 px-5 text-sm font-medium text-white transition-all duration-300 hover:scale-[1.02] hover:from-moss-600 hover:to-moss-700 motion-reduce:transform-none"
            >{{ t('common.auth.get_started') }}</RouterLink
          ></template
        ><template v-else
          ><RouterLink
            to="/dashboard"
            class="rounded-xl px-3 py-3 text-sm font-medium text-moss-700"
            >{{ t('common.nav.dashboard') }}</RouterLink
          ><AppButton variant="ghost" size="sm" @click="requestLogout">{{
            t('common.auth.logout')
          }}</AppButton></template
        >
      </div>
      <AppButton
        variant="ghost"
        size="sm"
        class="shrink-0 border border-stone-200 bg-stone-50 xl:hidden"
        :aria-label="t('common.nav.open_menu')"
        aria-haspopup="dialog"
        :aria-expanded="menuOpen"
        @click="menuOpen = true"
        ><Bars3Icon class="h-6 w-6" aria-hidden="true"
      /></AppButton>
    </div>
    <AppModal :is-open="menuOpen" :title="t('common.nav.menu')" size="sm" @close="closeMenu">
      <nav :aria-label="t('common.nav.mobile_label')" class="flex flex-col gap-5">
        <div class="flex flex-col gap-2">
          <RouterLink
            v-for="link in publicLinks"
            :key="link.to"
            :to="link.to"
            class="group flex min-h-14 items-center justify-between gap-4 rounded-2xl border px-4 py-3 font-serif text-2xl transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500 focus-visible:ring-offset-2"
            :class="
              route.path === link.to
                ? 'border-moss-200 bg-moss-50 text-moss-800'
                : 'border-transparent text-stone-900 hover:border-stone-200 hover:bg-stone-50'
            "
            @click="closeMenu"
          >
            {{ t(link.labelKey) }}
            <span
              class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full transition-colors duration-200"
              :class="
                route.path === link.to
                  ? 'bg-moss-100 text-moss-700'
                  : 'text-stone-500 group-hover:bg-stone-100'
              "
              aria-hidden="true"
            >
              <ArrowUpRightIcon class="h-4 w-4" />
            </span>
          </RouterLink>
        </div>
        <hr aria-hidden="true" class="border-stone-200" />
        <div v-if="!authStore.isAuthenticated" class="flex flex-col gap-2">
          <RouterLink
            to="/auth/login"
            class="inline-flex min-h-12 items-center justify-center rounded-xl border-2 border-moss-300 px-5 py-2.5 text-base font-medium text-moss-700 transition-all duration-300 hover:scale-[1.02] hover:border-moss-500 hover:bg-moss-50 motion-reduce:transform-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500 focus-visible:ring-offset-2"
            @click="closeMenu"
            >{{ t('common.auth.login') }}</RouterLink
          >
          <RouterLink
            to="/auth/register"
            class="inline-flex min-h-12 items-center justify-center rounded-xl bg-gradient-to-br from-moss-500 to-moss-600 px-5 py-2.5 text-base font-medium text-white shadow-soft transition-all duration-300 hover:scale-[1.02] hover:from-moss-600 hover:to-moss-700 hover:shadow-organic motion-reduce:transform-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500 focus-visible:ring-offset-2"
            @click="closeMenu"
            >{{ t('common.auth.get_started') }}</RouterLink
          >
        </div>
        <div v-else class="flex flex-col gap-3">
          <RouterLink
            v-if="authStore.user?.id"
            :to="{ name: 'user-profile', params: { userId: authStore.user.id } }"
            :aria-label="t('shell.profile_of', { name: authStore.user.name })"
            class="flex min-h-14 items-center gap-3 rounded-2xl border border-stone-200 bg-stone-50 p-3 transition-colors duration-200 hover:bg-stone-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500 focus-visible:ring-offset-2"
            @click="closeMenu"
          >
            <UserCircleIcon class="h-9 w-9 shrink-0 text-moss-600" aria-hidden="true" />
            <span class="min-w-0 break-words text-sm font-medium text-stone-900">
              {{ authStore.user.name }}
              <span class="mt-0.5 block text-xs font-normal text-stone-600">{{
                t('shell.profile')
              }}</span>
            </span>
          </RouterLink>
          <RouterLink
            to="/dashboard"
            class="inline-flex min-h-11 items-center justify-center rounded-xl bg-gradient-to-br from-moss-500 to-moss-600 px-5 py-2.5 text-base font-medium text-white shadow-soft transition-all duration-300 hover:scale-[1.02] hover:from-moss-600 hover:to-moss-700 hover:shadow-organic motion-reduce:transform-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500 focus-visible:ring-offset-2"
            @click="closeMenu"
            >{{ t('common.nav.dashboard') }}</RouterLink
          >
          <AppButton variant="ghost" class="w-full" @click="requestLogout">{{
            t('common.auth.logout')
          }}</AppButton>
        </div>
        <hr aria-hidden="true" class="border-stone-200" />
        <div class="space-y-2">
          <p class="text-xs font-medium uppercase tracking-widest text-soil-700">
            {{ t('common.language.label') }}
          </p>
          <LanguageDropdown variant="inline" />
        </div>
      </nav>
    </AppModal>
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
