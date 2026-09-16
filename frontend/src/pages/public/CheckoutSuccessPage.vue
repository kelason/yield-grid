<script setup>
import { CheckCircleIcon } from '@heroicons/vue/24/solid'
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 text-center">
        <CheckCircleIcon class="mx-auto h-16 w-16 text-green-500 mb-4" />
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Payment Successful!</h2>
        <p class="text-sm text-gray-500 mb-6">
          Your purchase has been processed. The farmer will be notified shortly.
        </p>

        <router-link
          to="/dashboard/buyer/purchases"
          class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-farm-600 hover:bg-farm-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-farm-500"
        >
          View My Purchases
        </router-link>
      </div>
    </div>
  </div>
</template>
