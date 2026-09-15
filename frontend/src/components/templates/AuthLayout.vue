<script setup>
import { RouterView } from 'vue-router'
import AppLogo from '../atoms/AppLogo.vue'
</script>

<template>
  <div class="min-h-screen relative flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans overflow-hidden">

    <!-- Gradient background -->
    <div class="absolute inset-0 bg-gradient-to-br from-earth-50 via-white to-farm-50 pointer-events-none" aria-hidden="true"></div>
    <!-- Decorative blobs -->
    <div class="absolute top-0 right-0 w-96 h-96 bg-farm-200/20 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl pointer-events-none" aria-hidden="true"></div>
    <div class="absolute bottom-0 left-0 w-72 h-72 bg-earth-200/20 rounded-full translate-y-1/2 -translate-x-1/2 blur-3xl pointer-events-none" aria-hidden="true"></div>

    <!-- Content -->
    <div class="relative z-10 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="text-center mb-8">
        <AppLogo />
      </div>
      <h2 class="text-center text-3xl font-extrabold text-gray-900 tracking-tight">
        <slot name="title">Welcome Back</slot>
      </h2>
      <p class="mt-2 text-center text-sm text-gray-500">
        <slot name="subtitle" />
      </p>
    </div>

    <div class="relative z-10 mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white/90 backdrop-blur-sm py-8 px-6 shadow-xl rounded-2xl border border-gray-100 ring-1 ring-gray-200/50 sm:px-10">
        <RouterView />
      </div>
    </div>

  </div>
</template>
