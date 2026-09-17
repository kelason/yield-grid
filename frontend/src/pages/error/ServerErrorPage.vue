<template>
  <div class="min-h-screen bg-earth-50 px-4 py-16 sm:px-6 sm:py-24 md:grid md:place-items-center lg:px-8">
    <div class="max-w-max mx-auto">
      <main class="sm:flex">
        <p class="text-4xl font-extrabold text-red-600 sm:text-5xl">500</p>
        <div class="sm:ml-6">
          <div class="sm:border-l sm:border-gray-200 sm:pl-6">
            <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight sm:text-5xl">Server Error</h1>
            <p class="mt-1 text-base text-gray-500">Something went wrong on our end. Please try again later.</p>
          </div>
          <div class="mt-10 flex space-x-3 sm:border-l sm:border-transparent sm:pl-6">
            <AppButton
              to="/"
              variant="primary"
            >
              Go back home
            </AppButton>
            <AppButton
              to="/contact"
              variant="outline"
            >
              Contact support
            </AppButton>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import AppButton from '@/components/atoms/AppButton.vue'
</script>
