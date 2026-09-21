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
    available: {
      classes: 'bg-moss-100 text-moss-800 border border-moss-200',
      label: 'Available',
    },
    reserved: {
      classes: 'bg-harvest-100 text-harvest-800 border border-harvest-200',
      label: 'Reserved',
    },
    sold: {
      classes: 'bg-soil-100 text-soil-800 border border-soil-200',
      label: 'Sold',
    },
    expired: {
      classes: 'bg-stone-100 text-stone-600 border border-stone-200',
      label: 'Expired',
    },
    cancelled: {
      classes: 'bg-red-100 text-red-800 border border-red-200',
      label: 'Cancelled',
    },
    pending: {
      classes: 'bg-harvest-50 text-harvest-700 border border-harvest-200',
      label: 'Pending Payment',
    },
    completed: {
      classes: 'bg-moss-100 text-moss-800 border border-moss-200',
      label: 'Completed',
    },
    failed: { classes: 'bg-red-100 text-red-800 border border-red-200', label: 'Failed' },
  }

  return (
    map[props.status.toLowerCase()] || {
      classes: 'bg-stone-100 text-stone-700 border border-stone-200',
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
