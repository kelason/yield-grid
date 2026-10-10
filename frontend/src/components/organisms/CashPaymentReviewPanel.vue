<script setup>
import FormField from '../molecules/FormField.vue'
import AppButton from '../atoms/AppButton.vue'
import AppAlert from '../atoms/AppAlert.vue'
import { useI18n } from 'vue-i18n'
import { CASH_PAYMENT_LIMITS, CASH_PAYMENT_TYPE } from '@/constants/payment'
const { t } = useI18n()
defineProps({
  amount: { type: [String, Number], default: 0 },
  paymentType: { type: String, required: true },
  error: { type: String, default: '' },
  loading: { type: Boolean, default: false },
})
defineEmits(['update:amount', 'submit', 'cancel'])
</script>
<template>
  <form class="space-y-6" @submit.prevent="$emit('submit')">
    <p class="text-sm text-stone-600">{{ t('farmer.cash.instruction') }}</p>
    <AppAlert v-if="error" type="error">{{ error }}</AppAlert>
    <FormField
      id="cash-amount"
      :label="t('farmer.cash.amount_label')"
      type="number"
      :model-value="amount"
      @update:model-value="$emit('update:amount', $event)"
      :min="CASH_PAYMENT_LIMITS.MIN"
      :max="CASH_PAYMENT_LIMITS.MAX"
      :maxlength="CASH_PAYMENT_LIMITS.MAX_LENGTH"
      step="0.01"
      :disabled="loading || paymentType === CASH_PAYMENT_TYPE.FULL"
      required
    />
    <p v-if="paymentType === CASH_PAYMENT_TYPE.FULL" class="text-sm text-stone-600">
      {{ t('farmer.cash.locked_hint') }}
    </p>
    <div class="flex flex-wrap justify-end gap-3">
      <AppButton variant="ghost" :disabled="loading" @click="$emit('cancel')">{{
        t('shell.cancel')
      }}</AppButton>
      <AppButton type="submit" :loading="loading">{{ t('farmer.cash.review_button') }}</AppButton>
    </div>
  </form>
</template>
