<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/atoms/AppButton.vue'
import AppCard from '@/components/atoms/AppCard.vue'
import EmptyState from '@/components/molecules/EmptyState.vue'
import FormField from '@/components/molecules/FormField.vue'
import InsuranceStatusBadge from '@/components/molecules/InsuranceStatusBadge.vue'
import { INSURANCE_LIMITS } from '@/constants/insurance'

defineProps({
  claims: { type: Array, required: true },
  enrollmentId: { type: Number, required: true },
})

defineEmits(['file-claim', 'advance-claim', 'record-payout'])

const { t } = useI18n()

const NEXT_STATUS = {
  draft: 'notice_of_loss_filed',
  notice_of_loss_filed: 'field_inspection',
  field_inspection: 'adjustment',
  adjustment: 'approved',
}

const REJECTABLE = ['notice_of_loss_filed', 'field_inspection', 'adjustment']

const canRecordRejection = (status) => REJECTABLE.includes(status)

const payoutAmounts = ref({})

const nextStatusFor = (status) => NEXT_STATUS[status] || null

const causeLabel = (cause) => {
  const key = `insurance.claims.cause_${cause}`
  const translated = t(key)
  return translated === key ? cause : translated
}

const payoutFor = (claimId) => payoutAmounts.value[claimId] || ''

const setPayout = (claimId, value) => {
  payoutAmounts.value = { ...payoutAmounts.value, [claimId]: value }
}

const payoutAmountFor = (claimId) => {
  const raw = payoutFor(claimId)
  if (raw === '' || raw === null || Number.isNaN(Number(raw))) {
    return null
  }
  return Number(raw)
}
</script>

<template>
  <AppCard>
    <div class="flex items-center justify-between gap-3">
      <h2 class="font-serif text-2xl font-bold text-stone-900">
        {{ t('insurance.claims.title') }}
      </h2>
      <AppButton size="sm" variant="primary" data-testid="file-claim" @click="$emit('file-claim')">
        {{ t('insurance.claims.file_claim') }}
      </AppButton>
    </div>
    <div v-if="claims.length === 0" data-testid="claims-empty" class="mt-4">
      <EmptyState :title="t('insurance.claims.title')" :description="t('insurance.claims.empty')" />
    </div>
    <ul v-else class="mt-4 space-y-3">
      <li
        v-for="claim in claims"
        :key="claim.id"
        data-testid="claim-card"
        class="rounded-2xl border p-4 transition-all duration-300 hover:shadow-organic"
        :class="
          claim.notice_of_loss_overdue
            ? 'border-harvest-300 bg-harvest-50'
            : 'border-stone-200 bg-stone-50'
        "
      >
        <div class="flex flex-wrap items-center justify-between gap-2">
          <span class="text-base font-medium text-stone-900"
            >{{ causeLabel(claim.cause) }} · {{ claim.loss_date }}</span
          >
          <InsuranceStatusBadge :status="claim.status" kind="claim" />
        </div>
        <p class="mt-2 text-sm text-stone-600">
          {{ t('insurance.claims.nl_deadline') }}: {{ claim.notice_of_loss_deadline }}
          <span v-if="claim.notice_of_loss_overdue" class="font-medium text-harvest-800">
            — {{ t('insurance.claims.nl_overdue') }}
          </span>
          <span v-else-if="claim.notice_of_loss_filed_at" class="font-medium text-moss-700">
            — {{ t('insurance.claims.nl_filed') }}
          </span>
        </p>
        <div class="mt-3 flex flex-wrap items-center gap-2">
          <AppButton
            v-if="nextStatusFor(claim.status)"
            size="sm"
            variant="outline"
            :data-testid="`advance-claim-${claim.id}`"
            @click="$emit('advance-claim', claim.id, nextStatusFor(claim.status))"
          >
            {{ t('insurance.claims.advance') }}
          </AppButton>
          <AppButton
            v-if="canRecordRejection(claim.status)"
            size="sm"
            variant="ghost"
            :data-testid="`reject-claim-${claim.id}`"
            @click="$emit('advance-claim', claim.id, 'rejected')"
          >
            {{ t('insurance.claims.status_rejected') }}
          </AppButton>
          <template v-if="claim.status === 'approved'">
            <FormField
              :id="`payout-${claim.id}`"
              :data-testid="`payout-amount-${claim.id}`"
              type="number"
              :label="t('insurance.claims.paid_amount_label')"
              :model-value="payoutFor(claim.id)"
              :min="0"
              :max="INSURANCE_LIMITS.PAYOUT_AMOUNT_MAX"
              @update:model-value="setPayout(claim.id, $event)"
            />
            <AppButton
              size="sm"
              variant="harvest"
              :data-testid="`mark-paid-${claim.id}`"
              @click="$emit('record-payout', claim.id, payoutAmountFor(claim.id))"
            >
              {{ t('insurance.claims.mark_paid') }}
            </AppButton>
          </template>
        </div>
      </li>
    </ul>
  </AppCard>
</template>
