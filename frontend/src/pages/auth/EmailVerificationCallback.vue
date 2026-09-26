<template>
  <div class="text-center">
    <div v-if="loading" class="flex flex-col items-center justify-center space-y-4">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
      <h2 class="text-xl font-semibold text-gray-900">Verifying your email...</h2>
      <p class="text-gray-500">Please wait while we confirm your email address.</p>
    </div>

    <div v-else-if="success" class="flex flex-col items-center justify-center space-y-4">
      <div class="h-16 w-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
        <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M5 13l4 4L19 7"
          />
        </svg>
      </div>
      <h2 class="text-2xl font-bold text-gray-900">Email Verified!</h2>
      <p class="text-gray-500">
        Thank you for verifying your email address. You will be redirected shortly.
      </p>
    </div>

    <div v-else class="flex flex-col items-center justify-center space-y-4">
      <div class="h-16 w-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
        <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M6 18L18 6M6 6l12 12"
          />
        </svg>
      </div>
      <h2 class="text-2xl font-bold text-gray-900">Verification Failed</h2>
      <p class="text-gray-500">
        {{ errorMessage || 'The verification link is invalid or has expired.' }}
      </p>
      <button
        @click="router.push({ path: '/dashboard' })"
        class="mt-4 w-full flex justify-center py-2.5 px-4 border border-transparent rounded-xl shadow-sm text-sm font-semibold text-white bg-green-600 hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-600 transition-all active:scale-[0.98]"
      >
        Go to Dashboard
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const REDIRECT_DELAY_MS = 2000

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const loading = ref(true)
const success = ref(false)
const errorMessage = ref('')

onMounted(async () => {
  let verifyUrl = route.query.verify_url

  if (!verifyUrl) {
    loading.value = false
    errorMessage.value = 'No verification URL provided.'
    return
  }

  // If the email client decoded the URL, Vue Router might split the query parameters
  // We need to reconstruct the full verification URL
  try {
    const urlObj = new URL(verifyUrl)
    for (const key in route.query) {
      if (key !== 'verify_url' && !urlObj.searchParams.has(key)) {
        urlObj.searchParams.append(key, route.query[key])
      }
    }
    verifyUrl = urlObj.toString()

    // Check if the link is expired before requiring authentication
    const expires = urlObj.searchParams.get('expires')
    if (expires && Date.now() / 1000 > parseInt(expires)) {
      loading.value = false
      errorMessage.value = 'The link expired please click resend.'
      return
    }
  } catch {
    // Ignore invalid URLs here, it will error in the store validation
  }

  try {
    await authStore.verifyEmail(verifyUrl)
    success.value = true

    // Redirect to dashboard (or login if not yet authenticated) after delay
    setTimeout(() => {
      if (authStore.isAuthenticated) {
        router.push({ path: '/dashboard' })
      } else {
        router.push({ name: 'login' })
      }
    }, REDIRECT_DELAY_MS)
  } catch {
    loading.value = false
    errorMessage.value = 'The link expired please click resend.'
  }
})
</script>
