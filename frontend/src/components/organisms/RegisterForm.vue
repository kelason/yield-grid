<script setup>
import { ref } from 'vue'
import FormField from '../molecules/FormField.vue'
import AppButton from '../atoms/AppButton.vue'
import AppAlert from '../atoms/AppAlert.vue'
import { useAuthStore } from '../../stores/auth'

const authStore = useAuthStore()

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'buyer',
})
const error = ref('')
const loading = ref(false)

const emit = defineEmits(['success'])

async function handleRegister() {
  error.value = ''
  loading.value = true

  if (form.value.password !== form.value.password_confirmation) {
    error.value = 'Passwords do not match.'
    loading.value = false
    return
  }

  try {
    await authStore.register(form.value)
    emit('success')
  } catch (e) {
    error.value = e.response?.data?.message || 'Registration failed.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form class="space-y-6" @submit.prevent="handleRegister">
    <AppAlert v-if="error" type="error">{{ error }}</AppAlert>

    <FormField id="reg-name" label="Full Name" v-model="form.name" :required="true" />
    <FormField
      id="reg-email"
      label="Email address"
      type="email"
      v-model="form.email"
      :required="true"
    />

    <div>
      <label class="block text-sm font-medium text-gray-700">I am a...</label>
      <div class="mt-2 flex items-center space-x-6">
        <div class="flex items-center">
          <input
            id="role_buyer"
            type="radio"
            value="buyer"
            v-model="form.role"
            class="focus:ring-farm-500 h-4 w-4 text-farm-600 border-gray-300"
          />
          <label for="role_buyer" class="ml-3 block text-sm font-medium text-gray-700">Buyer</label>
        </div>
        <div class="flex items-center">
          <input
            id="role_farmer"
            type="radio"
            value="farmer"
            v-model="form.role"
            class="focus:ring-farm-500 h-4 w-4 text-farm-600 border-gray-300"
          />
          <label for="role_farmer" class="ml-3 block text-sm font-medium text-gray-700"
            >Farmer</label
          >
        </div>
      </div>
    </div>

    <FormField
      id="reg-password"
      label="Password"
      type="password"
      v-model="form.password"
      :required="true"
    />
    <FormField
      id="reg-password-confirm"
      label="Confirm Password"
      type="password"
      v-model="form.password_confirmation"
      :required="true"
    />

    <AppButton type="submit" variant="primary" size="md" :loading="loading" class="w-full">
      Create account
    </AppButton>
  </form>
</template>
