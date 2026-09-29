<script setup>
import { ref } from 'vue'
import FormField from '../molecules/FormField.vue'
import AddressFields from '../molecules/AddressFields.vue'
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
const showAddress = ref(false)
const address = ref({
  label: '',
  street: '',
  region_code: '',
  province_code: null,
  city_municipality_code: '',
  barangay_code: '',
  latitude: null,
  longitude: null,
})
const addressErrors = ref({})
const pinValid = ref(true)
const error = ref('')
const loading = ref(false)

const emit = defineEmits(['success'])

async function handleRegister() {
  error.value = ''
  addressErrors.value = {}
  loading.value = true

  if (form.value.password !== form.value.password_confirmation) {
    error.value = 'Passwords do not match.'
    loading.value = false
    return
  }

  if (showAddress.value && !pinValid.value) {
    error.value = 'Please place your pin within the selected area.'
    loading.value = false
    return
  }

  const payload = { ...form.value }
  if (showAddress.value && address.value.region_code) {
    payload.address = { ...address.value }
    if (!payload.address.province_code) payload.address.province_code = null
    if (!payload.address.label) delete payload.address.label
  }

  try {
    await authStore.register(payload)
    emit('success')
  } catch (e) {
    error.value = e.response?.data?.message || 'Registration failed.'
    const errors = e.response?.data?.errors || {}
    Object.keys(errors).forEach((key) => {
      if (key.startsWith('address.')) {
        addressErrors.value[key.replace('address.', '')] = errors[key][0]
      }
    })
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
            class="focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300"
          />
          <label for="role_buyer" class="ml-3 block text-sm font-medium text-gray-700">Buyer</label>
        </div>
        <div class="flex items-center">
          <input
            id="role_farmer"
            type="radio"
            value="farmer"
            v-model="form.role"
            class="focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300"
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

    <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
      <label class="flex items-center gap-2 cursor-pointer select-none">
        <input
          id="reg-has-address"
          type="checkbox"
          v-model="showAddress"
          class="h-4 w-4 rounded text-moss-600 border-stone-300 focus:ring-moss-500"
        />
        <span class="text-sm font-medium text-stone-900">
          Add my address now
          <span class="text-stone-400 font-normal">(optional — required later for trading)</span>
        </span>
      </label>
      <div v-if="showAddress" class="mt-4">
        <AddressFields
          v-model="address"
          id-prefix="reg-addr"
          :show-label="false"
          :errors="addressErrors"
          @pin-validation="pinValid = $event.valid"
        />
      </div>
    </div>

    <AppButton type="submit" variant="primary" size="md" :loading="loading" class="w-full">
      Create account
    </AppButton>
  </form>
</template>
