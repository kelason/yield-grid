<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

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

const { t } = useI18n()

const STATUS_CONFIG = {
  available: {
    classes: 'bg-moss-100 text-moss-800 border border-moss-200',
    labelKey: 'market.status.available',
  },
  reserved: {
    classes: 'bg-harvest-100 text-harvest-800 border border-harvest-200',
    labelKey: 'market.status.reserved',
  },
  partially_paid: {
    classes: 'bg-harvest-50 text-harvest-700 border border-harvest-200',
    labelKey: 'market.status.partially_paid',
  },
  sold: {
    classes: 'bg-soil-100 text-soil-800 border border-soil-200',
    labelKey: 'market.status.sold',
  },
  expired: {
    classes: 'bg-stone-100 text-stone-600 border border-stone-200',
    labelKey: 'market.status.expired',
  },
  cancelled: {
    classes: 'bg-red-100 text-red-800 border border-red-200',
    labelKey: 'market.status.cancelled',
  },
  pending: {
    classes: 'bg-harvest-50 text-harvest-700 border border-harvest-200',
    labelKey: 'market.status.pending',
  },
  pending_approval: {
    classes: 'bg-harvest-100 text-harvest-800 border border-harvest-200',
    labelKey: 'market.status.pending_approval',
  },
  completed: {
    classes: 'bg-moss-100 text-moss-800 border border-moss-200',
    labelKey: 'market.status.completed',
  },
  failed: {
    classes: 'bg-red-100 text-red-800 border border-red-200',
    labelKey: 'market.status.failed',
  },
  fully_paid: {
    classes: 'bg-moss-100 text-moss-800 border border-moss-200',
    labelKey: 'market.status.fully_paid',
  },
  open: {
    classes: 'bg-moss-100 text-moss-800 border border-moss-200',
    labelKey: 'market.status.open',
  },
  fully_allocated: {
    classes: 'bg-harvest-100 text-harvest-800 border border-harvest-200',
    labelKey: 'market.status.fully_allocated',
  },
  fulfilled: {
    classes: 'bg-moss-100 text-moss-800 border border-moss-200',
    labelKey: 'market.status.fulfilled',
  },
  accepted: {
    classes: 'bg-dew-100 text-dew-800 border border-dew-200',
    labelKey: 'market.status.accepted',
  },
  rejected: {
    classes: 'bg-stone-100 text-stone-600 border border-stone-200',
    labelKey: 'market.status.rejected',
  },
  withdrawn: {
    classes: 'bg-stone-100 text-stone-600 border border-stone-200',
    labelKey: 'market.status.withdrawn',
  },
  paid: {
    classes: 'bg-harvest-100 text-harvest-800 border border-harvest-200',
    labelKey: 'market.status.paid',
  },
  delivered: {
    classes: 'bg-dew-100 text-dew-800 border border-dew-200',
    labelKey: 'market.status.delivered',
  },
}

const statusConfig = computed(() => {
  const entry = STATUS_CONFIG[(props.status || '').toLowerCase()]
  if (entry) {
    return { classes: entry.classes, label: t(entry.labelKey) }
  }
  return {
    classes: 'bg-stone-100 text-stone-700 border border-stone-200',
    label: props.status,
  }
})
</script>

<template>
  <span
    class="inline-flex items-center justify-center rounded-full font-medium"
    :class="[sizeClasses, statusConfig.classes]"
  >
    {{ statusConfig.label }}
  </span>
</template>
