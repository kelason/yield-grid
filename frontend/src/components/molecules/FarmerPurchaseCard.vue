<script setup>
import AppCard from '../atoms/AppCard.vue'
import AppButton from '../atoms/AppButton.vue'
import PriceTag from '../atoms/PriceTag.vue'
import StatusBadge from '../atoms/StatusBadge.vue'
import { BanknotesIcon } from '@heroicons/vue/24/outline'
import { PAYMENT_OPTION, CASH_PAYMENT_TYPE } from '@/constants/payment'
defineProps({
  purchase: { type: Object, required: true },
  loading: { type: Boolean, default: false },
})
defineEmits(['message', 'approve'])
function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Intl.DateTimeFormat('en-PH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(new Date(dateStr))
}

function getConfirmedPaid(purchase) {
  if (purchase.payment_method !== PAYMENT_OPTION.CASH && purchase.payment_status === 'completed') {
    return purchase.amount_paid || 0
  }
  return purchase.cash_amount_confirmed || 0
}
</script>
<template>
  <AppCard padding="p-5 sm:p-6">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
      <!-- Left: Crop & Buyer Info -->
      <div class="flex items-start gap-4 min-w-0">
        <div
          class="w-12 h-12 rounded-2xl bg-gradient-to-br from-moss-50 to-moss-100/50 flex items-center justify-center text-2xl flex-shrink-0 border border-moss-100 mt-1 lg:mt-0"
        >
          <BanknotesIcon class="h-6 w-6 text-moss-700" aria-hidden="true" />
        </div>
        <div class="min-w-0">
          <h3 class="font-bold text-stone-900 text-[17px] leading-tight truncate font-serif">
            {{ purchase.contract?.title || purchase.demand_offer?.demand_title || 'Unknown Item' }}
          </h3>
          <p v-if="purchase.demand_offer" class="text-xs font-medium text-moss-700 mt-0.5">
            Demand offer · {{ purchase.demand_offer.quantity_kg }} kg of
            {{ purchase.demand_offer.crop_name }}
          </p>

          <div
            class="flex flex-wrap items-center gap-x-2 gap-y-1.5 text-[13px] text-stone-500 mt-1.5"
          >
            <span class="font-medium text-stone-700">{{
              purchase.buyer?.name || 'Unknown Buyer'
            }}</span>
            <span class="text-stone-300 hidden sm:inline">•</span>
            <span
              class="uppercase tracking-wider text-[11px] font-semibold text-stone-500 bg-stone-100 px-2 py-0.5 rounded-xl"
              >{{ purchase.payment_method || '—' }}</span
            >
            <span class="text-stone-300 hidden sm:inline">•</span>
            <span>{{ formatDate(purchase.created_at) }}</span>
          </div>

          <div class="flex flex-wrap items-center gap-2 mt-3">
            <StatusBadge :status="purchase.payment_status" size="sm" />
            <StatusBadge
              v-if="purchase.cash_payment_status"
              :status="purchase.cash_payment_status"
              size="sm"
            />
          </div>
        </div>
      </div>

      <!-- Right: Amounts & Actions -->
      <div
        class="flex flex-col sm:flex-row items-start sm:items-center justify-between lg:justify-end gap-5 w-full lg:w-auto mt-2 lg:mt-0 pt-4 lg:pt-0 border-t lg:border-0 border-stone-100"
      >
        <!-- Amounts -->
        <div
          class="flex flex-row sm:flex-col items-center sm:items-end justify-between w-full sm:w-auto gap-4 sm:gap-1.5 bg-stone-50 sm:bg-transparent p-3 sm:p-0 rounded-xl sm:rounded-xl border border-stone-100 sm:border-0"
        >
          <div class="text-left sm:text-right">
            <p
              class="text-[11px] font-medium text-stone-500 uppercase tracking-wider mb-0.5 sm:mb-0"
            >
              Total Due
            </p>
            <PriceTag
              :amount="purchase.total_contract_amount"
              :currency="purchase.currency"
              class="text-[15px] font-extrabold text-stone-900 leading-none"
            />
          </div>

          <div class="hidden sm:block w-8 border-t border-stone-200 my-0.5"></div>

          <div class="text-right">
            <p
              class="text-[11px] font-medium text-stone-500 uppercase tracking-wider mb-0.5 sm:mb-0"
            >
              Confirmed Paid
            </p>
            <PriceTag
              :amount="getConfirmedPaid(purchase)"
              :currency="purchase.currency"
              class="text-[15px] font-extrabold text-moss-600 leading-none"
            />
          </div>
        </div>

        <!-- Message buyer (transaction partner) -->
        <div v-if="purchase.buyer?.id" class="flex w-full sm:w-auto mt-2 sm:mt-0 flex-shrink-0">
          <AppButton @click="$emit('message', purchase)"> Message buyer </AppButton>
        </div>

        <!-- Actions -->
        <div
          class="flex flex-wrap sm:flex-col gap-2.5 w-full sm:w-auto mt-2 sm:mt-0 flex-shrink-0"
          v-if="
            purchase.payment_status === 'pending' ||
            purchase.cash_payment_status === 'pending' ||
            purchase.cash_payment_status === 'partially_paid'
          "
        >
          <AppButton
            @click="$emit('approve', purchase, CASH_PAYMENT_TYPE.PARTIAL)"
            :disabled="loading"
            v-if="purchase.cash_payment_status !== 'partially_paid'"
          >
            Approve 10%
          </AppButton>
          <AppButton
            @click="$emit('approve', purchase, CASH_PAYMENT_TYPE.FULL)"
            :disabled="loading"
          >
            Approve Full
          </AppButton>
        </div>

        <!-- Visual spacer when no actions -->
        <div v-else class="hidden lg:block w-[110px]"></div>
      </div>
    </div>
  </AppCard>
</template>
