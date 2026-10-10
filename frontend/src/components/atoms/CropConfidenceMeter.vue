<template>
  <div
    class="confidence-meter w-24 h-24 relative"
    role="img"
    :aria-label="t('farmer.analysis.confidence_aria', { score })"
  >
    <Doughnut :data="chartData" :options="chartOptions" aria-hidden="true" />
    <div
      class="confidence-label absolute inset-0 flex items-center justify-center text-sm font-bold text-stone-800"
    >
      {{ score }}%
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { DESIGN_COLORS, DESIGN_STATUS_COLORS } from '@/constants/designTokens'
const { t } = useI18n()
const CONFIDENCE_LIMITS = { LOW: 50, HIGH: 75, MAX: 100 }
import { Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'

ChartJS.register(ArcElement, Tooltip, Legend)

const props = defineProps({
  score: {
    type: Number,
    required: true,
  },
})

const chartData = computed(() => {
  // Organic palette: moss green (high), harvest amber (medium), red (low)
  let color = DESIGN_COLORS.moss[500] // moss-500 for high confidence
  if (props.score < CONFIDENCE_LIMITS.LOW)
    color = DESIGN_STATUS_COLORS.error // red-600 for low
  else if (props.score < CONFIDENCE_LIMITS.HIGH) color = DESIGN_COLORS.harvest[500] // harvest-500 for medium

  return {
    labels: ['Confidence', 'Remaining'],
    datasets: [
      {
        backgroundColor: [color, DESIGN_COLORS.stone[200]], // stone-200 for remaining track
        data: [props.score, CONFIDENCE_LIMITS.MAX - props.score],
        borderWidth: 0,
      },
    ],
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '75%',
  plugins: {
    legend: { display: false },
    tooltip: { enabled: false },
  },
}
</script>
