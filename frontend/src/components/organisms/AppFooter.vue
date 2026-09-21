<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
  <footer
    class="relative bg-gradient-to-br from-soil-900 to-soil-800 text-stone-300 overflow-hidden"
  >
    <!-- Organic wave divider at top -->
    <div class="absolute top-0 left-0 right-0 overflow-hidden leading-none pointer-events-none">
      <svg
        viewBox="0 0 1440 50"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        preserveAspectRatio="none"
        class="w-full h-10 sm:h-14"
      >
        <path
          d="M0 0L60 8C120 16 240 32 360 35C480 38 600 28 720 22C840 16 960 16 1080 22C1200 28 1320 38 1380 43L1440 48V0H0Z"
          fill="white"
          fill-opacity="0.05"
        />
      </svg>
    </div>

    <!-- Decorative organic blob -->
    <div
      class="absolute top-0 right-0 w-96 h-96 bg-moss-500/5 rounded-full translate-x-1/3 -translate-y-1/2 blur-3xl pointer-events-none"
      aria-hidden="true"
    ></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 relative z-10">
      <div class="flex flex-col md:flex-row justify-between items-start gap-10">
        <!-- Brand -->
        <div class="max-w-sm">
          <div class="flex items-center gap-2 mb-3">
            <span class="text-2xl">🌾</span>
            <span class="text-2xl font-bold text-white tracking-tight font-serif">YieldGrid</span>
          </div>
          <p class="text-sm text-stone-400 leading-relaxed">
            Empowering farmers and buyers with AI-driven crop intelligence and direct marketplace
            tools. From soil to sale.
          </p>
          <!-- Organic accent line -->
          <div
            class="mt-5 h-px w-16 bg-gradient-to-r from-moss-500 to-harvest-500 rounded-full"
          ></div>
        </div>

        <!-- Links grid -->
        <div class="flex flex-wrap gap-10">
          <div class="flex flex-col gap-2">
            <p class="text-[10px] font-bold text-stone-500 uppercase tracking-widest mb-1">
              Platform
            </p>
            <RouterLink
              to="/about"
              class="text-sm text-stone-400 hover:text-white transition-colors duration-200"
              >About Us</RouterLink
            >
            <RouterLink
              to="/contact"
              class="text-sm text-stone-400 hover:text-white transition-colors duration-200"
              >Contact</RouterLink
            >
          </div>
          <div class="flex flex-col gap-2">
            <p class="text-[10px] font-bold text-stone-500 uppercase tracking-widest mb-1">Legal</p>
            <a
              href="#"
              class="text-sm text-stone-400 hover:text-white transition-colors duration-200"
              >Privacy Policy</a
            >
            <a
              href="#"
              class="text-sm text-stone-400 hover:text-white transition-colors duration-200"
              >Terms of Service</a
            >
          </div>
        </div>
      </div>

      <!-- Bottom bar -->
      <div
        class="mt-12 pt-6 border-t border-soil-700/50 flex flex-col sm:flex-row items-center justify-between gap-3"
      >
        <p class="text-xs text-stone-600">
          © {{ new Date().getFullYear() }} YieldGrid. All rights reserved.
        </p>
        <div class="flex items-center gap-1.5">
          <span class="text-xs text-stone-600">Grown with</span>
          <span class="text-xs text-moss-400 font-semibold">🌱 precision agriculture</span>
        </div>
      </div>
    </div>
  </footer>
</template>
