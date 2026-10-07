<script setup>
import { RouterLink } from 'vue-router'
import ForgotPasswordForm from '../../components/organisms/ForgotPasswordForm.vue'
</script>

<template>
  <div>
    <ForgotPasswordForm />
    <div class="mt-6 text-center text-sm">
      <span class="text-stone-600">Remember your password? </span>
      <RouterLink
        to="/auth/login"
        class="inline-flex min-h-11 items-center rounded-xl px-1 font-medium text-moss-700 hover:text-moss-900 transition-colors duration-200"
        >Sign in here</RouterLink
      >
    </div>
  </div>
</template>
