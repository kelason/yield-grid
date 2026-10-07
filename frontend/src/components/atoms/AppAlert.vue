<script setup>
import {
  CheckCircleIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import AppButton from './AppButton.vue'
const ALERT_ICONS = {
  success: CheckCircleIcon,
  error: ExclamationTriangleIcon,
  warning: ExclamationTriangleIcon,
  info: InformationCircleIcon,
}
defineProps({
  type: {
    type: String,
    default: 'info',
    validator: (v) => ['success', 'error', 'warning', 'info'].includes(v),
  },
  dismissible: { type: Boolean, default: false },
})

const emit = defineEmits(['dismiss'])
</script>

<template>
  <div
    :class="[
      'rounded-2xl p-4 border-l-[3px] flex items-start gap-3 shadow-soft motion-reduce:transition-none',
      type === 'success' && 'bg-moss-50 text-moss-800 border-moss-500',
      type === 'error' && 'bg-red-50 text-red-800 border-red-500',
      type === 'warning' && 'bg-harvest-50 text-harvest-800 border-harvest-500',
      type === 'info' && 'bg-dew-50 text-dew-800 border-dew-400',
    ]"
    role="alert"
  >
    <component :is="ALERT_ICONS[type]" class="h-5 w-5 shrink-0 mt-0.5" aria-hidden="true" />

    <div class="flex-1 text-sm font-medium leading-relaxed">
      <slot />
    </div>

    <AppButton
      v-if="dismissible"
      variant="ghost"
      aria-label="Dismiss alert"
      @click="emit('dismiss')"
      ><XMarkIcon class="h-5 w-5" aria-hidden="true"
    /></AppButton>
  </div>
</template>
