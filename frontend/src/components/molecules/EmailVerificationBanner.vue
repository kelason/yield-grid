<template>
  <div
    class="bg-gradient-to-r from-amber-500 to-orange-500 px-4 py-3 sm:px-6 lg:px-8 relative z-50 shadow-md border-b border-amber-600"
  >
    <div class="max-w-7xl mx-auto flex items-center justify-between flex-wrap gap-4">
      <div class="flex flex-1 items-center gap-x-3">
        <span class="flex p-2 rounded-lg bg-white/20 backdrop-blur-sm shadow-inner">
          <svg
            class="h-5 w-5 text-white drop-shadow-sm"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="2"
            stroke="currentColor"
            aria-hidden="true"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
            />
          </svg>
        </span>
        <p class="text-sm leading-6 text-white drop-shadow-sm">
          <strong class="font-semibold text-white">Action Required:</strong>
          Please verify your email address. You will not be able to use the marketplace or manage
          plots until your email is verified.
        </p>
      </div>
      <div class="flex-shrink-0">
        <button
          type="button"
          @click="handleResend"
          :disabled="isSending || authStore.resendCooldown > 0"
          class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-amber-700 shadow-sm hover:bg-amber-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white transition-all disabled:opacity-70 disabled:cursor-not-allowed active:scale-95 min-w-[180px]"
        >
          <span v-if="isSending">Sending...</span>
          <span v-else-if="authStore.resendCooldown > 0"
            >Resend in {{ authStore.resendCooldown }}s</span
          >
          <span v-else>Resend Verification Email</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const isSending = ref(false)

const handleResend = async () => {
  if (isSending.value) return
  isSending.value = true
  try {
    await authStore.resendVerificationEmail()
    alert('Verification email sent! Please check your inbox.')
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'Failed to resend verification email.')
  } finally {
    isSending.value = false
  }
}
</script>
