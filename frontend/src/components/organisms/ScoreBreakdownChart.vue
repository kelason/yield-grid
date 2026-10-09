<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { InformationCircleIcon } from '@heroicons/vue/24/outline'
import { CREDIT_DIMENSIONS, CREDIT_SCORE } from '@/constants/creditScoring'
const { t } = useI18n()

const GOOD_DIMENSION_SCORE = 70
const FAIR_DIMENSION_SCORE = 40

const props = defineProps({
  dimensionScores: {
    type: Object,
    required: true,
  },
})

const expandedKey = ref(null)

function toggleDescription(key) {
  expandedKey.value = expandedKey.value === key ? null : key
}

function barColor(score) {
  if (score >= GOOD_DIMENSION_SCORE) {
    return 'text-moss-600'
  }
  if (score >= FAIR_DIMENSION_SCORE) {
    return 'text-harvest-600'
  }
  return 'text-stone-500'
}

function barValue(dimension) {
  const score = props.dimensionScores?.[dimension.key] ?? CREDIT_SCORE.MIN_SCORE
  return Math.min(CREDIT_SCORE.MAX_SCORE, Math.max(CREDIT_SCORE.MIN_SCORE, score))
}
</script>

<template>
  <div class="bg-white rounded-2xl shadow-soft border border-stone-200 p-6">
    <h3 class="font-serif text-2xl font-bold text-stone-900">
      {{ t('farmer.credit_breakdown.title') }}
    </h3>
    <div class="mt-4 space-y-4">
      <div v-for="dimension in CREDIT_DIMENSIONS" :key="dimension.key">
        <div class="flex items-baseline justify-between gap-2">
          <span class="inline-flex items-center gap-1">
            <span class="text-sm font-medium text-soil-700">
              {{ t(dimension.labelKey) }}
              <span class="text-stone-500 font-normal">({{ dimension.weightPct }}%)</span>
            </span>
            <button
              type="button"
              :aria-expanded="expandedKey === dimension.key"
              :aria-controls="`dimension-info-${dimension.key}`"
              :aria-label="t('farmer.credit_breakdown.about', { label: t(dimension.labelKey) })"
              :title="t('farmer.credit_breakdown.about', { label: t(dimension.labelKey) })"
              class="min-h-11 min-w-11 inline-flex items-center justify-center rounded-full p-0.5 text-dew-700 transition-colors duration-200 hover:bg-dew-100 hover:text-dew-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500"
              @click="toggleDescription(dimension.key)"
            >
              <InformationCircleIcon class="h-4 w-4" aria-hidden="true" />
            </button>
          </span>
          <span class="text-sm font-semibold text-stone-900">
            {{ dimensionScores?.[dimension.key] ?? 0 }}/100
          </span>
        </div>
        <p
          v-if="expandedKey === dimension.key"
          :id="`dimension-info-${dimension.key}`"
          class="mt-1.5 rounded-xl border border-dew-100 bg-dew-50 px-3 py-2 text-sm font-normal leading-relaxed text-stone-600"
        >
          {{ t(dimension.descriptionKey) }}
        </p>
        <progress
          class="dimension-progress appearance-none bg-stone-100 mt-1.5 h-2.5 w-full rounded-full"
          :class="barColor(dimensionScores?.[dimension.key] ?? 0)"
          :value="barValue(dimension)"
          :max="CREDIT_SCORE.MAX_SCORE"
          :aria-label="t('farmer.credit_breakdown.score_aria', { label: t(dimension.labelKey) })"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.dimension-progress::-webkit-progress-bar {
  @apply rounded-full bg-stone-100;
}
.dimension-progress::-webkit-progress-value {
  @apply rounded-full bg-current;
}
.dimension-progress::-moz-progress-bar {
  @apply rounded-full bg-current;
}
</style>
