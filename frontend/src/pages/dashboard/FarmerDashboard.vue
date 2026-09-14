<script setup>
import { onMounted } from 'vue'
import { useFarmingStore } from '../../stores/farming'
import AppCard from '../../components/atoms/AppCard.vue'

const farmingStore = useFarmingStore()

onMounted(() => {
  farmingStore.fetchFarms()
})
</script>

<template>
  <div class="space-y-6">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
      <AppCard class="bg-gradient-to-r from-farm-500 to-farm-600 text-white border-none">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
              />
            </svg>
          </div>
          <div class="ml-5 w-0 flex-1">
            <dl>
              <dt class="text-sm font-medium text-farm-100 truncate">Total Farms</dt>
              <dd>
                <div class="text-3xl font-semibold text-white">{{ farmingStore.farms.length }}</div>
              </dd>
            </dl>
          </div>
        </div>
      </AppCard>

      <AppCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <svg
              class="h-6 w-6 text-gray-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"
              />
            </svg>
          </div>
          <div class="ml-5 w-0 flex-1">
            <dl>
              <dt class="text-sm font-medium text-gray-500 truncate">Total Plots</dt>
              <dd>
                <div class="text-3xl font-semibold text-gray-900">
                  {{ farmingStore.farms.reduce((acc, farm) => acc + (farm.plots_count || 0), 0) }}
                </div>
              </dd>
            </dl>
          </div>
        </div>
      </AppCard>
    </div>

    <h2 class="text-lg leading-6 font-medium text-gray-900 mt-8 mb-4">Recent Activity</h2>
    <AppCard>
      <div v-if="farmingStore.farms.length === 0" class="text-center py-8 text-gray-500">
        You haven't created any farms yet. Go to
        <router-link :to="{ name: 'farm-manager' }" class="text-farm-600 hover:underline"
          >My Farms</router-link
        >
        to get started.
      </div>
      <div v-else class="text-sm text-gray-600 py-4">
        Activity feed will be implemented in future phases.
      </div>
    </AppCard>
  </div>
</template>
