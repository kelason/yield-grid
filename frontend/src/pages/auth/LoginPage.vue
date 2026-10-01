<script setup>
import { useRouter, useRoute, RouterLink } from 'vue-router'
import LoginForm from '../../components/organisms/LoginForm.vue'

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
      <span class="text-stone-600">Don't have an account? </span>
      <RouterLink to="/auth/register" class="font-medium text-moss-600 hover:text-moss-500"
        >Register here</RouterLink
      >
    </div>
  </div>
</template>
