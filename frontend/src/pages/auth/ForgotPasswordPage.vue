<script setup>
import { RouterLink } from 'vue-router'
import ForgotPasswordForm from '../../components/organisms/ForgotPasswordForm.vue'
</script>

<template>
  <div>
    <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-gray-900 mb-8">
      Forgot your password?
    </h2>
    <ForgotPasswordForm />
    <div class="mt-6 text-center text-sm">
      <span class="text-gray-600">Remember your password? </span>
      <RouterLink to="/auth/login" class="font-medium text-green-600 hover:text-green-500"
        >Sign in here</RouterLink
      >
    </div>
  </div>
</template>
