<script setup>
import ConfirmModal from '../molecules/ConfirmModal.vue'
import { useConfirmModal } from '@/composables/useConfirmModal'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import FormField from '../molecules/FormField.vue'
import AddressFields from './AddressFields.vue'
import AppButton from '../atoms/AppButton.vue'
import AppAlert from '../atoms/AppAlert.vue'
import { useAuthStore } from '../../stores/auth'

const AUTH_NAME_MAX_LENGTH = 255
const AUTH_EMAIL_MAX_LENGTH = 255
const AUTH_PASSWORD_MIN_LENGTH = 8
const AUTH_PASSWORD_MAX_LENGTH = 255

const { t } = useI18n()
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
  if (!form.value.name.trim()) return t('auth.register.err_name')
  if (form.value.name.length > AUTH_NAME_MAX_LENGTH)
    return t('auth.register.err_name_max', { max: AUTH_NAME_MAX_LENGTH })
  if (!form.value.email.trim()) return t('auth.register.err_email')
  if (form.value.email.length > AUTH_EMAIL_MAX_LENGTH)
    return t('auth.register.err_email_max', { max: AUTH_EMAIL_MAX_LENGTH })
  if (form.value.password.length < AUTH_PASSWORD_MIN_LENGTH)
    return t('auth.register.err_password_min', { min: AUTH_PASSWORD_MIN_LENGTH })
  if (form.value.password.length > AUTH_PASSWORD_MAX_LENGTH)
    return t('auth.register.err_password_max', { max: AUTH_PASSWORD_MAX_LENGTH })
  if (form.value.password !== form.value.password_confirmation)
    return t('auth.register.err_mismatch')
  if (showAddress.value && !pinValid.value) return t('auth.register.err_pin')
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
      title: t('auth.register.confirm_title'),
      message: t('auth.register.confirm_msg'),
      confirmText: t('auth.register.confirm_cta'),
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
    error.value = e.response?.data?.message || t('auth.register.failed')
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
        :label="t('auth.register.name_label')"
        v-model="form.name"
        :required="true"
        :maxlength="AUTH_NAME_MAX_LENGTH"
      />
      <FormField
        id="reg-email"
        :label="t('auth.register.email_label')"
        type="email"
        autocomplete="username"
        name="email"
        v-model="form.email"
        :required="true"
        :maxlength="AUTH_EMAIL_MAX_LENGTH"
      />

      <fieldset>
        <legend class="text-sm font-medium text-soil-700">
          {{ t('auth.register.role_legend') }}
        </legend>
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
            <label for="role_buyer" class="ml-3 block text-sm font-medium text-soil-700">{{
              t('auth.register.role_buyer')
            }}</label>
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
            <label for="role_farmer" class="ml-3 block text-sm font-medium text-soil-700">{{
              t('auth.register.role_farmer')
            }}</label>
          </div>
        </div>
      </fieldset>

      <FormField
        id="reg-password"
        :label="t('auth.register.password_label')"
        type="password"
        :minlength="AUTH_PASSWORD_MIN_LENGTH"
        autocomplete="new-password"
        v-model="form.password"
        :required="true"
        :maxlength="AUTH_PASSWORD_MAX_LENGTH"
      />
      <FormField
        id="reg-password-confirm"
        :label="t('auth.register.confirm_label')"
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
            {{ t('auth.register.address_toggle') }}
            <span class="text-stone-500 font-normal">{{ t('auth.register.address_hint') }}</span>
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
        {{ t('auth.register.submit') }}
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
