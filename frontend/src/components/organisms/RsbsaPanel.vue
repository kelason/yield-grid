<script setup>
import { ref, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/atoms/AppButton.vue'
import AppCard from '@/components/atoms/AppCard.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import FormField from '@/components/molecules/FormField.vue'
import { INSURANCE_LIMITS } from '@/constants/insurance'

const props = defineProps({
  profile: { type: Object, default: null },
  isSaving: { type: Boolean, default: false },
})

const emit = defineEmits(['save-profile'])

const { t } = useI18n()

const rsbsaNumber = ref('')
const rsbsaStatus = ref('not_registered')

const statusOptions = computed(() => [
  { value: 'registered', label: t('insurance.rsbsa.registered') },
  { value: 'not_registered', label: t('insurance.rsbsa.not_registered') },
])

watch(
  () => props.profile,
  (profile) => {
    if (profile) {
      rsbsaNumber.value = profile.rsbsa_number || ''
      rsbsaStatus.value = profile.rsbsa_status
    }
  },
  { immediate: true },
)

const save = () => {
  emit('save-profile', { rsbsa_number: rsbsaNumber.value || null, rsbsa_status: rsbsaStatus.value })
}
</script>

<template>
  <AppCard>
    <h2 class="font-serif text-2xl font-bold text-stone-900">{{ t('insurance.rsbsa.title') }}</h2>
    <p class="mt-1 text-base text-stone-600 font-normal leading-relaxed">
      {{ t('insurance.rsbsa.description') }}
    </p>
    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
      <FormField
        id="insurance-rsbsa-number"
        v-model="rsbsaNumber"
        data-testid="rsbsa-number"
        type="text"
        :label="t('insurance.rsbsa.number_label')"
        :placeholder="t('insurance.rsbsa.number_placeholder')"
        :maxlength="INSURANCE_LIMITS.RSBSA_NUMBER_MAX_LENGTH"
      />
      <AppSelect
        id="insurance-rsbsa-status"
        v-model="rsbsaStatus"
        data-testid="rsbsa-status"
        :label="t('insurance.rsbsa.status_label')"
        :options="statusOptions"
      />
    </div>
    <p v-if="rsbsaStatus === 'not_registered'" class="mt-2 text-sm text-stone-600 font-normal">
      {{ t('insurance.rsbsa.unregistered_guidance') }}
    </p>
    <AppButton
      class="mt-4"
      variant="primary"
      data-testid="rsbsa-save"
      :loading="isSaving"
      @click="save"
    >
      {{ t('insurance.rsbsa.save') }}
    </AppButton>
  </AppCard>
</template>
