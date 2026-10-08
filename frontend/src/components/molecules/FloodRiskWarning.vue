<script setup>
import { computed } from 'vue'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import AppCard from '../atoms/AppCard.vue'
import AppButton from '../atoms/AppButton.vue'

const props = defineProps({
  assessment: { type: Object, default: null },
})
const emit = defineEmits(['dismiss'])

const TINTS = {
  high: 'border-red-300 bg-red-50',
  medium: 'border-harvest-300 bg-harvest-50',
  low: 'border-moss-300 bg-moss-50',
  safe: 'border-stone-200 bg-white',
  unknown: 'border-stone-200 bg-white',
}

const ICONS = {
  high: 'text-red-600',
  medium: 'text-harvest-600',
  low: 'text-moss-600',
  safe: 'text-stone-400',
  unknown: 'text-stone-400',
}

const tint = computed(() => TINTS[props.assessment?.level] || TINTS.unknown)
const iconColor = computed(() => ICONS[props.assessment?.level] || ICONS.unknown)
const title = computed(() => `${props.assessment?.label || 'Unknown'} flood risk`)
</script>

<template>
  <AppCard v-if="assessment" data-testid="flood-warning" padding="p-4" :class="tint">
    <div class="flex items-start gap-3">
      <ExclamationTriangleIcon class="h-6 w-6 shrink-0" :class="iconColor" aria-hidden="true" />
      <div class="min-w-0 flex-1">
        <h3 class="font-serif text-lg font-bold text-stone-900">{{ title }}</h3>
        <ul class="mt-2 space-y-1 text-sm leading-relaxed text-stone-600">
          <li v-for="(line, index) in assessment.advice || []" :key="index">{{ line }}</li>
        </ul>
        <AppButton
          class="mt-3"
          size="sm"
          data-testid="flood-warning-dismiss"
          @click="emit('dismiss')"
        >
          Proceed anyway
        </AppButton>
      </div>
    </div>
  </AppCard>
</template>
