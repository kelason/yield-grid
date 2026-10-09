<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppCard from '../atoms/AppCard.vue'
import AppButton from '../atoms/AppButton.vue'
import StatusBadge from '../atoms/StatusBadge.vue'
import PriceTag from '../atoms/PriceTag.vue'
import PriceFairnessBadge from '../atoms/PriceFairnessBadge.vue'
import { FlagIcon, MapPinIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '../../stores/auth'

const authStore = useAuthStore()
const { t } = useI18n()

const props = defineProps({
  demand: { type: Object, required: true },
})

const emit = defineEmits(['view', 'report'])

const isOwner = computed(() => {
  const userId = authStore.user?.id
  if (userId == null) return false
  const ownerId = props.demand.buyer?.id ?? props.demand.buyer_id ?? props.demand.user_id
  return ownerId != null && String(userId) === String(ownerId)
})

function emitReport() {
  emit('report', {
    reportable_type: 'demand',
    reportable_id: props.demand.id,
    title: props.demand.title,
  })
}

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
  if (meters < 1000) return t('farmer.demand_card.m_away', { value: Math.round(meters) })
  return t('farmer.demand_card.km_away', { value: (meters / 1000).toFixed(1) })
}

function remainingPercent(demand) {
  if (!demand.quantity_kg) return 0
  const percent = (demand.remaining_quantity_kg / demand.quantity_kg) * 100
  return Math.min(100, Math.max(0, Math.round(percent)))
}
</script>

<template>
  <AppCard padding="p-5">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <h3 class="font-serif text-xl font-bold text-stone-900 truncate">
          {{ demand.title }}
        </h3>
        <p class="text-sm text-stone-500 mt-0.5">
          {{
            t('farmer.demand_card.qty_line', {
              crop: demand.crop_name,
              remaining: demand.remaining_quantity_kg,
              qty: demand.quantity_kg,
            })
          }}
        </p>
      </div>
      <StatusBadge :status="demand.status" size="sm" />
    </div>

    <progress
      :value="remainingPercent(demand)"
      max="100"
      :aria-label="
        t('farmer.demand_card.progress_aria', {
          remaining: demand.remaining_quantity_kg,
          qty: demand.quantity_kg,
        })
      "
      class="mt-3 w-full h-2 rounded-full accent-moss-600"
    />

    <div class="flex flex-wrap items-center justify-between gap-4 mt-4">
      <p>
        <PriceTag :amount="demand.target_price_per_kg" size="md" />
        <span class="text-sm text-stone-500">{{ t('farmer.demand_card.per_kg_target') }}</span>
        <PriceFairnessBadge
          :listing-price="Number(demand.target_price_per_kg)"
          :crop-name="demand.crop_name"
          class="ml-2"
        />
      </p>
      <div class="text-right text-xs text-stone-500">
        <p>
          {{ t('farmer.demand_card.needed_by') }}
          <span class="font-medium text-stone-700">{{ formatDate(demand.needed_by_date) }}</span>
        </p>
        <p v-if="demand.location_summary" class="flex items-center justify-end gap-1 mt-1">
          <MapPinIcon class="h-3.5 w-3.5 text-stone-500" aria-hidden="true" />
          {{ demand.location_summary }}
          <span v-if="formatDistance(demand.distance_m)" class="text-moss-700 font-medium">
            · {{ formatDistance(demand.distance_m) }}
          </span>
        </p>
        <p v-if="demand.offers_count != null" class="mt-1">
          {{ demand.offers_count }}
          {{
            demand.offers_count === 1
              ? t('farmer.demand_card.offer_one')
              : t('farmer.demand_card.offer_other')
          }}
        </p>
      </div>
    </div>
    <div class="mt-5 flex flex-wrap items-center gap-2">
      <AppButton
        variant="outline"
        class="flex-1"
        @click="$emit('view', demand)"
        :aria-label="t('farmer.demand_card.view_aria', { title: demand.title })"
        >{{ t('farmer.demand_card.view') }}</AppButton
      >
      <button
        v-if="!isOwner"
        type="button"
        :aria-label="t('farmer.demand_card.report_aria')"
        class="min-h-11 px-2 inline-flex items-center gap-1 text-sm font-medium text-soil-600 hover:text-soil-800 transition-colors motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500 rounded-xl"
        @click.stop="emitReport"
      >
        <FlagIcon class="w-4 h-4" aria-hidden="true" />
        {{ t('farmer.demand_card.report') }}
      </button>
    </div>
  </AppCard>
</template>
