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
    class="bg-white overflow-hidden shadow-soft rounded-2xl border border-stone-200 hover:shadow-organic hover:border-stone-300 transition-all duration-300 transform hover:-translate-y-1 flex flex-col h-full"
  >
    <div class="h-1.5 bg-gradient-to-r from-moss-500 to-moss-600 w-full"></div>

    <div class="p-5 flex-grow flex flex-col">
      <div class="flex justify-between items-start mb-4 gap-3">
        <div class="min-w-0 flex-1">
          <h3
            class="text-lg font-bold text-stone-900 leading-tight mb-1 truncate font-serif"
            :title="contract.title"
          >
            {{ contract.title }}
          </h3>
          <div class="flex items-center gap-2 mb-2 flex-wrap sm:flex-nowrap">
            <p class="text-sm font-semibold text-moss-600 truncate">{{ contract.crop_name }}</p>
          </div>
          <div
            v-if="contract.is_harvest_available"
            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-moss-100 text-moss-800"
          >
            🟢 Harvest Available
          </div>
          <div
            v-else
            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-harvest-100 text-harvest-800"
          >
            🕐 Incoming Harvest
          </div>
        </div>
        <StatusBadge :status="contract.status" size="sm" class="ml-2 flex-shrink-0" />
      </div>

      <div class="space-y-2 mb-6 flex-grow">
        <div class="flex items-center text-sm text-stone-600">
          <CalendarIcon class="h-4 w-4 mr-2 text-stone-400" aria-hidden="true" />
          <span
            >Harvest:
            <span class="font-medium text-stone-900">{{
              contract.estimated_harvest_date
            }}</span></span
          >
        </div>

        <div class="flex items-center text-sm text-stone-600">
          <UserIcon class="h-4 w-4 mr-2 text-stone-400" aria-hidden="true" />
          <span class="truncate">{{ contract.farmer?.name || 'Farmer' }}</span>
        </div>

        <div class="flex items-center text-sm text-stone-600">
          <MapPinIcon class="h-4 w-4 mr-2 text-stone-400" aria-hidden="true" />
          <span class="truncate">{{
            contract.farmer?.location || contract.farmer?.farm_name || 'Location'
          }}</span>
        </div>
      </div>

      <div class="pt-4 border-t border-stone-100 mt-auto flex items-end justify-between">
        <div>
          <div class="text-xs text-stone-500 mb-1">Total for {{ contract.quantity_kg }}kg</div>
          <PriceTag :amount="contract.total_price" :currency="contract.currency" size="md" />
        </div>

        <button
          @click="$emit('view-details', contract)"
          class="inline-flex items-center px-4 py-2 border border-moss-200 rounded-xl shadow-sm text-sm font-medium text-moss-700 bg-white hover:bg-moss-50 hover:border-moss-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-moss-500 transition-all duration-200 hover:scale-[1.02]"
        >
          View Details
        </button>
      </div>
    </div>
  </div>
</template>
