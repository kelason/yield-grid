<script setup>
import { RouterLink } from 'vue-router'
import AppLogo from '../atoms/AppLogo.vue'
</script>
<template>
  <footer
    class="border-t border-soil-700 bg-gradient-to-br from-soil-900 to-soil-800 text-stone-100"
  >
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
      <div class="flex flex-col justify-between gap-8 sm:flex-row">
        <div class="max-w-md">
          <AppLogo dark />
          <p class="mt-4 text-sm leading-relaxed text-stone-300">
            Crop intelligence and a direct marketplace for Filipino farmers and buyers. From soil to
            sale.
          </p>
        </div>
        <nav aria-label="Footer navigation" class="flex flex-wrap items-start gap-6">
          <RouterLink
            to="/about"
            class="inline-flex min-h-11 items-center rounded-xl text-sm text-stone-100 transition-colors hover:text-white"
            >About Us</RouterLink
          ><RouterLink
            to="/contact"
            class="inline-flex min-h-11 items-center rounded-xl text-sm text-stone-100 transition-colors hover:text-white"
            >Contact</RouterLink
          >
        </nav>
      </div>
      <p class="mt-8 border-t border-soil-600 pt-6 text-sm text-stone-300">
        © {{ new Date().getFullYear() }} YieldGrid. All rights reserved.
      </p>
    </div>
  </footer>
</template>
