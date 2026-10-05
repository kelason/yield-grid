<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/atoms/AppButton.vue'
import AppCard from '@/components/atoms/AppCard.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import FormField from '@/components/molecules/FormField.vue'
import { INSURANCE_LIMITS } from '@/constants/insurance'

defineProps({
  isSaving: { type: Boolean, default: false },
})

const emit = defineEmits(['submit-claim', 'cancel'])

const { t } = useI18n()

const LOSS_CAUSES = ['typhoon', 'flood', 'drought', 'pest', 'disease', 'other']

const maxLossDate = new Date().toLocaleDateString('en-CA')

const lossDate = ref('')
const cause = ref(LOSS_CAUSES[0])
const description = ref('')
const formError = ref('')

const causeOptions = computed(() =>
  LOSS_CAUSES.map((value) => ({ value, label: t(`insurance.claims.cause_${value}`) })),
)

const submit = () => {
  formError.value = ''
  if (!lossDate.value || lossDate.value > maxLossDate) {
    formError.value = t('insurance.claims.loss_date_label')
    return
  }
  if (description.value.length > INSURANCE_LIMITS.CLAIM_DESCRIPTION_MAX_LENGTH) {
    formError.value = `${t('insurance.claims.description_label')}: max ${INSURANCE_LIMITS.CLAIM_DESCRIPTION_MAX_LENGTH}`
    return
  }
  emit('submit-claim', {
    loss_date: lossDate.value,
    cause: cause.value,
    description: description.value || null,
  })
}
</script>

<template>
  <AppCard>
    <h2 class="font-serif text-2xl font-bold text-stone-900">
      {{ t('insurance.claims.file_claim') }}
    </h2>
    <form class="mt-4 space-y-4" @submit.prevent="submit">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <FormField
          id="claim-loss-date"
          v-model="lossDate"
          data-testid="claim-loss-date"
          type="date"
          :label="t('insurance.claims.loss_date_label')"
          :max="maxLossDate"
          required
        />
        <AppSelect
          id="claim-cause"
          v-model="cause"
          data-testid="claim-cause"
          :label="t('insurance.claims.cause_label')"
          :options="causeOptions"
          required
        />
      </div>
      <div>
        <label for="claim-description" class="text-sm font-medium text-soil-700">
          {{ t('insurance.claims.description_label') }}
        </label>
        <textarea
          id="claim-description"
          v-model="description"
          data-testid="claim-description"
          rows="3"
          :maxlength="INSURANCE_LIMITS.CLAIM_DESCRIPTION_MAX_LENGTH"
          class="mt-1 block w-full px-4 py-2.5 border border-stone-300 rounded-xl shadow-soft bg-stone-50 text-stone-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-moss-500 focus:border-moss-500 focus:bg-white sm:text-sm transition-all duration-200"
        />
        <p class="mt-1 text-sm text-stone-600 font-light">
          {{ description.length }} / {{ INSURANCE_LIMITS.CLAIM_DESCRIPTION_MAX_LENGTH }}
        </p>
      </div>
      <p
        v-if="formError"
        data-testid="claim-error"
        class="text-sm font-medium text-red-600"
        role="alert"
      >
        {{ formError }}
      </p>
      <div class="flex gap-2">
        <AppButton type="submit" variant="primary" data-testid="claim-submit" :loading="isSaving">
          {{ t('insurance.claims.file_claim') }}
        </AppButton>
        <AppButton variant="ghost" data-testid="claim-cancel" @click="emit('cancel')">
          {{ t('insurance.common.cancel') }}
        </AppButton>
      </div>
    </form>
  </AppCard>
</template>
