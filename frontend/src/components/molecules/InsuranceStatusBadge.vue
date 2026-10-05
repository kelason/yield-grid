<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  status: { type: String, required: true },
  kind: {
    type: String,
    required: true,
    validator: (value) => ['enrollment', 'claim'].includes(value),
  },
})

const { t } = useI18n()

const ENROLLMENT_STYLES = {
  draft: 'bg-stone-100 text-stone-800',
  documents_ready: 'bg-dew-50 text-dew-700',
  submitted_to_mao: 'bg-dew-50 text-dew-700',
  active: 'bg-moss-100 text-moss-800',
  expired: 'bg-stone-100 text-stone-800',
  rejected: 'bg-red-100 text-red-800',
  cancelled: 'bg-stone-100 text-stone-800',
}

const CLAIM_STYLES = {
  draft: 'bg-stone-100 text-stone-800',
  notice_of_loss_filed: 'bg-harvest-100 text-harvest-800',
  field_inspection: 'bg-dew-50 text-dew-700',
  adjustment: 'bg-dew-50 text-dew-700',
  approved: 'bg-moss-100 text-moss-800',
  paid: 'bg-moss-100 text-moss-800',
  rejected: 'bg-red-100 text-red-800',
}

const NEUTRAL_STYLE = 'bg-stone-100 text-stone-800'

const badgeClass = computed(() => {
  const styles = props.kind === 'claim' ? CLAIM_STYLES : ENROLLMENT_STYLES
  return styles[props.status] || NEUTRAL_STYLE
})

const label = computed(() => {
  const key =
    props.kind === 'claim'
      ? `insurance.claims.status_${props.status}`
      : `insurance.enrollments.status_${props.status}`
  const translated = t(key)
  return translated === key ? props.status : translated
})
</script>

<template>
  <span
    data-testid="insurance-status-badge"
    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition-colors duration-200"
    :class="badgeClass"
  >
    {{ label }}
  </span>
</template>
