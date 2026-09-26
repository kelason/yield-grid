<script setup>
import { ref } from 'vue'
import FormField from '../molecules/FormField.vue'
import AppButton from '../atoms/AppButton.vue'
import AppAlert from '../atoms/AppAlert.vue'
import { useApi } from '../../composables/useApi'

const api = useApi()

const form = ref({ name: '', email: '', subject: '', message: '' })
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
    error.value = e.response?.data?.message || 'Failed to send message.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form @submit.prevent="submitContact" class="space-y-6">
    <AppAlert v-if="successMessage" type="success">{{ successMessage }}</AppAlert>
    <AppAlert v-if="error" type="error">{{ error }}</AppAlert>

    <FormField id="contact-name" label="Name" v-model="form.name" :required="true" />
    <FormField
      id="contact-email"
      label="Email"
      type="email"
      v-model="form.email"
      :required="true"
    />
    <FormField id="contact-subject" label="Subject" v-model="form.subject" />

    <div>
      <label for="contact-message" class="block text-sm font-medium text-gray-700">Message</label>
      <div class="mt-1">
        <textarea
          id="contact-message"
          v-model="form.message"
          rows="4"
          required
          class="py-3 px-4 block w-full shadow-sm focus:ring-green-500 focus:border-green-500 border-gray-300 rounded-md border sm:text-sm"
        ></textarea>
      </div>
    </div>

    <AppButton type="submit" variant="primary" size="md" :loading="loading" class="w-full">
      Send Message
    </AppButton>
  </form>
</template>
