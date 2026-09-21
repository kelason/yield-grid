<script setup>
import { RouterView } from 'vue-router'
import AppLogo from '../atoms/AppLogo.vue'
</script>

<template>
  <div
    class="min-h-screen relative flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans overflow-hidden"
  >
    <!-- Organic gradient background — stone linen to moss hint -->
    <div
      class="absolute inset-0 bg-gradient-to-br from-stone-100 via-white to-moss-50 pointer-events-none"
      aria-hidden="true"
    ></div>

    <!-- Organic decorative blobs -->
    <div
      class="absolute top-0 right-0 w-[500px] h-[500px] bg-moss-200/20 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl pointer-events-none"
      aria-hidden="true"
    ></div>
    <div
      class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-harvest-200/20 rounded-full translate-y-1/2 -translate-x-1/2 blur-3xl pointer-events-none"
      aria-hidden="true"
    ></div>
    <div
      class="absolute top-1/2 left-1/2 w-[300px] h-[300px] bg-soil-200/10 rounded-full -translate-x-1/2 -translate-y-1/2 blur-2xl pointer-events-none"
      aria-hidden="true"
    ></div>

    <!-- Header content -->
    <div class="relative z-10 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="text-center mb-8">
        <AppLogo />
      </div>
      <h2 class="text-center text-3xl font-bold text-stone-900 tracking-tight font-serif">
        <slot name="title">Welcome Back</slot>
      </h2>
      <p class="mt-2 text-center text-sm text-stone-500">
        <slot name="subtitle" />
      </p>
    </div>

    <!-- Form card — organic glass -->
    <div class="relative z-10 mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div
        class="bg-white/95 backdrop-blur-sm py-8 px-6 shadow-organic rounded-3xl border border-stone-100 sm:px-10"
      >
        <RouterView />
      </div>
    </div>
  </div>
</template>
