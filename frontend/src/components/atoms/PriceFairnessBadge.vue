<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { usePriceGuide } from '@/composables/usePriceGuide'
import { PRICE_GUIDE, PRICE_TIER } from '@/constants/prices'

const { t } = useI18n()

const props = defineProps({
  listingPrice: {
    type: Number,
    required: true,
  },
  guide: {
    type: Object,
    default: null,
  },
  cropName: {
    type: String,
    default: '',
  },
})

const { fetchGuide } = usePriceGuide()
const fetchedGuide = ref(null)

watch(
  () => [props.cropName, props.guide],
  ([crop, guide]) => {
    if (!guide && crop) {
      fetchGuide(crop).then((result) => {
        fetchedGuide.value = result
      })
    }
  },
  { immediate: true },
)

const effectiveGuide = computed(() => props.guide ?? fetchedGuide.value)

const referencePrice = computed(() => {
  const tiers = effectiveGuide.value?.tiers ?? {}
  return (
    tiers[PRICE_TIER.FARMGATE]?.price_per_kg ??
    tiers[PRICE_TIER.ESTIMATE]?.price_per_kg ??
    tiers[PRICE_TIER.WHOLESALE]?.price_per_kg ??
    tiers[PRICE_TIER.RETAIL]?.price_per_kg ??
    null
  )
})

const verdict = computed(() => {
  if (
    !effectiveGuide.value?.available ||
    referencePrice.value === null ||
    referencePrice.value <= 0 ||
    !Number.isFinite(props.listingPrice)
  ) {
    return null
  }

  const diffPct = ((props.listingPrice - referencePrice.value) / referencePrice.value) * 100

  if (Math.abs(diffPct) <= PRICE_GUIDE.FAIR_BAND_PCT) {
    return { labelKey: 'market.fairness.fair', classes: 'bg-moss-100 text-moss-800' }
  }

  if (diffPct > 0) {
    return { labelKey: 'market.fairness.above', classes: 'bg-harvest-100 text-harvest-800' }
  }

  return { labelKey: 'market.fairness.below', classes: 'bg-dew-50 text-dew-700' }
})
</script>

<template>
  <span
    v-if="verdict"
    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
    :class="verdict.classes"
    :aria-label="t('market.fairness.verdict_aria', { label: t(verdict.labelKey) })"
  >
    {{ t(verdict.labelKey) }}
  </span>
</template>
