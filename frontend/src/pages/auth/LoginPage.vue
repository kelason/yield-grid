<script setup>
import { useI18n } from 'vue-i18n'
import { useRouter, useRoute, RouterLink } from 'vue-router'
import LoginForm from '../../components/organisms/LoginForm.vue'

const { t } = useI18n()

const router = useRouter()
const route = useRoute()

function onLoginSuccess() {
  const redirect = route.query.redirect
  if (redirect) {
    router.push(redirect)
  } else {
    router.push('/dashboard')
  }
}
</script>

<template>
  <div>
    <LoginForm @success="onLoginSuccess" />
    <div class="mt-6 text-center text-sm">
      <span class="text-stone-600">{{ t('auth.login.no_account') }}</span>
      <RouterLink
        to="/auth/register"
        class="inline-flex min-h-11 items-center rounded-xl px-1 font-medium text-moss-700 hover:text-moss-900 transition-colors duration-200"
        >{{ t('auth.login.register_link') }}</RouterLink
      >
    </div>
  </div>
</template>
