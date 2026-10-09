<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { RouterLink, RouterView, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Bars3Icon } from '@heroicons/vue/24/outline'
import AppSidebar from '../organisms/AppSidebar.vue'
import AppLogo from '../atoms/AppLogo.vue'
import AppButton from '../atoms/AppButton.vue'
import ConfirmModal from '../molecules/ConfirmModal.vue'
import EmailVerificationBanner from '../molecules/EmailVerificationBanner.vue'
import LanguageDropdown from '../molecules/LanguageDropdown.vue'
import { useAuthStore } from '@/stores/auth'
import { useChatStore } from '@/stores/chatStore'
import { useNotificationStore } from '@/stores/notificationStore'
import { useConfirmModal } from '@/composables/useConfirmModal'
import { USER_ROLES } from '@/constants/roles'
const authStore = useAuthStore()
const chatStore = useChatStore()
const router = useRouter()
const { t } = useI18n()
const mobileOpen = ref(false)
const isResending = ref(false)
const notificationStore = useNotificationStore()
const { isOpen, isExecuting, config, confirm, execute, cancel } = useConfirmModal()
onMounted(async () => {
  if (
    authStore.isAuthenticated &&
    authStore.isEmailVerified &&
    authStore.userRole !== USER_ROLES.ADMIN
  ) {
    await chatStore.ensureConversationsLoaded()
    chatStore.startRealtimeSync()
  }
})
onUnmounted(() => chatStore.stopRealtimeSync())
function requestLogout() {
  confirm(
    { title: 'Log out?', message: 'End your current YieldGrid session?', confirmText: 'Log out' },
    async () => {
      await authStore.logout()
      router.push({ name: 'home' })
    },
  )
}
function requestResend() {
  if (isResending.value || authStore.resendCooldown > 0) return
  confirm(
    {
      title: 'Resend verification email?',
      message: 'Send a new verification link to your account email?',
      confirmText: 'Resend email',
    },
    resendVerification,
  )
}
async function resendVerification() {
  isResending.value = true
  try {
    await authStore.resendVerificationEmail()
    notificationStore.addNotification({ type: 'success', messageKey: 'common.verify.email_sent' })
  } catch (error) {
    notificationStore.addNotification({
      type: 'error',
      message: error.response?.data?.message,
      messageKey: 'common.verify.resend_failed',
    })
  } finally {
    isResending.value = false
  }
}
</script>
<template>
  <div class="flex h-dvh flex-col overflow-hidden bg-stone-50 font-sans">
    <a
      href="#main-content"
      class="sr-only z-50 rounded-xl bg-white p-3 focus:not-sr-only focus:absolute"
      >Skip to main content</a
    >
    <EmailVerificationBanner
      v-if="authStore.isAuthenticated && !authStore.isEmailVerified"
      :loading="isResending"
      :cooldown="authStore.resendCooldown"
      @resend="requestResend"
    />
    <div class="flex min-h-0 flex-1">
      <AppSidebar :mobile-open="mobileOpen" @close="mobileOpen = false" />
      <div class="flex min-w-0 flex-1 flex-col">
        <header
          class="flex flex-wrap min-h-20 shrink-0 items-center justify-between gap-3 border-b border-stone-200 bg-white px-4 sm:px-6 lg:px-8"
        >
          <div class="flex min-w-0 items-center gap-3">
            <AppButton
              variant="ghost"
              size="sm"
              class="md:hidden"
              :aria-label="t('shell.open_navigation')"
              @click="mobileOpen = true"
              ><Bars3Icon class="h-6 w-6" aria-hidden="true" /></AppButton
            ><AppLogo class="md:hidden" /><span class="hidden text-sm text-stone-600 md:block">{{
              t('shell.tagline')
            }}</span>
          </div>
          <div class="flex shrink-0 items-center gap-2 sm:gap-4">
            <div class="hidden md:block">
              <LanguageDropdown />
            </div>
            <RouterLink
              v-if="authStore.user?.id"
              :to="{ name: 'user-profile', params: { userId: authStore.user.id } }"
              class="hidden max-w-48 break-words rounded-xl text-sm font-medium text-stone-900 transition-colors hover:text-moss-700 sm:block"
              >{{ authStore.user.name }}</RouterLink
            ><AppButton variant="ghost" size="sm" @click="requestLogout">{{
              t('common.auth.logout')
            }}</AppButton>
          </div>
        </header>
        <main
          id="main-content"
          tabindex="-1"
          class="min-h-0 flex-1 overflow-y-auto focus:outline-none"
        >
          <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10"><RouterView /></div>
        </main>
      </div>
    </div>
    <ConfirmModal
      :is-open="isOpen"
      :title="config.title"
      :message="config.message"
      :confirm-text="config.confirmText"
      :loading="isExecuting"
      @confirm="execute"
      @cancel="cancel"
    />
  </div>
</template>
