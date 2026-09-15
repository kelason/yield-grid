<script setup>
defineProps({
  padding: {
    type: String,
    default: 'p-6',
  },
  variant: {
    type: String,
    default: 'default',
    validator: (v) => ['default', 'gradient', 'muted', 'glass'].includes(v),
  },
})
</script>

<template>
  <div
    :class="[
      'overflow-hidden rounded-xl border transition-all duration-200',
      variant === 'default' && 'bg-white shadow-sm border-gray-100 hover:shadow-md hover:border-gray-200',
      variant === 'gradient' && 'bg-gradient-to-br from-farm-500 to-farm-600 shadow-md border-transparent text-white',
      variant === 'muted'   && 'bg-earth-50 shadow-sm border-earth-100 hover:shadow-md',
      variant === 'glass'   && 'bg-white/80 backdrop-blur-sm shadow-lg border-white/30 hover:shadow-xl',
    ]"
  >
    <div :class="padding">
      <slot></slot>
    </div>
  </div>
</template>
