<script setup>
import { computed } from 'vue'
import AppCard from '../atoms/AppCard.vue'
import StatusBadge from '../atoms/StatusBadge.vue'
import PriceTag from '../atoms/PriceTag.vue'
import AppButton from '../atoms/AppButton.vue'

const props = defineProps({
  offer: { type: Object, required: true },
  viewerRole: { type: String, required: true },
  loading: { type: Boolean, default: false },
})

defineEmits([
  'accept',
  'reject',
  'pay',
  'withdraw',
  'cancel',
  'mark-delivered',
  'confirm-completed',
  'settle-balance',
  'message',
])

const isBuyer = () => props.viewerRole === 'buyer'
const isFarmer = () => props.viewerRole === 'farmer'

const hasDownpaymentPurchase = computed(() => {
  const purchase = props.offer.purchase
  return !!purchase && !!purchase.is_downpayment && purchase.total_contract_amount != null
})

const balanceSettled = computed(() => {
  if (!hasDownpaymentPurchase.value) return true
  const purchase = props.offer.purchase
  return parseFloat(purchase.amount_paid) >= parseFloat(purchase.total_contract_amount)
})

const showSettleButton = computed(
  () =>
    isFarmer() &&
    ['delivered', 'completed'].includes(props.offer.status) &&
    props.offer.purchase?.payment_status === 'completed' &&
    hasDownpaymentPurchase.value &&
    !balanceSettled.value,
)

const showSettledPill = computed(
  () =>
    ['delivered', 'completed'].includes(props.offer.status) &&
    props.offer.purchase?.payment_status === 'completed' &&
    hasDownpaymentPurchase.value &&
    balanceSettled.value,
)
</script>

<template>
  <AppCard padding="p-5">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <p class="font-semibold text-stone-900">
          {{ offer.quantity_kg }} kg
          <span class="font-normal text-stone-500"
            >from {{ offer.farmer?.name || 'a farmer' }}</span
          >
        </p>
        <p v-if="offer.message" class="text-sm text-stone-600 mt-1 leading-relaxed">
          “{{ offer.message }}”
        </p>
      </div>
      <StatusBadge :status="offer.status" size="sm" />
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 mt-3">
      <p>
        <PriceTag :amount="offer.price_per_kg" size="md" />
        <span class="text-sm text-stone-500">/kg</span>
      </p>
      <p class="text-sm text-stone-500">
        Total
        <span class="font-semibold text-stone-900"
          >₱{{ Number(offer.total_price).toLocaleString('en-PH') }}</span
        >
      </p>
    </div>

    <div class="flex flex-wrap gap-2 mt-4">
      <template v-if="isBuyer() && offer.status === 'pending'">
        <AppButton size="sm" variant="primary" :loading="loading" @click="$emit('accept', offer)">
          Accept offer
        </AppButton>
        <AppButton size="sm" variant="ghost" :loading="loading" @click="$emit('reject', offer)">
          Reject
        </AppButton>
      </template>
      <template v-if="isBuyer() && offer.status === 'accepted'">
        <AppButton size="sm" variant="primary" :loading="loading" @click="$emit('pay', offer)">
          Pay now
        </AppButton>
        <AppButton size="sm" variant="ghost" :loading="loading" @click="$emit('cancel', offer)">
          Cancel
        </AppButton>
        <AppButton size="sm" variant="secondary" @click="$emit('message', offer)">
          Message farmer
        </AppButton>
      </template>
      <template
        v-if="
          isBuyer() && ['partially_paid', 'paid', 'delivered', 'completed'].includes(offer.status)
        "
      >
        <AppButton
          v-if="offer.status === 'delivered'"
          size="sm"
          variant="primary"
          :loading="loading"
          @click="$emit('confirm-completed', offer)"
        >
          Confirm receipt
        </AppButton>
        <AppButton size="sm" variant="secondary" @click="$emit('message', offer)">
          Message farmer
        </AppButton>
        <span
          v-if="showSettledPill"
          class="inline-flex items-center rounded-full bg-moss-100 text-moss-800 text-xs font-medium px-2.5 py-1"
        >
          Fully settled
        </span>
      </template>

      <template v-if="isFarmer() && offer.status === 'pending'">
        <AppButton size="sm" variant="ghost" :loading="loading" @click="$emit('withdraw', offer)">
          Withdraw
        </AppButton>
      </template>
      <template v-if="isFarmer() && offer.status === 'accepted'">
        <AppButton size="sm" variant="ghost" :loading="loading" @click="$emit('cancel', offer)">
          Cancel
        </AppButton>
        <AppButton size="sm" variant="secondary" @click="$emit('message', offer)">
          Message buyer
        </AppButton>
      </template>
      <template v-if="isFarmer() && ['partially_paid', 'paid'].includes(offer.status)">
        <AppButton
          size="sm"
          variant="primary"
          :loading="loading"
          @click="$emit('mark-delivered', offer)"
        >
          Mark delivered
        </AppButton>
        <AppButton size="sm" variant="secondary" @click="$emit('message', offer)">
          Message buyer
        </AppButton>
      </template>
      <template v-if="isFarmer() && ['delivered', 'completed'].includes(offer.status)">
        <AppButton
          v-if="showSettleButton"
          size="sm"
          variant="primary"
          :loading="loading"
          @click="$emit('settle-balance', offer)"
        >
          Confirm full payment
        </AppButton>
        <span
          v-if="showSettledPill"
          class="inline-flex items-center rounded-full bg-moss-100 text-moss-800 text-xs font-medium px-2.5 py-1"
        >
          Fully settled
        </span>
        <AppButton size="sm" variant="secondary" @click="$emit('message', offer)">
          Message buyer
        </AppButton>
      </template>
    </div>
  </AppCard>
</template>
