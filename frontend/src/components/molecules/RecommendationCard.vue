<template>
  <AppCard variant="default" padding="p-6" class="recommendation-card">
    <div class="flex flex-col sm:flex-row gap-6">
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
            <div class="flex flex-shrink-0 items-center gap-2">
              <VerifiedBadge v-if="showVerifiedBadge" size="sm" />
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
          </div>

          <!-- Taxonomy chips -->
          <div
            v-if="recommendation.produce_type || recommendation.subtype"
            class="flex flex-wrap gap-2 mb-3"
          >
            <span
              v-if="recommendation.produce_type"
              data-test="taxonomy-chip"
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-dew-50 text-dew-700"
            >
              {{ recommendation.produce_type }}
            </span>
            <span
              v-if="recommendation.subtype"
              data-test="taxonomy-chip"
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-stone-100 text-stone-800"
            >
              {{ recommendation.subtype }}
            </span>
          </div>

          <!-- Reasoning -->
          <p class="text-stone-600 mb-4 leading-relaxed text-base">
            {{ recommendation.reasoning }}
          </p>

          <!-- Projected Yield badge -->
          <div
            class="inline-flex items-center gap-2 bg-moss-50 text-moss-700 border border-moss-200 px-3 py-1.5 rounded-xl text-sm font-semibold mb-4 sm:mb-0"
          >
            <ChartBarIcon class="h-5 w-5" aria-hidden="true" />
            <span
              >Projected Yield: <strong>{{ recommendation.projected_yield }}</strong></span
            >
          </div>
        </div>

        <!-- Actions -->
        <div
          v-if="recommendation.status === 'pending'"
          class="flex flex-wrap items-center gap-3 mt-4 sm:justify-end"
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
            Accept &amp; Contract
          </AppButton>
        </div>
      </div>
    </div>
  </AppCard>
</template>

<script setup>
import { ChartBarIcon } from '@heroicons/vue/24/outline'
import { computed } from 'vue'
import AppCard from '../atoms/AppCard.vue'
import CropConfidenceMeter from '../atoms/CropConfidenceMeter.vue'
import AppButton from '../atoms/AppButton.vue'
import VerifiedBadge from '../atoms/VerifiedBadge.vue'
import { isRecommendationVerified } from '@/utils/verification'

const props = defineProps({
  recommendation: {
    type: Object,
    required: true,
  },
})

const showVerifiedBadge = computed(() => isRecommendationVerified(props.recommendation))

defineEmits(['accept', 'reject'])
</script>
