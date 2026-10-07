<script setup>
import ConfirmModal from '../molecules/ConfirmModal.vue'
import { useConfirmModal } from '@/composables/useConfirmModal'
import { ref } from 'vue'
import FormField from '../molecules/FormField.vue'
import AddressFields from './AddressFields.vue'
import AppButton from '../atoms/AppButton.vue'
import AppAlert from '../atoms/AppAlert.vue'
import { useAuthStore } from '../../stores/auth'

const AUTH_NAME_MAX_LENGTH = 255
const AUTH_EMAIL_MAX_LENGTH = 255
const AUTH_PASSWORD_MIN_LENGTH = 8
const AUTH_PASSWORD_MAX_LENGTH = 255

const authStore = useAuthStore()
const { isOpen, isExecuting, config, confirm, execute, cancel } = useConfirmModal()

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

function registrationError() {
  if (!form.value.name.trim()) return 'Please enter your name.'
  if (form.value.name.length > AUTH_NAME_MAX_LENGTH)
    return `Name must be at most ${AUTH_NAME_MAX_LENGTH} characters.`
  if (!form.value.email.trim()) return 'Please enter your email address.'
  if (form.value.email.length > AUTH_EMAIL_MAX_LENGTH)
    return `Email must be at most ${AUTH_EMAIL_MAX_LENGTH} characters.`
  if (form.value.password.length < AUTH_PASSWORD_MIN_LENGTH)
    return `Password must be at least ${AUTH_PASSWORD_MIN_LENGTH} characters.`
  if (form.value.password.length > AUTH_PASSWORD_MAX_LENGTH)
    return `Password must be at most ${AUTH_PASSWORD_MAX_LENGTH} characters.`
  if (form.value.password !== form.value.password_confirmation) return 'Passwords do not match.'
  if (showAddress.value && !pinValid.value) return 'Please place your pin within the selected area.'
  return ''
}
function registrationPayload() {
  const payload = { ...form.value }
  if (showAddress.value && address.value.region_code) {
    payload.address = { ...address.value }
    if (!payload.address.province_code) payload.address.province_code = null
    if (!payload.address.label) delete payload.address.label
  }
  return payload
}
function handleRegister() {
  error.value = registrationError()
  addressErrors.value = {}
  if (error.value) return
  const payload = registrationPayload()
  confirm(
    {
      title: 'Create account?',
      message: 'Create your YieldGrid account with these details?',
      confirmText: 'Create account',
    },
    () => performRegister(payload),
  )
}
async function performRegister(payload) {
  loading.value = true
  try {
    await authStore.register(payload)
    emit('success')
  } catch (e) {
    error.value = e.response?.data?.message || 'Registration failed.'
    const errors = e.response?.data?.errors || {}
    Object.keys(errors).forEach((key) => {
      if (key.startsWith('address.'))
        addressErrors.value[key.replace('address.', '')] = errors[key][0]
    })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <form class="space-y-6" @submit.prevent="handleRegister">
      <AppAlert v-if="error" type="error">{{ error }}</AppAlert>

      <FormField
        id="reg-name"
        autocomplete="name"
        name="name"
        label="Full Name"
        v-model="form.name"
        :required="true"
        :maxlength="AUTH_NAME_MAX_LENGTH"
      />
      <FormField
        id="reg-email"
        label="Email address"
        type="email"
        autocomplete="username"
        name="email"
        v-model="form.email"
        :required="true"
        :maxlength="AUTH_EMAIL_MAX_LENGTH"
      />

      <fieldset>
        <legend class="text-sm font-medium text-soil-700">I am a...</legend>
        <div class="mt-2 flex items-center space-x-6">
          <div class="flex items-center">
            <input
              id="role_buyer"
              type="radio"
              name="role"
              value="buyer"
              v-model="form.role"
              class="focus:ring-moss-500 h-4 w-4 text-moss-600 border-stone-300"
            />
            <label for="role_buyer" class="ml-3 block text-sm font-medium text-soil-700"
              >Buyer</label
            >
          </div>
          <div class="flex items-center">
            <input
              id="role_farmer"
              type="radio"
              name="role"
              value="farmer"
              v-model="form.role"
              class="focus:ring-moss-500 h-4 w-4 text-moss-600 border-stone-300"
            />
            <label for="role_farmer" class="ml-3 block text-sm font-medium text-soil-700"
              >Farmer</label
            >
          </div>
        </div>
      </fieldset>

      <FormField
        id="reg-password"
        label="Password"
        type="password"
        :minlength="AUTH_PASSWORD_MIN_LENGTH"
        autocomplete="new-password"
        v-model="form.password"
        :required="true"
        :maxlength="AUTH_PASSWORD_MAX_LENGTH"
      />
      <FormField
        id="reg-password-confirm"
        label="Confirm Password"
        type="password"
        :minlength="AUTH_PASSWORD_MIN_LENGTH"
        autocomplete="new-password"
        v-model="form.password_confirmation"
        :required="true"
        :maxlength="AUTH_PASSWORD_MAX_LENGTH"
      />

      <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
        <label class="flex items-center gap-2 cursor-pointer select-none">
          <input
            id="reg-has-address"
            type="checkbox"
            v-model="showAddress"
            class="h-4 w-4 rounded-xl text-moss-600 border-stone-300 focus:ring-moss-500"
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
