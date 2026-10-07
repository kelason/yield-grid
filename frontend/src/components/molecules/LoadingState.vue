<script setup>
import AppSpinner from '../atoms/AppSpinner.vue'
defineProps({ label: { type: String, default: 'Loading…' } })
</script>
<template>
  <div role="status" aria-live="polite" class="text-stone-600">
    <span :class="$slots.default ? 'sr-only' : 'text-sm'">{{ label }}</span>
    <slot><AppSpinner class="mt-2" size="md" /></slot>
  </div>
</template>
