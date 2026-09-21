<template>
  <div class="confidence-meter w-24 h-24 relative">
    <Doughnut :data="chartData" :options="chartOptions" />
    <div
      class="confidence-label absolute inset-0 flex items-center justify-center text-sm font-bold text-gray-800"
    >
      {{ score }}%
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
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
  let color = '#4a8c42' // moss-500 for high confidence
  if (props.score < 50)
    color = '#dc2626' // red-600 for low
  else if (props.score < 75) color = '#d49a20' // harvest-500 for medium

  return {
    labels: ['Confidence', 'Remaining'],
    datasets: [
      {
        backgroundColor: [color, '#e8e4de'], // stone-200 for remaining track
        data: [props.score, 100 - props.score],
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
