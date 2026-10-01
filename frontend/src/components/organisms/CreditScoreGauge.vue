<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import ScoreTierBadge from '@/components/molecules/ScoreTierBadge.vue'

const GAUGE_RADIUS = 54
const GAUGE_CIRCUMFERENCE = 2 * Math.PI * GAUGE_RADIUS
const COUNT_UP_DURATION_MS = 1000

const props = defineProps({
  score: {
    type: Number,
    required: true,
  },
  tier: {
    type: String,
    required: true,
  },
  tierLabel: {
    type: String,
    default: '',
  },
  ringColor: {
    type: String,
    default: '#4a8c42',
  },
})

const displayedScore = ref(0)
let animationFrame = null

function animateTo(target) {
  if (animationFrame) {
    cancelAnimationFrame(animationFrame)
  }
  const start = displayedScore.value
  const startedAt = performance.now()

  const tick = (now) => {
    const progress = Math.min(1, (now - startedAt) / COUNT_UP_DURATION_MS)
    displayedScore.value = Math.round(start + (target - start) * progress)
    if (progress < 1) {
      animationFrame = requestAnimationFrame(tick)
    }
  }

  animationFrame = requestAnimationFrame(tick)
}

const dashOffset = computed(
  () => GAUGE_CIRCUMFERENCE * (1 - Math.min(100, Math.max(0, displayedScore.value)) / 100),
)

onMounted(() => animateTo(props.score))
watch(
  () => props.score,
  (target) => animateTo(target),
)
</script>

<template>
  <div class="bg-white rounded-2xl shadow-soft border border-stone-200 p-6 text-center">
    <svg viewBox="0 0 130 130" class="w-44 h-44 mx-auto" role="img" aria-label="Trust score gauge">
      <circle cx="65" cy="65" :r="GAUGE_RADIUS" fill="none" stroke="#e7e5e4" stroke-width="12" />
      <circle
        cx="65"
        cy="65"
        :r="GAUGE_RADIUS"
        fill="none"
        :stroke="ringColor"
        stroke-width="12"
        stroke-linecap="round"
        :stroke-dasharray="GAUGE_CIRCUMFERENCE"
        :stroke-dashoffset="dashOffset"
        transform="rotate(-90 65 65)"
        class="transition-all duration-1000 ease-out"
      />
      <text
        x="65"
        y="63"
        text-anchor="middle"
        class="fill-stone-900"
        font-size="30"
        font-weight="700"
        data-testid="gauge-score"
      >
        {{ displayedScore }}
      </text>
      <text x="65" y="82" text-anchor="middle" class="fill-stone-500" font-size="12">/ 100</text>
    </svg>
    <div class="mt-3">
      <ScoreTierBadge :tier="tier" :label="tierLabel" />
    </div>
  </div>
</template>
