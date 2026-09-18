<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import FormField from '../molecules/FormField.vue'
import AppButton from '../atoms/AppButton.vue'
import AppAlert from '../atoms/AppAlert.vue'
import { useAuthStore } from '../../stores/auth'

const authStore = useAuthStore()
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
  loading.value = true
  try {
    const response = await authStore.resetPassword(form.value)
    success.value = response.message || 'Password reset successful. You can now login.'
    setTimeout(() => {
      router.push({ name: 'login' })
    }, 2000)
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to reset password.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form class="space-y-6" @submit.prevent="handleResetPassword">
    <AppAlert v-if="error" type="error">{{ error }}</AppAlert>
    <AppAlert v-if="success" type="success">{{ success }}</AppAlert>

    <FormField
      id="reset-email"
      label="Email address"
      type="email"
      v-model="form.email"
      :required="true"
      :disabled="true"
    />

    <FormField
      id="reset-password"
      label="New Password"
      type="password"
      v-model="form.password"
      :required="true"
    />

    <FormField
      id="reset-password-confirmation"
      label="Confirm Password"
      type="password"
      v-model="form.password_confirmation"
      :required="true"
    />

    <AppButton type="submit" variant="primary" size="md" :loading="loading" class="w-full">
      Reset Password
    </AppButton>
  </form>
</template>
