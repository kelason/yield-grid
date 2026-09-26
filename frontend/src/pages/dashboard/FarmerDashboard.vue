<script setup>
import { onMounted, computed } from 'vue'
import { useFarmingStore } from '../../stores/farming'
import AppCard from '../../components/atoms/AppCard.vue'

const farmingStore = useFarmingStore()

const totalPlots = computed(() =>
  farmingStore.farms.reduce((acc, farm) => acc + (farm.plots_count || 0), 0),
)

onMounted(() => {
  farmingStore.fetchFarms()
})
</script>

<template>
  <div class="space-y-8">
    <!-- KPI Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
      <!-- Total Farms — gradient card -->
      <AppCard variant="gradient" class="!rounded-2xl">
        <div class="flex items-center gap-4">
          <div
            class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center text-2xl flex-shrink-0 shadow-sm"
          >
            🏡
          </div>
          <div>
            <dt class="text-sm font-medium text-green-100">Total Farms</dt>
            <dd class="text-4xl font-bold text-white mt-1">{{ farmingStore.farms.length }}</dd>
          </div>
        </div>
      </AppCard>

      <!-- Total Plots -->
      <AppCard>
        <div class="flex items-center gap-4">
          <div
            class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-2xl flex-shrink-0"
          >
            🗺️
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Total Plots</dt>
            <dd class="text-4xl font-bold text-gray-900 mt-1">{{ totalPlots }}</dd>
          </div>
        </div>
      </AppCard>

      <!-- AI Recommendations -->
      <router-link :to="{ name: 'recommendations' }" class="block group">
        <AppCard
          class="transition-all duration-200 group-hover:border-green-200 group-hover:shadow-md"
        >
          <div class="flex items-center gap-4">
            <div
              class="w-12 h-12 rounded-xl bg-stone-50 flex items-center justify-center text-2xl flex-shrink-0 group-hover:scale-105 transition-transform duration-200"
            >
              🤖
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500">AI Advisor</dt>
              <dd class="text-sm font-semibold text-green-600 mt-1">View Recommendations →</dd>
              <dd class="text-xs text-gray-400">Crop advisor insights</dd>
            </div>
          </div>
        </AppCard>
      </router-link>
    </div>

    <!-- Recent Activity -->
    <div>
      <div class="flex items-center gap-3 mb-4">
        <h2 class="text-lg font-bold text-gray-900">Recent Activity</h2>
        <div class="flex-1 h-px bg-gradient-to-r from-gray-200 to-transparent"></div>
      </div>

      <!-- Skeleton loading state -->
      <AppCard v-if="farmingStore.loading">
        <div class="animate-pulse space-y-3">
          <div class="h-4 bg-gray-200 rounded w-3/4"></div>
          <div class="h-4 bg-gray-200 rounded w-1/2"></div>
          <div class="h-4 bg-gray-200 rounded w-2/3"></div>
        </div>
      </AppCard>

      <!-- Empty -->
      <AppCard v-else-if="farmingStore.farms.length === 0">
        <div class="text-center py-8">
          <div class="text-4xl mb-3">🌱</div>
          <h3 class="text-sm font-semibold text-gray-700 mb-1">No activity yet</h3>
          <p class="text-sm text-gray-500">
            Go to
            <router-link
              :to="{ name: 'farm-manager' }"
              class="text-green-600 hover:text-green-700 font-medium transition-colors"
            >
              My Farms
            </router-link>
            to get started.
          </p>
        </div>
      </AppCard>

      <!-- Placeholder -->
      <AppCard v-else>
        <div class="text-sm text-gray-500 py-2">Activity feed coming soon.</div>
      </AppCard>
    </div>
  </div>
</template>
