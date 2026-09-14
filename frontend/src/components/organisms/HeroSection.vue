<script setup>
import AppButton from '../atoms/AppButton.vue'
</script>

<template>
  <div class="relative bg-earth-50 overflow-hidden min-h-[90vh] flex items-center">
    <!-- Background decorative element -->
    <div
      class="absolute inset-y-0 right-0 w-1/2 bg-gradient-to-l from-farm-100 to-transparent opacity-50 transform translate-x-1/4 skew-x-12 hidden lg:block"
    ></div>

    <div class="max-w-7xl mx-auto w-full relative z-10 px-4 sm:px-6 lg:px-8">
      <div class="lg:w-1/2 py-16 lg:py-32">
        <h1
          class="text-5xl tracking-tight font-extrabold text-gray-900 sm:text-6xl md:text-7xl leading-tight"
        >
          <span class="block mb-2">Smarter farming</span>
          <span
            class="block text-transparent bg-clip-text bg-gradient-to-r from-farm-500 to-farm-700"
            >for a better harvest</span
          >
        </h1>
        <p class="mt-6 text-lg text-gray-600 sm:text-xl md:mt-8 md:max-w-2xl font-light">
          Connect with local buyers, plan your crops with AI, and manage your farm's success all in
          one premium platform.
        </p>
        <div class="mt-10 sm:flex sm:justify-start gap-4">
          <div class="transform hover:-translate-y-1 transition-all duration-300">
            <AppButton
              variant="primary"
              size="lg"
              rounded="full"
              @click="$router.push('/auth/register')"
            >
              Get Started Today
            </AppButton>
          </div>
          <div class="mt-4 sm:mt-0 transform hover:-translate-y-1 transition-all duration-300">
            <AppButton variant="outline" size="lg" rounded="full" @click="$router.push('/about')">
              Discover Features
            </AppButton>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
