<script setup>
import AppButton from '../atoms/AppButton.vue'
import AppAlert from '../atoms/AppAlert.vue'
import { PAYMENT_OPTION } from '@/constants/payment'
defineProps({
  offer: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
})
defineEmits(['pay', 'cancel'])
</script>
<template>
  <div v-if="offer" class="space-y-5">
    <p class="text-sm text-stone-600">
      {{ offer.quantity_kg }} kg × ₱{{ offer.price_per_kg }}/kg =
      <strong class="text-stone-900"
        >₱{{ Number(offer.total_price).toLocaleString('en-PH') }}</strong
      >
    </p>
    <AppAlert v-if="error" type="error">{{ error }}</AppAlert>
    <div class="flex flex-wrap justify-end gap-3">
      <AppButton variant="ghost" :disabled="loading" @click="$emit('cancel')">Cancel</AppButton>
      <AppButton variant="secondary" :loading="loading" @click="$emit('pay', PAYMENT_OPTION.CASH)"
        >Pay with cash</AppButton
      >
      <AppButton :loading="loading" @click="$emit('pay', PAYMENT_OPTION.PAYMONGO)"
        >Pay online</AppButton
      >
    </div>
  </div>
</template>
