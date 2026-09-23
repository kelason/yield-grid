<script setup>
import AppCard from '@/components/atoms/AppCard.vue'

const props = defineProps({
  purchase: {
    type: Object,
    required: true,
  },
})

defineEmits(['cancel'])

import { computed } from 'vue'

const statusStyles = {
  completed: 'bg-moss-50 text-moss-700 border border-moss-200/60',
  partially_paid: 'bg-harvest-50 text-harvest-700 border border-harvest-200/60',
  pending: 'bg-yellow-50 text-yellow-700 border border-yellow-200/60',
  failed: 'bg-red-50 text-red-700 border border-red-200/60',
}

const displayStatus = computed(() => {
  const p = props.purchase
  if (p.payment_status === 'completed') {
    return p.is_downpayment ? 'partially paid' : 'paid'
  }
  if (p.cash_payment_status === 'partially_paid') {
    return 'partially paid'
  }
  if (p.cash_payment_status === 'pending_approval') {
    return 'pending approval'
  }
  return p.payment_status
})

const displayStyle = computed(() => {
  if (displayStatus.value === 'partially paid') return statusStyles.partially_paid
  if (displayStatus.value === 'paid') return statusStyles.completed
  return (
    statusStyles[props.purchase.payment_status] ||
    'bg-stone-50 text-stone-600 border border-stone-200'
  )
})

function formatCurrency(amount) {
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount)
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Intl.DateTimeFormat('en-PH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(new Date(dateStr))
}
</script>

<template>
  <AppCard
    class="group hover:border-farm-300 hover:shadow-lg transition-all duration-300 bg-white"
    body-class="p-5"
  >
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-5">
      <!-- Left: crop info -->
      <div class="flex items-center gap-4 min-w-0">
        <div
          class="w-12 h-12 rounded-2xl bg-gradient-to-br from-farm-50 to-farm-100/50 flex items-center justify-center text-2xl flex-shrink-0 border border-farm-100"
        >
          🌱
        </div>
        <div class="min-w-0">
          <h3 class="font-bold text-gray-900 text-[17px] leading-tight truncate">
            {{ purchase.contract?.crop_name || 'Forward Contract' }}
          </h3>
          <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-[13px] text-gray-500 mt-1">
            <span class="font-medium text-gray-700"
              >{{ purchase.contract?.quantity_kg ?? '—' }} kg</span
            >
            <span class="text-gray-300 hidden sm:inline">•</span>
            <span>Harvest {{ formatDate(purchase.contract?.estimated_harvest_date) }}</span>
            <span class="text-gray-300 hidden sm:inline">•</span>
            <span>Purchased {{ formatDate(purchase.created_at) }}</span>
          </div>
        </div>
      </div>

      <!-- Right: status + amount + actions -->
      <div
        class="flex items-center justify-between sm:justify-end gap-4 sm:gap-6 w-full sm:w-auto mt-2 sm:mt-0 pt-3 sm:pt-0 border-t sm:border-0 border-gray-100"
      >
        <div class="flex items-center gap-4">
          <span
            :class="displayStyle"
            class="text-[11px] font-bold px-3 py-1 rounded-full uppercase tracking-wider"
          >
            {{ displayStatus }}
          </span>

          <div class="text-right min-w-[100px]">
            <p class="text-lg font-extrabold text-gray-900 tracking-tight leading-none">
              {{ formatCurrency(purchase.amount_paid) }}
            </p>
            <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wider mt-1.5">
              {{ purchase.payment_method || '—' }}
            </p>
          </div>
        </div>

        <!-- Actions (fixed width reserved so price aligns consistently across all rows) -->
        <div class="w-[85px] flex justify-end flex-shrink-0">
          <button
            v-if="
              purchase.payment_status === 'pending' &&
              purchase.cash_payment_status !== 'partially_paid'
            "
            @click="$emit('cancel', purchase)"
            class="text-xs font-semibold text-red-600 hover:text-white border border-red-200 hover:bg-red-500 hover:border-red-500 px-3 py-1.5 rounded-xl transition-all duration-200 shadow-sm w-full text-center"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  </AppCard>
</template>
