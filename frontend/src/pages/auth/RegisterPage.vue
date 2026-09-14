<script setup>
import { useRouter, RouterLink } from 'vue-router'
import RegisterForm from '../../components/organisms/RegisterForm.vue'

const router = useRouter()

function onRegisterSuccess() {
  router.push({ name: 'dashboard' })
}
</script>

<template>
  <div>
    <RegisterForm @success="onRegisterSuccess" />
    <div class="mt-6 text-center text-sm">
      <span class="text-gray-600">Already have an account? </span>
      <RouterLink to="/auth/login" class="font-medium text-farm-600 hover:text-farm-500"
        >Sign in here</RouterLink
      >
    </div>
  </div>
</template>
