<template>
  <div
    class="recommendation-card bg-white rounded-2xl shadow-soft border border-stone-200 hover:shadow-organic hover:border-moss-200 transition-all duration-300 hover:-translate-y-0.5 flex flex-col sm:flex-row gap-6 p-6"
  >
    <!-- Confidence Meter -->
    <div class="flex-shrink-0 flex justify-center sm:justify-start">
      <CropConfidenceMeter :score="recommendation.confidence_score" />
    </div>

    <!-- Content -->
    <div class="flex-grow flex flex-col justify-between">
      <div>
        <!-- Title row -->
        <div class="flex items-start justify-between mb-2 gap-3">
          <h3 class="text-2xl font-bold text-stone-900 leading-tight font-serif">
            {{ recommendation.crop_name }}
          </h3>
          <span
            v-if="recommendation.status !== 'pending'"
            :class="[
              'inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide flex-shrink-0',
              recommendation.status === 'accepted'
                ? 'bg-moss-100 text-moss-800'
                : 'bg-red-100 text-red-800',
            ]"
          >
            {{ recommendation.status }}
          </span>
        </div>

        <!-- Reasoning -->
        <p class="text-stone-600 mb-4 leading-relaxed text-sm text-justify">
          {{ recommendation.reasoning }}
        </p>

        <!-- Projected Yield badge -->
        <div
          class="inline-flex items-center gap-2 bg-moss-50 text-moss-700 border border-moss-200 px-3 py-1.5 rounded-xl text-sm font-semibold mb-4 sm:mb-0"
        >
          <span>🌾</span>
          <span
            >Projected Yield: <strong>{{ recommendation.projected_yield }}</strong></span
          >
        </div>
      </div>

      <!-- Actions -->
      <div
        v-if="recommendation.status === 'pending'"
        class="flex items-center gap-3 mt-4 sm:justify-end"
      >
        <AppButton variant="ghost" size="sm" @click="$emit('reject', recommendation.id)">
          Reject
        </AppButton>
        <AppButton
          variant="primary"
          size="sm"
          rounded="full"
          @click="$emit('accept', recommendation.id)"
        >
          ✓ Accept &amp; Contract
        </AppButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import CropConfidenceMeter from '../atoms/CropConfidenceMeter.vue'
import AppButton from '../atoms/AppButton.vue'

defineProps({
  recommendation: {
    type: Object,
    required: true,
  },
})

defineEmits(['accept', 'reject'])
</script>
