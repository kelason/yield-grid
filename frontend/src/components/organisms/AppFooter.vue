<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
  <footer class="bg-earth-900 text-earth-100 py-12">
    <div
      class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center"
    >
      <div class="mb-4 md:mb-0">
        <span class="text-2xl font-bold text-white">YieldGrid</span>
        <p class="mt-2 text-sm text-earth-300">Empowering farmers and buyers.</p>
      </div>
      <div class="flex space-x-6 text-sm">
        <RouterLink to="/about" class="hover:text-white transition-colors">About Us</RouterLink>
        <RouterLink to="/contact" class="hover:text-white transition-colors">Contact</RouterLink>
        <a href="#" class="hover:text-white transition-colors">Privacy Policy</a>
      </div>
    </div>
  </footer>
</template>
