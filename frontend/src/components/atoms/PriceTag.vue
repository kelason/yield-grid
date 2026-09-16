<script setup>
import { computed } from 'vue'

const props = defineProps({
  amount: {
    type: Number,
    required: true,
  },
  currency: {
    type: String,
    default: 'PHP',
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
})

const sizeClasses = computed(() => {
  const map = {
    sm: 'text-sm',
    md: 'text-lg',
    lg: 'text-3xl',
  }
  return map[props.size]
})

const formattedAmount = computed(() => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: props.currency,
  }).format(props.amount)
})
</script>

<template>
  <span class="font-semibold text-gray-900 tracking-tight" :class="sizeClasses">
    {{ formattedAmount }}
  </span>
</template>
