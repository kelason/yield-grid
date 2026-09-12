<script setup>
import { ref } from 'vue'
import { useApi } from '../composables/useApi'

const api = useApi()
const form = ref({
  name: '',
  email: '',
  subject: '',
  message: ''
})
const loading = ref(false)
const successMessage = ref('')
const error = ref('')

async function submitContact() {
  loading.value = true
  error.value = ''
  successMessage.value = ''
  
  try {
    const response = await api.post('/contact', form.value)
    successMessage.value = response.data.message
    form.value = { name: '', email: '', subject: '', message: '' }
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to send message. Please try again later.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="max-w-3xl mx-auto py-16 px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12">
      <h2 class="text-3xl font-extrabold text-gray-900">Contact Us</h2>
      <p class="mt-4 text-lg text-gray-500">Have a question about YieldGrid? Send us a message.</p>
    </div>
    
    <div v-if="successMessage" class="rounded-md bg-green-50 p-4 mb-6">
      <div class="flex">
        <div class="ml-3">
          <h3 class="text-sm font-medium text-green-800">{{ successMessage }}</h3>
        </div>
      </div>
    </div>
    
    <div v-if="error" class="rounded-md bg-red-50 p-4 mb-6">
      <div class="flex">
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">{{ error }}</h3>
        </div>
      </div>
    </div>

    <form @submit.prevent="submitContact" class="space-y-6">
      <div>
        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
        <div class="mt-1">
          <input type="text" id="name" v-model="form.name" required class="py-3 px-4 block w-full shadow-sm focus:ring-farm-500 focus:border-farm-500 border-gray-300 rounded-md border" />
        </div>
      </div>
      <div>
        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
        <div class="mt-1">
          <input type="email" id="email" v-model="form.email" required class="py-3 px-4 block w-full shadow-sm focus:ring-farm-500 focus:border-farm-500 border-gray-300 rounded-md border" />
        </div>
      </div>
      <div>
        <label for="subject" class="block text-sm font-medium text-gray-700">Subject</label>
        <div class="mt-1">
          <input type="text" id="subject" v-model="form.subject" class="py-3 px-4 block w-full shadow-sm focus:ring-farm-500 focus:border-farm-500 border-gray-300 rounded-md border" />
        </div>
      </div>
      <div>
        <label for="message" class="block text-sm font-medium text-gray-700">Message</label>
        <div class="mt-1">
          <textarea id="message" v-model="form.message" rows="4" required class="py-3 px-4 block w-full shadow-sm focus:ring-farm-500 focus:border-farm-500 border-gray-300 rounded-md border"></textarea>
        </div>
      </div>
      <div>
        <button type="submit" :disabled="loading" class="w-full inline-flex justify-center py-3 px-6 border border-transparent shadow-sm text-base font-medium rounded-md text-white bg-farm-600 hover:bg-farm-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-farm-500 transition-colors disabled:opacity-50">
          {{ loading ? 'Sending...' : 'Send Message' }}
        </button>
      </div>
    </form>
  </div>
</template>
