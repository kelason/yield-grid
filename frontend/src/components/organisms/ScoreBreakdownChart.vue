<script setup>
import { onMounted, ref } from 'vue'
import { InformationCircleIcon } from '@heroicons/vue/24/outline'
import { CREDIT_DIMENSIONS } from '@/constants/creditScoring'

const STAGGER_DELAY_MS = 90

const props = defineProps({
  dimensionScores: {
    type: Object,
    required: true,
  },
})

const revealed = ref(false)
const expandedKey = ref(null)

onMounted(() => {
  requestAnimationFrame(() => {
    revealed.value = true
  })
})

function toggleDescription(key) {
  expandedKey.value = expandedKey.value === key ? null : key
}

function barColor(score) {
  if (score >= 70) {
    return 'bg-moss-500'
  }
  if (score >= 40) {
    return 'bg-harvest-500'
  }
  return 'bg-stone-400'
}

function barWidth(dimension) {
  const score = props.dimensionScores[dimension.key] ?? 0
  return revealed.value ? `${Math.min(100, Math.max(0, score))}%` : '0%'
}
</script>

<template>
  <div class="bg-white rounded-2xl shadow-soft border border-stone-200 p-6">
    <h3 class="font-serif text-2xl font-bold text-stone-900">Score Breakdown</h3>
    <div class="mt-4 space-y-4">
      <div v-for="(dimension, index) in CREDIT_DIMENSIONS" :key="dimension.key">
        <div class="flex items-baseline justify-between gap-2">
          <span class="inline-flex items-center gap-1">
            <span class="text-sm font-medium text-soil-700">
              {{ dimension.label }}
              <span class="text-stone-400 font-light">({{ dimension.weightPct }}%)</span>
            </span>
            <button
              type="button"
              :aria-expanded="expandedKey === dimension.key"
              :aria-controls="`dimension-info-${dimension.key}`"
              :aria-label="`About ${dimension.label}`"
              :title="`About ${dimension.label}`"
              class="rounded-full p-0.5 text-dew-600 transition-colors duration-200 hover:bg-dew-100 hover:text-dew-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500"
              @click="toggleDescription(dimension.key)"
            >
              <InformationCircleIcon class="h-4 w-4" aria-hidden="true" />
            </button>
          </span>
          <span class="text-sm font-semibold text-stone-900">
            {{ dimensionScores[dimension.key] ?? 0 }}/100
          </span>
        </div>
        <p
          v-if="expandedKey === dimension.key"
          :id="`dimension-info-${dimension.key}`"
          class="mt-1.5 rounded-xl border border-dew-100 bg-dew-50 px-3 py-2 text-sm font-light leading-relaxed text-stone-600"
        >
          {{ dimension.description }}
        </p>
        <div class="mt-1.5 h-2.5 rounded-full bg-stone-100 overflow-hidden">
          <div
            class="h-full rounded-full transition-all duration-700 ease-out"
            :class="barColor(dimensionScores[dimension.key] ?? 0)"
            :style="{
              width: barWidth(dimension),
              transitionDelay: `${index * STAGGER_DELAY_MS}ms`,
            }"
          />
        </div>
      </div>
    </div>
  </div>
</template>
