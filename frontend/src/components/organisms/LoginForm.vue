<script setup>
import { ref } from 'vue'
import FormField from '../molecules/FormField.vue'
import AppButton from '../atoms/AppButton.vue'
import AppAlert from '../atoms/AppAlert.vue'
import { useAuthStore } from '../../stores/auth'

const authStore = useAuthStore()

const form = ref({ email: '', password: '', remember: false })
const error = ref('')
const loading = ref(false)

const emit = defineEmits(['success'])

async function handleLogin() {
  error.value = ''
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
          class="h-4 w-4 text-farm-600 focus:ring-farm-500 border-gray-300 rounded"
        />
        <label for="remember-me" class="ml-2 block text-sm text-gray-900">Remember me</label>
      </div>
      <router-link
        :to="{ name: 'forgot-password' }"
        class="text-sm font-medium text-farm-600 hover:text-farm-500"
      >
        Forgot your password?
      </router-link>
    </div>

    <AppButton type="submit" variant="primary" size="md" :loading="loading" class="w-full">
      Sign in
    </AppButton>
  </form>
</template>
