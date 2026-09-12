<script setup>
import { ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'buyer'
})
const error = ref('')
const loading = ref(false)

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
    router.push({ name: 'dashboard' })
  } catch (e) {
    error.value = e.response?.data?.message || 'Registration failed.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form class="space-y-6" @submit.prevent="handleRegister">
    <div v-if="error" class="rounded-md bg-red-50 p-4">
      <div class="flex">
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">{{ error }}</h3>
        </div>
      </div>
    </div>
    
    <div>
      <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
      <div class="mt-1">
        <input id="name" name="name" type="text" required v-model="form.name" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-farm-500 focus:border-farm-500 sm:text-sm" />
      </div>
    </div>

    <div>
      <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
      <div class="mt-1">
        <input id="email" name="email" type="email" autocomplete="email" required v-model="form.email" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-farm-500 focus:border-farm-500 sm:text-sm" />
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">I am a...</label>
      <div class="mt-2 flex items-center space-x-6">
        <div class="flex items-center">
          <input id="role_buyer" name="role" type="radio" value="buyer" v-model="form.role" class="focus:ring-farm-500 h-4 w-4 text-farm-600 border-gray-300" />
          <label for="role_buyer" class="ml-3 block text-sm font-medium text-gray-700"> Buyer </label>
        </div>
        <div class="flex items-center">
          <input id="role_farmer" name="role" type="radio" value="farmer" v-model="form.role" class="focus:ring-farm-500 h-4 w-4 text-farm-600 border-gray-300" />
          <label for="role_farmer" class="ml-3 block text-sm font-medium text-gray-700"> Farmer </label>
        </div>
      </div>
    </div>

    <div>
      <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
      <div class="mt-1">
        <input id="password" name="password" type="password" required v-model="form.password" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-farm-500 focus:border-farm-500 sm:text-sm" />
      </div>
    </div>

    <div>
      <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
      <div class="mt-1">
        <input id="password_confirmation" name="password_confirmation" type="password" required v-model="form.password_confirmation" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-farm-500 focus:border-farm-500 sm:text-sm" />
      </div>
    </div>

    <div>
      <button type="submit" :disabled="loading" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-farm-600 hover:bg-farm-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-farm-500 disabled:opacity-50">
        {{ loading ? 'Creating account...' : 'Create account' }}
      </button>
    </div>
    
    <div class="mt-6 text-center text-sm">
      <span class="text-gray-600">Already have an account? </span>
      <RouterLink to="/auth/login" class="font-medium text-farm-600 hover:text-farm-500">Sign in here</RouterLink>
    </div>
  </form>
</template>
