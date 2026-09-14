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
  let color = '#10B981' // green for high
  if (props.score < 50)
    color = '#EF4444' // red for low
  else if (props.score < 75) color = '#F59E0B' // yellow for medium

  return {
    labels: ['Confidence', 'Remaining'],
    datasets: [
      {
        backgroundColor: [color, '#E5E7EB'], // Tailwind gray-200 for remaining
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
