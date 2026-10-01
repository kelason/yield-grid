<script setup>
import { ref } from 'vue'
import FormField from '../molecules/FormField.vue'
import AppButton from '../atoms/AppButton.vue'
import AppAlert from '../atoms/AppAlert.vue'
import { useAuthStore } from '../../stores/auth'

const AUTH_EMAIL_MAX_LENGTH = 255

const authStore = useAuthStore()

const form = ref({ email: '', password: '', remember: false })
const error = ref('')
const loading = ref(false)

const emit = defineEmits(['success'])

async function handleLogin() {
  error.value = ''
  if (!form.value.email.trim()) {
    error.value = 'Please enter your email address.'
    return
  }
  if (form.value.email.length > AUTH_EMAIL_MAX_LENGTH) {
    error.value = `Email must be at most ${AUTH_EMAIL_MAX_LENGTH} characters.`
    return
  }
  if (!form.value.password) {
    error.value = 'Please enter your password.'
    return
  }
  loading.value = true
  try {
    await authStore.login(form.value)
    emit('success')
  } catch (e) {
    error.value = e.response?.data?.message || 'Login failed. Please check your credentials.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form class="space-y-6" @submit.prevent="handleLogin">
    <AppAlert v-if="error" type="error">{{ error }}</AppAlert>

    <FormField
      id="login-email"
      label="Email address"
      type="email"
      v-model="form.email"
      :required="true"
      :maxlength="AUTH_EMAIL_MAX_LENGTH"
    />
    <FormField
      id="login-password"
      label="Password"
      type="password"
      v-model="form.password"
      :required="true"
    />

    <div class="flex items-center justify-between">
      <div class="flex items-center">
        <input
          id="remember-me"
          type="checkbox"
          v-model="form.remember"
          class="h-4 w-4 text-moss-600 focus:ring-moss-500 border-stone-300 rounded-xl"
        />
        <label for="remember-me" class="ml-2 block text-sm text-soil-700">Remember me</label>
      </div>
      <router-link
        :to="{ name: 'forgot-password' }"
        class="text-sm font-medium text-moss-600 hover:text-moss-500 transition-colors duration-200"
      >
        Forgot your password?
      </router-link>
    </div>

    <AppButton type="submit" variant="primary" size="md" :loading="loading" class="w-full">
      Sign in
    </AppButton>
  </form>
</template>
