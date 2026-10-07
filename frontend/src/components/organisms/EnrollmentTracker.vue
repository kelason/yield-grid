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
  enrollments: { type: Array, required: true },
  isPackBusy: { type: Boolean, default: false },
})

const emit = defineEmits(['request-pack', 'advance-enrollment', 'record-policy-details'])

const { t } = useI18n()

const NEXT_STATUS = {
  draft: 'documents_ready',
  documents_ready: 'submitted_to_mao',
  submitted_to_mao: 'active',
  active: 'expired',
}

const CANCELLABLE = ['draft', 'documents_ready', 'submitted_to_mao']

const cicForms = ref({})

const nextStatusFor = (status) => NEXT_STATUS[status] || null
const canCancel = (status) => CANCELLABLE.includes(status)
const canRecordRejection = (status) => status === 'submitted_to_mao'

const needsPackFirst = (enrollment) =>
  enrollment.status === 'draft' && enrollment.pack_status !== 'ready'

const programLabel = (program) => t(`insurance.guide.program_${program}`)
const seasonLabel = (enrollment) =>
  `${t(`insurance.guide.season_${enrollment.season}`)} ${enrollment.season_year}`

const blankCicForm = () => ({
  open: false,
  number: '',
  coverage: '',
  enrolled: '',
  expires: '',
  error: '',
})

const cicFormFor = (id) => cicForms.value[id] || blankCicForm()

const setCicForm = (id, patch) => {
  cicForms.value = { ...cicForms.value, [id]: { ...cicFormFor(id), ...patch } }
}

const toggleCicForm = (id) => {
  const form = cicFormFor(id)
  setCicForm(id, { open: !form.open, error: '' })
}

const submitCicForm = (id) => {
  const form = cicFormFor(id)

  if (!form.number.trim()) {
    setCicForm(id, { error: t('insurance.guide.record_cic') })
    return
  }
  if (form.number.length > INSURANCE_LIMITS.CIC_NUMBER_MAX_LENGTH) {
    setCicForm(id, { error: `max ${INSURANCE_LIMITS.CIC_NUMBER_MAX_LENGTH}` })
    return
  }
  if (form.coverage !== '' && (Number.isNaN(Number(form.coverage)) || Number(form.coverage) < 0)) {
    setCicForm(id, { error: t('insurance.guide.coverage_label') })
    return
  }
  if (form.enrolled && form.expires && form.expires < form.enrolled) {
    setCicForm(id, { error: t('insurance.guide.expires_label') })
    return
  }

  emit('record-policy-details', id, {
    cic_number: form.number.trim(),
    coverage_amount_php: form.coverage === '' ? null : Number(form.coverage),
    enrolled_at: form.enrolled,
    expires_at: form.expires,
  })
  setCicForm(id, { open: false, error: '' })
}
</script>

