<script setup>
import { RouterLink } from 'vue-router'
import ForgotPasswordForm from '../../components/organisms/ForgotPasswordForm.vue'
</script>

<template>
  <div>
    <h2 class="font-serif mt-6 text-center text-3xl font-bold tracking-tight text-stone-900 mb-8">
      Forgot your password?
    </h2>
    <ForgotPasswordForm />
    <div class="mt-6 text-center text-sm">
      <span class="text-stone-600">Remember your password? </span>
      <RouterLink to="/auth/login" class="font-medium text-moss-600 hover:text-moss-500"
        >Sign in here</RouterLink
      >
    </div>
  </div>
</template>
