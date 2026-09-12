<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
  <div class="bg-white">
    <!-- Hero Section -->
    <div class="relative bg-earth-50 overflow-hidden min-h-[90vh] flex items-center">
      <!-- Background decorative element -->
      <div class="absolute inset-y-0 right-0 w-1/2 bg-gradient-to-l from-farm-100 to-transparent opacity-50 transform translate-x-1/4 skew-x-12 hidden lg:block"></div>
      
      <div class="max-w-7xl mx-auto w-full relative z-10 px-4 sm:px-6 lg:px-8">
        <div class="lg:w-1/2 py-16 lg:py-32">
          <h1 class="text-5xl tracking-tight font-extrabold text-gray-900 sm:text-6xl md:text-7xl leading-tight">
            <span class="block mb-2">Smarter farming</span>
            <span class="block text-transparent bg-clip-text bg-gradient-to-r from-farm-500 to-farm-700">for a better harvest</span>
          </h1>
          <p class="mt-6 text-lg text-gray-600 sm:text-xl md:mt-8 md:max-w-2xl font-light">
            Connect with local buyers, plan your crops with AI, and manage your farm's success all in one premium platform.
          </p>
          <div class="mt-10 sm:flex sm:justify-start gap-4">
            <div class="rounded-full shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
              <RouterLink to="/auth/register" class="w-full flex items-center justify-center px-8 py-4 border border-transparent text-lg font-medium rounded-full text-white bg-gradient-to-r from-farm-500 to-farm-600 hover:from-farm-600 hover:to-farm-700 md:py-4 md:px-10">
                Get Started Today
              </RouterLink>
            </div>
            <div class="mt-4 sm:mt-0 rounded-full transition-all duration-300 hover:bg-farm-50 transform hover:-translate-y-1">
              <RouterLink to="/about" class="w-full flex items-center justify-center px-8 py-4 border-2 border-farm-200 text-lg font-medium rounded-full text-farm-700 bg-transparent hover:border-farm-300 md:py-4 md:px-10">
                Discover Features
              </RouterLink>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