<template>
  <AppCard>
    <h2 class="font-serif text-2xl font-bold text-stone-900">
      {{ t('insurance.enrollments.title') }}
    </h2>
    <div v-if="enrollments.length === 0" data-testid="enrollments-empty" class="mt-4">
      <EmptyState
        :title="t('insurance.enrollments.title')"
        :description="t('insurance.enrollments.empty')"
      />
    </div>
    <div v-else class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-5">
      <article
        v-for="enrollment in enrollments"
        :key="enrollment.id"
        data-testid="enrollment-card"
        class="rounded-2xl border border-stone-200 bg-white shadow-soft p-5 transition-all duration-300"
      >
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h3 class="font-serif text-lg font-bold text-stone-900">
            {{ programLabel(enrollment.program) }} · {{ seasonLabel(enrollment) }}
          </h3>
          <InsuranceStatusBadge :status="enrollment.status" kind="enrollment" />
        </div>
        <p v-if="enrollment.cic_number" class="mt-1 text-sm text-stone-600">
          CIC: {{ enrollment.cic_number }}
        </p>
        <div class="mt-3 flex flex-wrap gap-2">
          <AppButton
            size="sm"
            variant="secondary"
            :data-testid="`request-pack-${enrollment.id}`"
            :loading="isPackBusy"
            :disabled="isPackBusy"
            @click="$emit('request-pack', enrollment.id)"
          >
            <svg
              class="h-4 w-4 flex-shrink-0"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.5"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"
              />
            </svg>
            {{ t('insurance.guide.download_pack') }}
          </AppButton>
          <AppButton
            v-if="nextStatusFor(enrollment.status)"
            size="sm"
            variant="outline"
            :data-testid="`advance-enrollment-${enrollment.id}`"
            :disabled="needsPackFirst(enrollment)"
            :title="
              needsPackFirst(enrollment) ? t('insurance.enrollments.pack_required_first') : null
            "
            @click="$emit('advance-enrollment', enrollment.id, nextStatusFor(enrollment.status))"
          >
            <svg
              class="h-4 w-4 flex-shrink-0"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.5"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"
              />
            </svg>
            {{ t(`insurance.enrollments.status_${nextStatusFor(enrollment.status)}`) }}
          </AppButton>
          <AppButton
            v-if="enrollment.status === 'submitted_to_mao'"
            size="sm"
            variant="primary"
            :data-testid="`record-cic-${enrollment.id}`"
            @click="toggleCicForm(enrollment.id)"
          >
            <svg
              class="h-4 w-4 flex-shrink-0"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.5"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
              />
            </svg>
            {{ t('insurance.guide.record_cic') }}
          </AppButton>
        </div>
        <p v-if="needsPackFirst(enrollment)" class="mt-2 text-xs font-medium text-harvest-700">
          {{ t('insurance.enrollments.pack_required_first') }}
        </p>
        <div
          v-if="canCancel(enrollment.status) || canRecordRejection(enrollment.status)"
          :data-testid="`danger-zone-${enrollment.id}`"
          class="mt-3 flex flex-wrap gap-2 border-t border-stone-100 pt-3"
        >
          <AppButton
            v-if="canCancel(enrollment.status)"
            size="sm"
            variant="ghost"
            :data-testid="`cancel-enrollment-${enrollment.id}`"
            @click="$emit('advance-enrollment', enrollment.id, 'cancelled')"
          >
            <svg
              class="h-4 w-4 flex-shrink-0"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.5"
              aria-hidden="true"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
            {{ t('insurance.enrollments.cancel_enrollment') }}
          </AppButton>
          <AppButton
            v-if="canRecordRejection(enrollment.status)"
            size="sm"
            variant="ghost"
            :data-testid="`reject-enrollment-${enrollment.id}`"
            @click="$emit('advance-enrollment', enrollment.id, 'rejected')"
          >
            <svg
              class="h-4 w-4 flex-shrink-0"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.5"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"
              />
            </svg>
            {{ t('insurance.enrollments.reject_enrollment') }}
          </AppButton>
        </div>
        <form
          v-if="cicFormFor(enrollment.id).open"
          class="mt-4 space-y-3 rounded-2xl bg-stone-50 p-4"
          @submit.prevent="submitCicForm(enrollment.id)"
        >
          <FormField
            :id="`cic-number-${enrollment.id}`"
            :model-value="cicFormFor(enrollment.id).number"
            :data-testid="`cic-number-${enrollment.id}`"
            type="text"
            :label="t('insurance.guide.record_cic')"
            :placeholder="t('insurance.guide.cic_placeholder')"
            :maxlength="INSURANCE_LIMITS.CIC_NUMBER_MAX_LENGTH"
            required
            @update:model-value="setCicForm(enrollment.id, { number: $event })"
          />
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <FormField
              :id="`cic-coverage-${enrollment.id}`"
              :model-value="cicFormFor(enrollment.id).coverage"
              :data-testid="`cic-coverage-${enrollment.id}`"
              type="number"
              :label="t('insurance.guide.coverage_label')"
              :min="0"
              @update:model-value="setCicForm(enrollment.id, { coverage: $event })"
            />
            <FormField
              :id="`cic-enrolled-${enrollment.id}`"
              :model-value="cicFormFor(enrollment.id).enrolled"
              :data-testid="`cic-enrolled-${enrollment.id}`"
              type="date"
              :label="t('insurance.guide.enrolled_label')"
              @update:model-value="setCicForm(enrollment.id, { enrolled: $event })"
            />
            <FormField
              :id="`cic-expires-${enrollment.id}`"
              :model-value="cicFormFor(enrollment.id).expires"
              :data-testid="`cic-expires-${enrollment.id}`"
              type="date"
              :label="t('insurance.guide.expires_label')"
              @update:model-value="setCicForm(enrollment.id, { expires: $event })"
            />
          </div>
          <p
            v-if="cicFormFor(enrollment.id).error"
            :data-testid="`cic-error-${enrollment.id}`"
            class="text-sm font-medium text-red-600"
            role="alert"
          >
            {{ cicFormFor(enrollment.id).error }}
          </p>
          <AppButton type="submit" size="sm" variant="primary">
            {{ t('insurance.common.confirm') }}
          </AppButton>
        </form>
      </article>
    </div>
  </AppCard>
</template>
