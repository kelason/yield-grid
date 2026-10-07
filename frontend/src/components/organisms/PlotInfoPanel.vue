<template>
  <div
    class="plot-info-panel bg-white p-6 rounded-2xl shadow-soft border border-stone-200 w-full max-w-md"
  >
    <h2 class="font-serif text-xl font-bold mb-4 text-stone-800">Plot Details</h2>
    <div v-if="plot" class="space-y-4">
      <div>
        <span class="text-sm text-stone-500 block">Name</span>
        <strong class="text-lg">{{ plot.name }}</strong>
      </div>
      <div>
        <span class="text-sm text-stone-500 block">Area</span>
        <strong>{{ plot.calculated_area }} hectares</strong>
      </div>

      <div class="mt-6">
        <button
          @click="$emit('analyze', plot)"
          class="w-full py-3 bg-moss-600 hover:bg-moss-700 text-white font-bold rounded-xl transition-all duration-300 hover:scale-[1.02] shadow-soft flex items-center justify-center gap-2"
        >
          Analyze This Plot
        </button>
      </div>
    </div>
    <div v-else class="text-stone-500 italic">Select a plot on the map to view details.</div>
  </div>
</template>

<script setup>
defineProps({
  plot: {
    type: Object,
    default: null,
  },
})

defineEmits(['analyze'])
</script>
