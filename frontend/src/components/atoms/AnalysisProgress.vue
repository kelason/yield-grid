<script setup>
import AppSpinner from './AppSpinner.vue'
defineProps({ location: { type: String, default: '' } })
</script>
<template>
  <div
    role="status"
    aria-live="polite"
    class="flex flex-col items-center justify-center gap-4 p-6 bg-dew-50 rounded-2xl border border-dew-200 text-center"
  >
    <AppSpinner size="md" class="text-dew-700" />
    <h2 class="font-serif text-2xl font-bold text-dew-800">
      Analyzing crops for {{ location || 'your region' }}...
    </h2>
    <p class="text-dew-700 text-sm max-w-lg leading-relaxed">
      Our AI agronomist is cross-referencing soil conditions and weather patterns against crops
      commercially cultivated and available in {{ location || 'this location' }}.
    </p>
  </div>
</template>
