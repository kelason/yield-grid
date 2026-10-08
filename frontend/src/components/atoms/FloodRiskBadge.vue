<script setup>
import { computed } from 'vue'

const props = defineProps({
  level: { type: String, default: 'unknown' },
})

const LABELS = {
  safe: 'Safe',
  low: 'Low',
  medium: 'Medium',
  high: 'High',
  unknown: 'Unknown',
}

const STYLES = {
  safe: 'bg-moss-100 text-moss-800',
  low: 'bg-harvest-100 text-harvest-800',
  medium: 'bg-harvest-200 text-harvest-900',
  high: 'bg-red-100 text-red-800',
  unknown: 'bg-stone-200 text-stone-600',
}

const label = computed(() => LABELS[props.level] || LABELS.unknown)
const style = computed(() => STYLES[props.level] || STYLES.unknown)
</script>

<template>
  <span
    data-testid="flood-badge"
    :aria-label="`${label} flood risk`"
    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
    :class="style"
    >{{ label }}</span
  >
</template>
