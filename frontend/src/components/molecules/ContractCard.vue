<script setup>
import { computed } from 'vue'
import AppButton from '../atoms/AppButton.vue'
import AppCard from '../atoms/AppCard.vue'
import StatusBadge from '../atoms/StatusBadge.vue'
import PriceTag from '../atoms/PriceTag.vue'
import PriceFairnessBadge from '../atoms/PriceFairnessBadge.vue'
import { CalendarIcon, FlagIcon, MapPinIcon, UserIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '../../stores/auth'
import VerifiedBadge from '../atoms/VerifiedBadge.vue'
import { isListingVerified } from '@/utils/verification'

const authStore = useAuthStore()

const props = defineProps({
  contract: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['view-details', 'purchase', 'report'])

const reportableType = computed(() => (props.contract.type === 'listing' ? 'listing' : 'contract'))

const isOwner = computed(() => {
  const userId = authStore.user?.id
  if (userId == null) return false
  const ownerId = props.contract.farmer?.id ?? props.contract.farmer_id
  return ownerId != null && String(userId) === String(ownerId)
})

const showVerifiedBadge = computed(
  () => props.contract.type === 'listing' && isListingVerified(props.contract),
)

function emitReport() {
  emit('report', {
    reportable_type: reportableType.value,
    reportable_id: props.contract.id,
    title: props.contract.title,
  })
}
</script>

<template>
  <AppCard variant="default" padding="p-0" class="flex flex-col h-full">
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
            <VerifiedBadge v-if="showVerifiedBadge" size="sm" />
          </div>
          <div
            v-if="contract.is_harvest_available"
            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-moss-100 text-moss-800"
          >
            Harvest Available
          </div>
          <div
            v-else
            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-harvest-100 text-harvest-800"
          >
            Incoming Harvest
          </div>
        </div>
        <StatusBadge :status="contract.status" size="sm" class="ml-2 flex-shrink-0" />
      </div>

      <div class="space-y-2 mb-6 flex-grow">
        <div class="flex items-center text-sm text-stone-600">
          <CalendarIcon class="h-4 w-4 mr-2 text-stone-500" aria-hidden="true" />
          <span
            >Harvest:
            <span class="font-medium text-stone-900">{{
              contract.estimated_harvest_date
            }}</span></span
          >
        </div>

        <div class="flex items-center text-sm text-stone-600">
          <UserIcon class="h-4 w-4 mr-2 text-stone-500" aria-hidden="true" />
          <span class="truncate">{{ contract.farmer?.name || 'Farmer' }}</span>
        </div>

        <div class="flex items-center text-sm text-stone-600">
          <MapPinIcon class="h-4 w-4 mr-2 text-stone-500" aria-hidden="true" />
          <span class="truncate">{{
            contract.farmer?.location || contract.farmer?.farm_name || 'Location'
          }}</span>
        </div>
      </div>

      <div
        class="pt-4 border-t border-stone-100 mt-auto flex flex-wrap items-end justify-between gap-3"
      >
        <div>
          <div class="text-xs text-stone-500 mb-1">Total for {{ contract.quantity_kg }}kg</div>
          <div class="flex flex-wrap items-center gap-2">
            <PriceTag :amount="contract.total_price" :currency="contract.currency" size="md" />
            <PriceFairnessBadge
              :listing-price="Number(contract.price_per_kg)"
              :crop-name="contract.crop_name"
            />
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <AppButton variant="outline" size="sm" @click="$emit('view-details', contract)">
            View Details
          </AppButton>
          <button
            v-if="!isOwner"
            type="button"
            aria-label="Report this listing"
            class="min-h-11 px-2 inline-flex items-center gap-1 text-sm font-medium text-soil-600 hover:text-soil-800 transition-colors motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500 rounded-xl"
            @click.stop="emitReport"
          >
            <FlagIcon class="w-4 h-4" aria-hidden="true" />
            Report
          </button>
        </div>
      </div>
    </div>
  </AppCard>
</template>
