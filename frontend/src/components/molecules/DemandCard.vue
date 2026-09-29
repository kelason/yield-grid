<script setup>
import AppCard from '../atoms/AppCard.vue'
import StatusBadge from '../atoms/StatusBadge.vue'
import PriceTag from '../atoms/PriceTag.vue'
import { MapPinIcon } from '@heroicons/vue/24/outline'

defineProps({
  demand: { type: Object, required: true },
})

defineEmits(['view'])

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Intl.DateTimeFormat('en-PH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(new Date(dateStr))
}

function formatDistance(meters) {
  if (meters == null) return null
  if (meters < 1000) return `${Math.round(meters)} m away`
  return `${(meters / 1000).toFixed(1)} km away`
}

function remainingPercent(demand) {
  if (!demand.quantity_kg) return 0
  const percent = (demand.remaining_quantity_kg / demand.quantity_kg) * 100
  return Math.min(100, Math.max(0, Math.round(percent)))
}
</script>

<template>
  <AppCard
    padding="p-5"
    :hover="true"
    class="cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500"
    @click="$emit('view', demand)"
  >
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <h3 class="font-serif text-xl font-bold text-stone-900 truncate">
          {{ demand.title }}
        </h3>
        <p class="text-sm text-stone-500 mt-0.5">
          {{ demand.crop_name }} · {{ demand.remaining_quantity_kg }} of {{ demand.quantity_kg }} kg
          still needed
        </p>
      </div>
      <StatusBadge :status="demand.status" size="sm" />
    </div>

    <div
      class="mt-3 h-1.5 rounded-full bg-stone-100 overflow-hidden"
      role="progressbar"
      :aria-valuenow="remainingPercent(demand)"
      aria-valuemin="0"
      aria-valuemax="100"
      :aria-label="`${demand.remaining_quantity_kg} of ${demand.quantity_kg} kilograms still needed`"
    >
      <div
        class="h-full rounded-full bg-gradient-to-r from-moss-500 to-moss-600 transition-all duration-300"
        :style="{ width: `${remainingPercent(demand)}%` }"
      ></div>
    </div>

    <div class="flex items-center justify-between mt-4">
      <p>
        <PriceTag :amount="demand.target_price_per_kg" size="md" />
        <span class="text-sm text-stone-500">/kg target</span>
      </p>
      <div class="text-right text-xs text-stone-500">
        <p>
          Needed by
          <span class="font-medium text-stone-700">{{ formatDate(demand.needed_by_date) }}</span>
        </p>
        <p v-if="demand.location_summary" class="flex items-center justify-end gap-1 mt-1">
          <MapPinIcon class="h-3.5 w-3.5 text-stone-400" aria-hidden="true" />
          {{ demand.location_summary }}
          <span v-if="formatDistance(demand.distance_m)" class="text-moss-700 font-medium">
            · {{ formatDistance(demand.distance_m) }}
          </span>
        </p>
        <p v-if="demand.offers_count != null" class="mt-1">
          {{ demand.offers_count }} offer{{ demand.offers_count === 1 ? '' : 's' }}
        </p>
      </div>
    </div>
  </AppCard>
</template>
