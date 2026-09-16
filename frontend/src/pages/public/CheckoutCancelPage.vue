<script setup>
import { XCircleIcon } from '@heroicons/vue/24/solid'
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 text-center">
        <XCircleIcon class="mx-auto h-16 w-16 text-red-500 mb-4" />
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Payment Cancelled</h2>
        <p class="text-sm text-gray-500 mb-6">
          Your payment was cancelled or failed. No charges were made.
        </p>

        <router-link
          to="/marketplace"
          class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-farm-500"
        >
          Return to Marketplace
        </router-link>
      </div>
    </div>
  </div>
</template>
