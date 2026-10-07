<script setup>
import { computed } from 'vue'

const CHART_WIDTH = 600
const CHART_HEIGHT = 180
const CHART_PADDING = 28

const props = defineProps({
  history: {
    type: Array,
    default: () => [],
  },
})

const points = computed(() => {
  const items = [...(props.history || [])].reverse()
  if (items.length === 0) {
    return []
  }
  const spanX = items.length === 1 ? 0 : (CHART_WIDTH - CHART_PADDING * 2) / (items.length - 1)
  return items.map((item, index) => {
    const score = Math.min(100, Math.max(0, item.overall_score ?? 0))
    return {
      x: CHART_PADDING + spanX * index,
      y: CHART_PADDING + (1 - score / 100) * (CHART_HEIGHT - CHART_PADDING * 2),
      score,
      date: item.snapshot_date ? new Date(item.snapshot_date).toLocaleDateString() : '',
    }
  })
})

const polylinePoints = computed(() => points.value.map((p) => `${p.x},${p.y}`).join(' '))
const hasEnoughData = computed(() => points.value.length >= 2)
</script>

<template>
  <div class="bg-white rounded-2xl shadow-soft border border-stone-200 p-6">
    <h3 class="font-serif text-2xl font-bold text-stone-900">Score History</h3>
    <div v-if="!hasEnoughData" class="mt-4 text-center py-8">
      <p class="text-base text-stone-600 font-light leading-relaxed">
        Your score trend will appear here after your next daily recalculation.
      </p>
    </div>
    <div v-else class="overflow-x-auto">
      <svg
        :viewBox="`0 0 ${CHART_WIDTH} ${CHART_HEIGHT}`"
        class="w-full min-w-[600px] h-auto mt-4"
        role="img"
        aria-label="Trust score trend over time"
      >
        <line
          :x1="CHART_PADDING"
          :y1="CHART_HEIGHT - CHART_PADDING"
          :x2="CHART_WIDTH - CHART_PADDING"
          :y2="CHART_HEIGHT - CHART_PADDING"
          class="stroke-stone-200"
          stroke-width="1"
        />
        <polyline
          :points="polylinePoints"
          fill="none"
          stroke-width="3"
          stroke-linecap="round"
          stroke-linejoin="round"
          class="stroke-moss-600 transition-all duration-700 ease-out motion-reduce:transition-none"
        />
        <g v-for="(point, index) in points" :key="index">
          <circle
            :cx="point.x"
            :cy="point.y"
            r="4.5"
            class="fill-moss-600 stroke-white"
            stroke-width="2"
          />
          <text
            :x="point.x"
            :y="point.y - 10"
            text-anchor="middle"
            class="fill-stone-900"
            font-size="11"
            font-weight="600"
          >
            {{ point.score }}
          </text>
          <text
            :x="point.x"
            :y="CHART_HEIGHT - 8"
            text-anchor="middle"
            class="fill-stone-500"
            font-size="10"
          >
            {{ point.date }}
          </text>
        </g>
      </svg>
    </div>
  </div>
</template>
