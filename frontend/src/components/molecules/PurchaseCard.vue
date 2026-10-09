<script setup>
import AppCard from '@/components/atoms/AppCard.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import { ShoppingBagIcon } from '@heroicons/vue/24/outline'
import { ChatBubbleLeftRightIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  purchase: {
    type: Object,
    required: true,
  },
})

defineEmits(['cancel', 'message'])

import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { PAYMENT_STATUS } from '@/constants/payment'

const { t } = useI18n()

const statusStyles = {
  completed: 'bg-moss-50 text-moss-700 border border-moss-200/60',
  partially_paid: 'bg-harvest-50 text-harvest-700 border border-harvest-200/60',
  pending: 'bg-harvest-50 text-harvest-700 border border-harvest-200/60',
  failed: 'bg-red-50 text-red-700 border border-red-200/60',
}

const displayStatus = computed(() => {
  const p = props.purchase
  if (p.payment_status === PAYMENT_STATUS.COMPLETED) {
    const total = parseFloat(p.total_contract_amount)
    const paid = parseFloat(p.amount_paid)
    if (p.is_downpayment && (Number.isNaN(total) || paid < total)) {
      return 'partially_paid'
    }
    return 'paid'
  }
  if (p.cash_payment_status === 'partially_paid') {
    return 'partially_paid'
  }
  if (p.cash_payment_status === 'pending_approval') {
    return 'pending_approval'
  }
  return p.payment_status
})

const displayLabel = computed(() => t(`buyer.purchases.status_${displayStatus.value}`))

const displayStyle = computed(() => {
  if (displayStatus.value === 'partially_paid') return statusStyles.partially_paid
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
  <AppCard class="group" padding="p-5">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-5">
      <!-- Left: crop info -->
      <div class="flex items-center gap-4 min-w-0">
        <div
          class="w-12 h-12 rounded-2xl bg-gradient-to-br from-moss-50 to-moss-100/50 flex items-center justify-center text-2xl flex-shrink-0 border border-moss-100"
        >
          <ShoppingBagIcon class="h-5 w-5 text-moss-700" aria-hidden="true" />
        </div>
        <div class="min-w-0">
          <h3 class="font-serif font-bold text-stone-900 text-[17px] leading-tight break-words">
            {{
              purchase.contract?.crop_name ||
              purchase.demand_offer?.crop_name ||
              t('buyer.purchases.forward_contract')
            }}
          </h3>
          <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-[13px] text-stone-500 mt-1">
            <span class="font-medium text-stone-700"
              >{{
                purchase.contract?.quantity_kg ?? purchase.demand_offer?.quantity_kg ?? '—'
              }}
              kg</span
            >
            <span v-if="purchase.demand_offer" class="text-moss-700 font-medium">{{
              t('buyer.purchases.demand_offer')
            }}</span>
            <span v-else>{{
              t('buyer.purchases.harvest_date', {
                date: formatDate(purchase.contract?.estimated_harvest_date),
              })
            }}</span>
            <span class="text-stone-300 hidden sm:inline">•</span>
            <span>{{
              t('buyer.purchases.purchased', { date: formatDate(purchase.created_at) })
            }}</span>
          </div>
        </div>
      </div>

      <!-- Right: status + amount + actions -->
      <div
        class="flex flex-wrap items-center justify-between sm:justify-end gap-4 sm:gap-6 w-full sm:w-auto mt-2 sm:mt-0 pt-3 sm:pt-0 border-t sm:border-0 border-stone-100"
      >
        <div class="flex flex-wrap items-center gap-4">
          <span
            :class="displayStyle"
            class="text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider"
          >
            {{ displayLabel }}
          </span>

          <div class="text-right min-w-[100px]">
            <p
              class="text-lg font-semibold text-stone-900 tracking-tight leading-relaxed break-all tabular-nums"
            >
              {{ formatCurrency(purchase.amount_paid) }}
            </p>
            <p class="text-xs font-medium text-stone-600 uppercase tracking-wider mt-1.5">
              {{ purchase.payment_method || '—' }}
            </p>
          </div>
        </div>

        <!-- Actions (icon buttons in a horizontal row) -->
        <div class="flex flex-row items-center justify-end gap-2 flex-shrink-0">
          <AppButton
            variant="outline"
            size="sm"
            v-if="purchase.contract?.farmer?.id || purchase.demand_offer?.farmer?.id"
            @click="$emit('message', purchase)"
            :title="t('buyer.purchases.message_farmer')"
            :aria-label="t('buyer.purchases.message_farmer')"
            class="p-2"
          >
            <ChatBubbleLeftRightIcon class="h-5 w-5" aria-hidden="true" />
          </AppButton>
          <AppButton
            variant="danger"
            size="sm"
            v-if="
              purchase.payment_status === PAYMENT_STATUS.PENDING &&
              purchase.cash_payment_status !== 'partially_paid'
            "
            @click="$emit('cancel', purchase)"
            :title="t('buyer.purchases.cancel_purchase')"
            :aria-label="t('buyer.purchases.cancel_purchase')"
            class="p-2"
          >
            <XMarkIcon class="h-5 w-5" aria-hidden="true" />
          </AppButton>
        </div>
      </div>
    </div>
  </AppCard>
</template>
