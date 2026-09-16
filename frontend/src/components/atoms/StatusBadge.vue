<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: {
    type: String,
    required: true,
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

const statusConfig = computed(() => {
  const map = {
    available: { classes: 'bg-farm-100 text-farm-800 border border-farm-200', label: 'Available' },
    reserved: {
      classes: 'bg-yellow-100 text-yellow-800 border border-yellow-200',
      label: 'Reserved',
    },
    sold: { classes: 'bg-green-100 text-green-800 border border-green-200', label: 'Sold' },
    expired: { classes: 'bg-gray-100 text-gray-800 border border-gray-200', label: 'Expired' },
    cancelled: { classes: 'bg-red-100 text-red-800 border border-red-200', label: 'Cancelled' },
    pending: {
      classes: 'bg-blue-50 text-blue-700 border border-blue-200',
      label: 'Pending Payment',
    },
    completed: {
      classes: 'bg-green-100 text-green-800 border border-green-200',
      label: 'Completed',
    },
    failed: { classes: 'bg-red-100 text-red-800 border border-red-200', label: 'Failed' },
  }

  return (
    map[props.status.toLowerCase()] || {
      classes: 'bg-gray-100 text-gray-800 border border-gray-200',
      label: props.status,
    }
  )
})
</script>

<template>
  <span
    class="inline-flex items-center justify-center rounded-full font-medium shadow-sm"
    :class="[sizeClasses, statusConfig.classes]"
  >
    {{ statusConfig.label }}
  </span>
</template>
