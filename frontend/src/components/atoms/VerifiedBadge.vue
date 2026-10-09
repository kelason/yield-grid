<script setup>
import { computed } from 'vue'
import { CheckBadgeIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  label: {
    type: String,
    default: 'Verified',
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md'].includes(value),
  },
})

const sizeClasses = computed(() => {
  return props.size === 'sm' ? 'px-2 py-0.5 text-xs' : 'px-2.5 py-1 text-sm'
})
</script>

<template>
  <span
    data-testid="verified-badge"
    role="img"
    :aria-label="label"
    class="inline-flex items-center gap-1 rounded-full border border-moss-200 bg-moss-100 font-medium text-moss-800"
    :class="sizeClasses"
  >
    <CheckBadgeIcon class="h-4 w-4" aria-hidden="true" />
    {{ label }}
  </span>
</template>
