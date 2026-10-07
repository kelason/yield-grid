<script setup>
import ConfirmModal from '../molecules/ConfirmModal.vue'
import { useConfirmModal } from '@/composables/useConfirmModal'
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import FormField from '../molecules/FormField.vue'
import AppButton from '../atoms/AppButton.vue'
import AppAlert from '../atoms/AppAlert.vue'
import { useAuthStore } from '../../stores/auth'
import { HTTP_STATUS } from '../../constants/http'

const REDIRECT_DELAY_MS = 2000
const AUTH_PASSWORD_MIN_LENGTH = 8
const AUTH_PASSWORD_MAX_LENGTH = 255

const authStore = useAuthStore()
const { isOpen, isExecuting, config, confirm, execute, cancel } = useConfirmModal()
const route = useRoute()
const router = useRouter()

const form = ref({
  email: '',
  token: '',
  password: '',
  password_confirmation: '',
})

const error = ref('')
const success = ref('')
const loading = ref(false)

onMounted(() => {
  form.value.email = route.query.email || ''
  form.value.token = route.query.token || ''
})

async function handleResetPassword() {
  error.value = ''
  success.value = ''
  if (form.value.password.length < AUTH_PASSWORD_MIN_LENGTH) {
    error.value = `Password must be at least ${AUTH_PASSWORD_MIN_LENGTH} characters.`
    return
  }
  if (form.value.password.length > AUTH_PASSWORD_MAX_LENGTH) {
    error.value = `Password must be at most ${AUTH_PASSWORD_MAX_LENGTH} characters.`
    return
  }
  if (form.value.password !== form.value.password_confirmation) {
    error.value = 'Passwords do not match.'
    return
  }
  const payload = { ...form.value }
  confirm(
    {
      title: 'Reset password?',
      message: 'Replace your account password with the new password?',
      confirmText: 'Reset Password',
    },
    () => performRequest(payload),
  )
}
async function performRequest(payload) {
  loading.value = true
  try {
    const response = await authStore.resetPassword(payload)
    success.value = response.message || 'Password reset successful. You can now login.'
    setTimeout(() => {
      router.push({ name: 'login' })
    }, REDIRECT_DELAY_MS)
  } catch (e) {
    if (e.response?.status === HTTP_STATUS.UNPROCESSABLE_ENTITY && e.response?.data?.errors) {
      error.value = Object.values(e.response.data.errors).flat().join(' ')
    } else {
      error.value = e.response?.data?.message || 'Failed to reset password.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <form class="space-y-6" @submit.prevent="handleResetPassword">
      <AppAlert v-if="error" type="error">{{ error }}</AppAlert>
      <AppAlert v-if="success" type="success">{{ success }}</AppAlert>

      <FormField
        id="reset-email"
        label="Email address"
        type="email"
        autocomplete="username"
        name="email"
        v-model="form.email"
        :required="true"
        :disabled="true"
      />

      <FormField
        id="reset-password"
        label="New Password"
        type="password"
        :minlength="AUTH_PASSWORD_MIN_LENGTH"
        autocomplete="new-password"
        v-model="form.password"
        :required="true"
        :maxlength="AUTH_PASSWORD_MAX_LENGTH"
      />

      <FormField
        id="reset-password-confirmation"
        label="Confirm Password"
        type="password"
        :minlength="AUTH_PASSWORD_MIN_LENGTH"
        autocomplete="new-password"
        v-model="form.password_confirmation"
        :required="true"
        :maxlength="AUTH_PASSWORD_MAX_LENGTH"
      />

      <AppButton type="submit" variant="primary" size="md" :loading="loading" class="w-full">
        Reset Password
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
