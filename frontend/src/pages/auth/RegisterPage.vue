<script setup>
import { useI18n } from 'vue-i18n'
import { useRouter, RouterLink } from 'vue-router'
import RegisterForm from '../../components/organisms/RegisterForm.vue'

const { t } = useI18n()

const router = useRouter()

function onRegisterSuccess() {
  router.push('/dashboard')
}
</script>

<template>
  <div>
    <RegisterForm @success="onRegisterSuccess" />
    <div class="mt-6 text-center text-sm">
      <span class="text-stone-600">{{ t('auth.register.has_account') }}</span>
      <RouterLink
        to="/auth/login"
        class="inline-flex min-h-11 items-center rounded-xl px-1 font-medium text-moss-700 hover:text-moss-900 transition-colors duration-200"
        >{{ t('auth.register.signin_link') }}</RouterLink
      >
    </div>
  </div>
</template>
