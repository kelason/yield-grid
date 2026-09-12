<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useApi } from '../../composables/useApi'
import AppButton from '../../components/atoms/AppButton.vue'
import AppAlert from '../../components/atoms/AppAlert.vue'

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
    <p class="mt-4 text-sm text-gray-500">
      Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you?
    </p>

    <AppAlert v-if="message" type="success" class="mt-4">{{ message }}</AppAlert>
    <AppAlert v-if="error" type="error" class="mt-4">{{ error }}</AppAlert>

    <div class="mt-6 flex flex-col sm:flex-row justify-center gap-4">
      <AppButton variant="primary" :loading="sending" @click="resendVerificationEmail">
        Resend Verification Email
      </AppButton>
      <AppButton variant="ghost" @click="authStore.logout()">
        Log Out
      </AppButton>
    </div>
  </div>
</template>
