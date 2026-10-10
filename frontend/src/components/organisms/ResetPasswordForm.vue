<script setup>
import ConfirmModal from '../molecules/ConfirmModal.vue'
import { useConfirmModal } from '@/composables/useConfirmModal'
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import FormField from '../molecules/FormField.vue'
import AppButton from '../atoms/AppButton.vue'
import AppAlert from '../atoms/AppAlert.vue'
import { useAuthStore } from '../../stores/auth'
import { HTTP_STATUS } from '../../constants/http'

const REDIRECT_DELAY_MS = 2000
const AUTH_PASSWORD_MIN_LENGTH = 8
const AUTH_PASSWORD_MAX_LENGTH = 255

const { t } = useI18n()
const authStore = useAuthStore()
const { isOpen, isExecuting, config, confirm, execute, cancel } = useConfirmModal()
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
  if (form.value.password.length < AUTH_PASSWORD_MIN_LENGTH) {
    error.value = t('auth.reset.err_password_min', { min: AUTH_PASSWORD_MIN_LENGTH })
    return
  }
  if (form.value.password.length > AUTH_PASSWORD_MAX_LENGTH) {
    error.value = t('auth.reset.err_password_max', { max: AUTH_PASSWORD_MAX_LENGTH })
    return
  }
  if (form.value.password !== form.value.password_confirmation) {
    error.value = t('auth.reset.err_mismatch')
    return
  }
  const payload = { ...form.value }
  confirm(
    {
      title: t('auth.reset.confirm_title'),
      message: t('auth.reset.confirm_msg'),
      confirmText: t('auth.reset.confirm_cta'),
    },
    () => performRequest(payload),
  )
}
async function performRequest(payload) {
  loading.value = true
  try {
    const response = await authStore.resetPassword(payload)
    success.value = response.message || t('auth.reset.success')
    setTimeout(() => {
      router.push({ name: 'login' })
    }, REDIRECT_DELAY_MS)
  } catch (e) {
    if (e.response?.status === HTTP_STATUS.UNPROCESSABLE_ENTITY && e.response?.data?.errors) {
      error.value = Object.values(e.response.data.errors).flat().join(' ')
    } else {
      error.value = e.response?.data?.message || t('auth.reset.failed')
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <form class="space-y-6" @submit.prevent="handleResetPassword">
      <AppAlert v-if="error" type="error">{{ error }}</AppAlert>
      <AppAlert v-if="success" type="success">{{ success }}</AppAlert>

      <FormField
        id="reset-email"
        :label="t('auth.reset.email_label')"
        type="email"
        autocomplete="username"
        name="email"
        v-model="form.email"
        :required="true"
        :disabled="true"
      />

      <FormField
        id="reset-password"
        :label="t('auth.reset.new_label')"
        type="password"
        :minlength="AUTH_PASSWORD_MIN_LENGTH"
        autocomplete="new-password"
        v-model="form.password"
        :required="true"
        :maxlength="AUTH_PASSWORD_MAX_LENGTH"
      />

      <FormField
        id="reset-password-confirmation"
        :label="t('auth.reset.confirm_label')"
        type="password"
        :minlength="AUTH_PASSWORD_MIN_LENGTH"
        autocomplete="new-password"
        v-model="form.password_confirmation"
        :required="true"
        :maxlength="AUTH_PASSWORD_MAX_LENGTH"
      />

      <AppButton type="submit" variant="primary" size="md" :loading="loading" class="w-full">
        {{ t('auth.reset.submit') }}
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
