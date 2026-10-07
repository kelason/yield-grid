<script setup>
import { RouterView } from 'vue-router'
import AppLogo from '../atoms/AppLogo.vue'
import AppCard from '../atoms/AppCard.vue'
</script>
<template>
  <main
    id="main-content"
    tabindex="-1"
    class="flex min-h-screen flex-col items-center justify-center bg-stone-100 px-4 py-12 font-sans focus:outline-none sm:px-6"
  >
    <div class="w-full max-w-md">
      <div class="mb-10 text-center">
        <AppLogo />
        <h1 class="mt-8 font-serif text-3xl font-bold tracking-tight text-stone-900">
          <slot name="title">Welcome Back</slot>
        </h1>
        <p v-if="$slots.subtitle" class="mt-3 text-base text-stone-600"><slot name="subtitle" /></p>
      </div>
      <AppCard padding="p-6 sm:p-8"><RouterView /></AppCard>
    </div>
  </main>
</template>
