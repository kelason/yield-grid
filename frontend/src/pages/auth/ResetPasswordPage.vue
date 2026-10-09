<script setup>
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import ResetPasswordForm from '../../components/organisms/ResetPasswordForm.vue'

const { t } = useI18n()
</script>

<template>
  <div>
    <ResetPasswordForm />
    <div class="mt-6 text-center text-sm">
      <span class="text-stone-600">{{ t('auth.reset.remember') }} </span>
      <RouterLink
        to="/auth/login"
        class="inline-flex min-h-11 items-center rounded-xl px-1 font-medium text-moss-700 hover:text-moss-900 transition-colors duration-200"
        >{{ t('auth.reset.signin_link') }}</RouterLink
      >
    </div>
  </div>
</template>
