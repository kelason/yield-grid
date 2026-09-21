<script setup>
import { ref } from 'vue'
import FormField from '../molecules/FormField.vue'
import AppButton from '../atoms/AppButton.vue'
import AppAlert from '../atoms/AppAlert.vue'
import { useAuthStore } from '../../stores/auth'

const authStore = useAuthStore()

const email = ref('')
const error = ref('')
const success = ref('')
const loading = ref(false)

async function handleForgotPassword() {
  error.value = ''
  success.value = ''
  loading.value = true
  try {
    const response = await authStore.sendPasswordResetLink(email.value)
    success.value = response.message || 'Password reset link sent to your email.'
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to send reset link.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form class="space-y-6" @submit.prevent="handleForgotPassword">
    <AppAlert v-if="error" type="error">{{ error }}</AppAlert>
    <AppAlert v-if="success" type="success">{{ success }}</AppAlert>

    <FormField
      id="forgot-email"
      label="Email address"
      type="email"
      v-model="email"
      :required="true"
    />

    <AppButton type="submit" variant="primary" size="md" :loading="loading" class="w-full">
      Send Reset Link
    </AppButton>
  </form>
</template>
