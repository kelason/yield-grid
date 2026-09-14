<script setup>
import { RouterView } from 'vue-router'
import AppLogo from '../atoms/AppLogo.vue'
</script>

<template>
  <div
    class="min-h-screen bg-earth-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans"
  >
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div class="text-center">
        <AppLogo />
      </div>
      <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
        <slot name="title">Welcome Back</slot>
      </h2>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 border border-earth-100">
        <RouterView />
      </div>
    </div>
  </div>
</template>
