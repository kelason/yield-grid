<script setup>
import ConfirmModal from '../molecules/ConfirmModal.vue'
import { useConfirmModal } from '@/composables/useConfirmModal'
import { ref } from 'vue'
import FormField from '../molecules/FormField.vue'
import AppButton from '../atoms/AppButton.vue'
import AppAlert from '../atoms/AppAlert.vue'
import { useAuthStore } from '../../stores/auth'

const AUTH_EMAIL_MAX_LENGTH = 255

const authStore = useAuthStore()
const { isOpen, isExecuting, config, confirm, execute, cancel } = useConfirmModal()

const email = ref('')
const error = ref('')
const success = ref('')
const loading = ref(false)

async function handleForgotPassword() {
  error.value = ''
  success.value = ''
  if (!email.value.trim()) {
    error.value = 'Please enter your email address.'
    return
  }
  if (email.value.length > AUTH_EMAIL_MAX_LENGTH) {
    error.value = `Email must be at most ${AUTH_EMAIL_MAX_LENGTH} characters.`
    return
  }
  const payload = email.value
  confirm(
    {
      title: 'Send reset link?',
      message: 'Send a password reset link to this email address?',
      confirmText: 'Send Reset Link',
    },
    () => performRequest(payload),
  )
}
async function performRequest(payload) {
  loading.value = true
  try {
    const response = await authStore.sendPasswordResetLink(payload)
    success.value = response.message || 'Password reset link sent to your email.'
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to send reset link.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <form class="space-y-6" @submit.prevent="handleForgotPassword">
      <AppAlert v-if="error" type="error">{{ error }}</AppAlert>
      <AppAlert v-if="success" type="success">{{ success }}</AppAlert>

      <FormField
        id="forgot-email"
        label="Email address"
        type="email"
        autocomplete="username"
        name="email"
        v-model="email"
        :required="true"
        :maxlength="AUTH_EMAIL_MAX_LENGTH"
      />

      <AppButton type="submit" variant="primary" size="md" :loading="loading" class="w-full">
        Send Reset Link
      </AppButton>
    </form>
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
