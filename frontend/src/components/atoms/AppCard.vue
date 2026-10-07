<script setup>
defineProps({
  padding: { type: String, default: 'p-6' },
  variant: {
    type: String,
    default: 'default',
    validator: (value) =>
      ['default', 'gradient', 'muted', 'glass', 'harvest', 'dew'].includes(value),
  },
  hover: Boolean,
})
const variants = {
  default: 'border-stone-200 bg-white',
  gradient: 'border-moss-700 bg-moss-700 text-white',
  muted: 'border-stone-200 bg-stone-100',
  glass: 'border-stone-200 bg-white',
  harvest: 'border-harvest-200 bg-harvest-50',
  dew: 'border-dew-200 bg-dew-50',
}
</script>
<template>
  <div
    :class="[
      'overflow-hidden rounded-2xl border shadow-soft transition-all duration-300 motion-reduce:transition-none',
      variants[variant],
      hover ? 'hover:border-moss-300 hover:shadow-organic' : '',
    ]"
  >
    <div :class="padding"><slot /></div>
  </div>
</template>
