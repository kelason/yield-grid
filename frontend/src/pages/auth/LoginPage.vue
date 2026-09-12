<script setup>
import { useRouter, RouterLink } from 'vue-router'
import LoginForm from '../../components/organisms/LoginForm.vue'

const router = useRouter()

function onLoginSuccess() {
  router.push({ name: 'dashboard' })
}
</script>

<template>
  <div>
    <LoginForm @success="onLoginSuccess" />
    <div class="mt-6 text-center text-sm">
      <span class="text-gray-600">Don't have an account? </span>
      <RouterLink to="/auth/register" class="font-medium text-farm-600 hover:text-farm-500">Register here</RouterLink>
    </div>
  </div>
</template>
