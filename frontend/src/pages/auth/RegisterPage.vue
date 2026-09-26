<script setup>
import { useRouter, RouterLink } from 'vue-router'
import RegisterForm from '../../components/organisms/RegisterForm.vue'

const router = useRouter()

function onRegisterSuccess() {
  router.push('/dashboard')
}
</script>

<template>
  <div>
    <RegisterForm @success="onRegisterSuccess" />
    <div class="mt-6 text-center text-sm">
      <span class="text-gray-600">Already have an account? </span>
      <RouterLink to="/auth/login" class="font-medium text-green-600 hover:text-green-500"
        >Sign in here</RouterLink
      >
    </div>
  </div>
</template>
