<script setup>
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import ForgotPasswordForm from '../../components/organisms/ForgotPasswordForm.vue'

const { t } = useI18n()
</script>

<template>
  <div>
    <ForgotPasswordForm />
    <div class="mt-6 text-center text-sm">
      <span class="text-stone-600">{{ t('auth.forgot.remember') }} </span>
      <RouterLink
        to="/auth/login"
        class="inline-flex min-h-11 items-center rounded-xl px-1 font-medium text-moss-700 hover:text-moss-900 transition-colors duration-200"
        >{{ t('auth.forgot.signin_link') }}</RouterLink
      >
    </div>
  </div>
</template>
