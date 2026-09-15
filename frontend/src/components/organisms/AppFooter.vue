<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
  <footer class="bg-gradient-to-br from-earth-900 to-earth-800 text-earth-100 border-t-2 border-farm-500/20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="flex flex-col md:flex-row justify-between items-start gap-8">

        <!-- Brand -->
        <div>
          <div class="flex items-center gap-2 mb-3">
            <span class="text-xl">🌾</span>
            <span class="text-2xl font-extrabold text-white tracking-tight">YieldGrid</span>
          </div>
          <p class="text-sm text-earth-400 max-w-xs leading-relaxed">
            Empowering farmers and buyers with AI-driven crop intelligence and marketplace tools.
          </p>
        </div>

        <!-- Links -->
        <div class="flex flex-col gap-2">
          <p class="text-[10px] font-bold text-earth-500 uppercase tracking-widest mb-1">Company</p>
          <RouterLink to="/about" class="text-sm text-earth-300 hover:text-white transition-colors duration-200">About Us</RouterLink>
          <RouterLink to="/contact" class="text-sm text-earth-300 hover:text-white transition-colors duration-200">Contact</RouterLink>
          <a href="#" class="text-sm text-earth-300 hover:text-white transition-colors duration-200">Privacy Policy</a>
        </div>

      </div>

      <!-- Bottom bar -->
      <div class="mt-10 pt-6 border-t border-earth-700/50 flex flex-col sm:flex-row items-center justify-between gap-3">
        <p class="text-xs text-earth-500">© {{ new Date().getFullYear() }} YieldGrid. All rights reserved.</p>
        <div class="flex items-center gap-1">
          <span class="text-xs text-earth-600">Built with</span>
          <span class="text-xs text-farm-400 font-semibold">🌱 precision agriculture</span>
        </div>
      </div>
    </div>
  </footer>
</template>
