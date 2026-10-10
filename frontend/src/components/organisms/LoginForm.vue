<script setup>
import ConfirmModal from '../molecules/ConfirmModal.vue'
import { useConfirmModal } from '@/composables/useConfirmModal'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import FormField from '../molecules/FormField.vue'
import AppButton from '../atoms/AppButton.vue'
import AppAlert from '../atoms/AppAlert.vue'
import { useAuthStore } from '../../stores/auth'

const AUTH_EMAIL_MAX_LENGTH = 255
const AUTH_PASSWORD_MAX_LENGTH = 255

const { t } = useI18n()
const authStore = useAuthStore()
const { isOpen, isExecuting, config, confirm, execute, cancel } = useConfirmModal()

const form = ref({ email: '', password: '', remember: false })
const error = ref('')
const loading = ref(false)

const emit = defineEmits(['success'])

function loginError() {
  if (!form.value.email.trim()) return t('auth.login.err_email')
  if (form.value.email.length > AUTH_EMAIL_MAX_LENGTH)
    return t('auth.login.err_email_max', { max: AUTH_EMAIL_MAX_LENGTH })
  if (!form.value.password) return t('auth.login.err_password')
  if (form.value.password.length > AUTH_PASSWORD_MAX_LENGTH)
    return t('auth.login.err_password_max', { max: AUTH_PASSWORD_MAX_LENGTH })
  return ''
}
function handleLogin() {
  error.value = loginError()
  if (error.value) return
  const payload = { ...form.value }
  confirm(
    {
      title: t('auth.login.confirm_title'),
      message: t('auth.login.confirm_msg'),
      confirmText: t('auth.login.confirm_cta'),
    },
    () => performLogin(payload),
  )
}
async function performLogin(payload) {
  loading.value = true
  try {
    await authStore.login(payload)
    emit('success')
  } catch (e) {
    error.value = e.response?.data?.message || t('auth.login.failed')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <form class="space-y-6" @submit.prevent="handleLogin">
      <AppAlert v-if="error" type="error">{{ error }}</AppAlert>

      <FormField
        id="login-email"
        :label="t('auth.login.email_label')"
        type="email"
        autocomplete="username"
        name="email"
        v-model="form.email"
        :required="true"
        :maxlength="AUTH_EMAIL_MAX_LENGTH"
      />
      <FormField
        id="login-password"
        name="password"
        :maxlength="AUTH_PASSWORD_MAX_LENGTH"
        minlength="1"
        :label="t('auth.login.password_label')"
        type="password"
        autocomplete="current-password"
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
          <label for="remember-me" class="ml-2 block text-sm text-soil-700">{{
            t('auth.login.remember')
          }}</label>
        </div>
        <router-link
          :to="{ name: 'forgot-password' }"
          class="text-sm font-medium text-moss-600 hover:text-moss-500 transition-colors duration-200"
        >
          {{ t('auth.login.forgot') }}
        </router-link>
      </div>

      <AppButton type="submit" variant="primary" size="md" :loading="loading" class="w-full">
        {{ t('auth.login.submit') }}
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
