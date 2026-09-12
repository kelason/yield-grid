<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useApi } from '../composables/useApi'

const authStore = useAuthStore()
const api = useApi()

const sending = ref(false)
const message = ref('')
const error = ref('')

async function resendVerificationEmail() {
  sending.value = true
  message.value = ''
  error.value = ''
  
  try {
    const res = await api.post('/email/verification-notification')
    message.value = res.data.message
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to send verification link.'
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <div class="text-center py-6">
    <svg class="mx-auto h-12 w-12 text-farm-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
    </svg>
    <h3 class="mt-2 text-lg leading-6 font-medium text-gray-900">Verify your email</h3>
    <div class="mt-4 text-sm text-gray-500">
      <p>Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.</p>
    </div>
    
    <div v-if="message" class="mt-4 rounded-md bg-green-50 p-4">
      <h3 class="text-sm font-medium text-green-800">{{ message }}</h3>
    </div>
    <div v-if="error" class="mt-4 rounded-md bg-red-50 p-4">
      <h3 class="text-sm font-medium text-red-800">{{ error }}</h3>
    </div>

    <div class="mt-6 flex flex-col sm:flex-row justify-center gap-4">
      <button @click="resendVerificationEmail" :disabled="sending" class="inline-flex justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-farm-600 hover:bg-farm-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-farm-500 disabled:opacity-50">
        {{ sending ? 'Sending...' : 'Resend Verification Email' }}
      </button>
      <button @click="authStore.logout" class="inline-flex justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-farm-500">
        Log Out
      </button>
    </div>
  </div>
</template>
