<script setup>
import StatusBadge from '../atoms/StatusBadge.vue'
import PriceTag from '../atoms/PriceTag.vue'
import { CalendarIcon, MapPinIcon, UserIcon } from '@heroicons/vue/24/outline'

defineProps({
  contract: {
    type: Object,
    required: true,
  },
})

defineEmits(['view-details', 'purchase'])
</script>

<template>
  <div
    class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-100 hover:shadow-md transition-shadow duration-200 hover:border-farm-200 transform hover:-translate-y-0.5 transition-all duration-300 flex flex-col h-full"
  >
    <div class="h-1 bg-gradient-to-r from-farm-500 to-farm-600 w-full"></div>

    <div class="p-5 flex-grow flex flex-col">
      <div class="flex justify-between items-start mb-4">
        <div>
          <h3
            class="text-lg font-bold text-gray-900 leading-tight mb-1 line-clamp-2"
            :title="contract.title"
          >
            {{ contract.title }}
          </h3>
          <p class="text-sm font-medium text-farm-600">{{ contract.crop_name }}</p>
        </div>
        <StatusBadge :status="contract.status" size="sm" class="ml-2 flex-shrink-0" />
      </div>

      <div class="space-y-2 mb-6 flex-grow">
        <div class="flex items-center text-sm text-gray-600">
          <CalendarIcon class="h-4 w-4 mr-2 text-gray-400" aria-hidden="true" />
          <span
            >Harvest:
            <span class="font-medium text-gray-900">{{
              contract.estimated_harvest_date
            }}</span></span
          >
        </div>

        <div class="flex items-center text-sm text-gray-600">
          <UserIcon class="h-4 w-4 mr-2 text-gray-400" aria-hidden="true" />
          <span class="truncate">{{ contract.farmer?.name || 'Farmer' }}</span>
        </div>

        <div class="flex items-center text-sm text-gray-600">
          <MapPinIcon class="h-4 w-4 mr-2 text-gray-400" aria-hidden="true" />
          <span class="truncate">{{
            contract.farmer?.location || contract.farmer?.farm_name || 'Location'
          }}</span>
        </div>
      </div>

      <div class="pt-4 border-t border-gray-100 mt-auto flex items-end justify-between">
        <div>
          <div class="text-xs text-gray-500 mb-1">Total for {{ contract.quantity_kg }}kg</div>
          <PriceTag :amount="contract.total_price" :currency="contract.currency" size="md" />
        </div>

        <button
          @click="$emit('view-details', contract)"
          class="inline-flex items-center px-4 py-2 border border-farm-200 rounded-md shadow-sm text-sm font-medium text-farm-700 bg-white hover:bg-farm-50 hover:border-farm-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-farm-500 transition-colors"
        >
          View Details
        </button>
      </div>
    </div>
  </div>
</template>
