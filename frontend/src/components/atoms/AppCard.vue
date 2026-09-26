<script setup>
defineProps({
  padding: {
    type: String,
    default: 'p-6',
  },
  variant: {
    type: String,
    default: 'default',
    validator: (v) => ['default', 'gradient', 'muted', 'glass', 'harvest', 'dew'].includes(v),
  },
  hover: {
    type: Boolean,
    default: false,
  },
})
</script>

<template>
  <div
    :class="[
      'overflow-hidden rounded-2xl border transition-all duration-300 motion-reduce:transition-none motion-reduce:transform-none',
      variant === 'default' &&
        'bg-white shadow-soft border-stone-300 ' +
          (hover
            ? 'hover:shadow-organic hover:border-stone-400 hover:-translate-y-0.5 cursor-pointer'
            : ''),
      variant === 'gradient' &&
        'bg-gradient-to-br from-moss-500 to-moss-700 shadow-organic border-transparent text-white',
      variant === 'muted' &&
        'bg-stone-100 shadow-sm border-stone-300 ' +
          (hover
            ? 'hover:shadow-soft hover:border-stone-400 hover:-translate-y-0.5 cursor-pointer'
            : ''),
      variant === 'glass' &&
        'bg-white/85 backdrop-blur-sm shadow-organic border-white/30 ' +
          (hover ? 'hover:bg-white/95 hover:shadow-xl hover:-translate-y-0.5 cursor-pointer' : ''),
      variant === 'harvest' &&
        'bg-gradient-to-br from-harvest-50 to-harvest-100 shadow-harvest-glow border-harvest-200 ' +
          (hover ? 'hover:shadow-organic hover:-translate-y-0.5 cursor-pointer' : ''),
      variant === 'dew' &&
        'bg-gradient-to-br from-dew-50 to-dew-100 shadow-sm border-dew-200 ' +
          (hover ? 'hover:shadow-soft hover:-translate-y-0.5 cursor-pointer' : ''),
    ]"
  >
    <div :class="padding">
      <slot></slot>
    </div>
  </div>
</template>
