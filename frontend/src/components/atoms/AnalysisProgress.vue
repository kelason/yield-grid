<template>
  <div class="flex flex-col items-center justify-center p-8 bg-green-50 rounded-xl border border-green-200">
    <div class="animate-pulse flex flex-col items-center">
      <div class="text-4xl mb-4 animate-bounce">🌱</div>
      <h3 class="text-xl font-semibold text-green-800">
        Analyzing crops for {{ location || 'your region' }}...
      </h3>
      <p class="text-green-600 mt-2 text-sm text-center max-w-sm">
        Our AI agronomist is cross-referencing soil conditions and weather patterns against crops commercially cultivated and available in {{ location || 'this location' }}.
      </p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  location: {
    type: String,
    default: ''
  }
});
</script>
