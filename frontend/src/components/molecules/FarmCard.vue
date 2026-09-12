<script setup>
import AppCard from '../atoms/AppCard.vue'

defineProps({
  farm: {
    type: Object,
    required: true
  }
})

defineEmits(['view-plots'])
</script>

<template>
  <AppCard class="hover:shadow-md transition-shadow duration-200">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-medium text-gray-900 truncate">{{ farm.name }}</h3>
      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-farm-100 text-farm-800">
        {{ farm.plots_count || 0 }} Plots
      </span>
    </div>
    <div class="text-sm text-gray-500 space-y-1 mb-6">
      <p v-if="farm.city || farm.state">{{ farm.city }}<span v-if="farm.city && farm.state">, </span>{{ farm.state }}</p>
      <p v-if="farm.total_area">Total Area: <span class="font-medium text-gray-900">{{ farm.total_area }} ha</span></p>
    </div>
    <button
      @click="$emit('view-plots', farm.id)"
      class="w-full inline-flex justify-center items-center px-4 py-2 border border-farm-300 shadow-sm text-sm font-medium rounded-md text-farm-700 bg-white hover:bg-farm-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-farm-500"
    >
      Manage Plots
    </button>
  </AppCard>
</template>
