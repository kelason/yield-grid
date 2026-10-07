<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import AppButton from '@/components/atoms/AppButton.vue'
import LoadingState from '@/components/molecules/LoadingState.vue'
const REDIRECT_DELAY_MS = 2000
const MILLISECONDS_PER_SECOND = 1000
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const loading = ref(true)
const success = ref(false)
const errorMessage = ref('')
let redirectTimer
let disposed = false
function verificationUrl() {
  const original = route.query.verify_url
  if (!original) throw new Error('No verification URL provided.')
  try {
    const url = new URL(original)
    for (const key in route.query) {
      if (key !== 'verify_url' && !url.searchParams.has(key))
        url.searchParams.append(key, route.query[key])
    }
    const expires = url.searchParams.get('expires')
    if (expires && Date.now() / MILLISECONDS_PER_SECOND > parseInt(expires))
      throw new Error('The link expired please click resend.')
    return url.toString()
  } catch (error) {
    if (error.message === 'The link expired please click resend.') throw error
    return original
  }
}
function redirect() {
  router.push(authStore.isAuthenticated ? { path: '/dashboard' } : { name: 'login' })
}
onMounted(async () => {
  try {
    await authStore.verifyEmail(verificationUrl())
    if (disposed) return
    success.value = true
    redirectTimer = setTimeout(redirect, REDIRECT_DELAY_MS)
  } catch (error) {
    if (!disposed)
      errorMessage.value =
        error.message === 'No verification URL provided.'
          ? error.message
          : 'The link expired please click resend.'
  } finally {
    if (!disposed) loading.value = false
  }
})
onBeforeUnmount(() => {
  disposed = true
  clearTimeout(redirectTimer)
})
</script>
<template>
  <div class="text-center space-y-5">
    <LoadingState v-if="loading" label="Verifying your email..." />
    <template v-else-if="success"
      ><CheckCircleIcon class="mx-auto h-12 w-12 text-moss-700" aria-hidden="true" />
      <h2 class="font-serif text-2xl font-bold text-stone-900">Email Verified!</h2>
      <p class="text-base text-stone-600 leading-relaxed">
        Thank you for verifying your email address. You will be redirected shortly.
      </p></template
    >
    <template v-else
      ><XCircleIcon class="mx-auto h-12 w-12 text-red-600" aria-hidden="true" />
      <h2 class="font-serif text-2xl font-bold text-stone-900">Verification Failed</h2>
      <p role="alert" class="text-base text-stone-600 leading-relaxed">
        {{ errorMessage || 'The verification link is invalid or has expired.' }}
      </p>
      <AppButton @click="router.push('/dashboard')">Go to Dashboard</AppButton></template
    >
  </div>
</template>
