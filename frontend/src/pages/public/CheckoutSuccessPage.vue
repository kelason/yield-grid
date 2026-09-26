<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { CheckCircleIcon, ClockIcon } from '@heroicons/vue/24/solid'
import { useApi } from '@/composables/useApi'

const route = useRoute()
const api = useApi()
const status = ref('verifying') // verifying, completed, pending

onMounted(async () => {
  const sessionId = route.query.session_id
  if (sessionId) {
    try {
      const response = await api.get(`/checkout/${sessionId}/verify`)
      status.value = response.data.status || 'completed'
    } catch (err) {
      console.error('Failed to verify checkout session:', err)
      status.value = 'pending'
    }
  } else {
    status.value = 'completed'
  }
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 text-center">
        <template v-if="status === 'verifying'">
          <ClockIcon class="mx-auto h-16 w-16 text-yellow-500 mb-4 animate-pulse" />
          <h2 class="text-2xl font-bold text-gray-900 mb-2">Verifying Payment...</h2>
          <p class="text-sm text-gray-500 mb-6">
            Please wait while we confirm your payment with PayMongo.
          </p>
        </template>

        <template v-else-if="status === 'completed'">
          <CheckCircleIcon class="mx-auto h-16 w-16 text-green-500 mb-4" />
          <h2 class="text-2xl font-bold text-gray-900 mb-2">Payment Successful!</h2>
          <p class="text-sm text-gray-500 mb-6">
            Your purchase has been processed. The farmer will be notified shortly.
          </p>
        </template>

        <template v-else>
          <ClockIcon class="mx-auto h-16 w-16 text-yellow-500 mb-4" />
          <h2 class="text-2xl font-bold text-gray-900 mb-2">Payment Pending</h2>
          <p class="text-sm text-gray-500 mb-6">
            Your payment is still being processed. It may take a few moments to confirm.
          </p>
        </template>

        <router-link
          v-if="status !== 'verifying'"
          to="/dashboard/buyer/purchases"
          class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
        >
          View My Purchases
        </router-link>
      </div>
    </div>
  </div>
</template>
