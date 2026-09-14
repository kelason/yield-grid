<template>
  <div class="recommendation-card bg-white p-6 rounded-2xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow flex flex-col sm:flex-row gap-6">
    <div class="flex-shrink-0 flex justify-center sm:justify-start">
      <CropConfidenceMeter :score="recommendation.confidence_score" />
    </div>
    <div class="flex-grow flex flex-col justify-between">
      <div>
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-2xl font-bold text-gray-900">{{ recommendation.crop_name }}</h3>
          <span 
            v-if="recommendation.status !== 'pending'"
            :class="[
              'px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide',
              recommendation.status === 'accepted' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
            ]"
          >
            {{ recommendation.status }}
          </span>
        </div>
        <p class="text-gray-600 mb-4 leading-relaxed">{{ recommendation.reasoning }}</p>
        <div class="inline-flex items-center gap-2 bg-blue-50 text-blue-700 px-3 py-1.5 rounded-lg text-sm font-medium mb-4 sm:mb-0">
          <span>🌾 Projected Yield:</span>
          <strong>{{ recommendation.projected_yield }}</strong>
        </div>
      </div>
      
      <div v-if="recommendation.status === 'pending'" class="flex items-center gap-3 mt-4 sm:justify-end">
        <button 
          @click="$emit('reject', recommendation.id)"
          class="px-4 py-2 text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
        >
          Reject
        </button>
        <button 
          @click="$emit('accept', recommendation.id)"
          class="px-6 py-2 text-sm font-bold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm transition-colors"
        >
          Accept & Contract
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import CropConfidenceMeter from '../atoms/CropConfidenceMeter.vue';

defineProps({
  recommendation: {
    type: Object,
    required: true
  }
});

defineEmits(['accept', 'reject']);
</script>
