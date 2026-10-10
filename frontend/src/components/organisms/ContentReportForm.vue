<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from '../atoms/AppButton.vue'
import AppSelect from '../atoms/AppSelect.vue'
import FormField from '../molecules/FormField.vue'
import {
  REPORT_DESCRIPTION_MAX_LENGTH,
  REPORT_REASON,
  REPORT_REASON_OPTIONS,
  REPORT_TARGET_LABEL_KEYS,
} from '@/constants/reporting'

const { t } = useI18n()

const props = defineProps({
  target: { type: Object, required: true },
  reason: { type: String, default: '' },
  description: { type: String, default: '' },
  busy: Boolean,
  error: { type: String, default: '' },
  fieldError: { type: String, default: '' },
})

defineEmits(['update:reason', 'update:description', 'submit', 'cancel'])

const typeLabel = computed(() =>
  t(REPORT_TARGET_LABEL_KEYS[props.target?.reportable_type] ?? 'market.report.content_fallback'),
)
const requiresDescription = computed(() => props.reason === REPORT_REASON.OTHER)
const reasonOptions = computed(() =>
  REPORT_REASON_OPTIONS.map((option) => ({ value: option.value, label: t(option.labelKey) })),
)
</script>

<template>
  <form class="space-y-5" @submit.prevent="$emit('submit')">
    <div class="rounded-2xl bg-stone-100 p-4">
      <p class="text-xs font-semibold uppercase tracking-wide text-stone-500">{{ typeLabel }}</p>
      <p class="mt-1 break-words font-serif text-lg font-bold text-stone-900">
        {{ target?.title || t('market.report.untitled') }}
      </p>
    </div>

    <AppSelect
      id="report-reason"
      :label="t('market.report.reason_label')"
      :model-value="reason"
      :options="reasonOptions"
      :disabled="busy"
      :required="true"
      :error="fieldError"
      @update:model-value="$emit('update:reason', $event)"
    />

    <FormField
      id="report-description"
      :label="t('market.report.desc_label')"
      :model-value="description"
      :multiline="true"
      :maxlength="REPORT_DESCRIPTION_MAX_LENGTH"
      :disabled="busy"
      :required="requiresDescription"
      :placeholder="t('market.report.desc_ph')"
      :hint="requiresDescription ? t('market.report.desc_hint_other') : ''"
      @update:model-value="$emit('update:description', $event)"
    />

    <p v-if="error" role="alert" class="text-sm text-red-600">{{ error }}</p>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
      <AppButton type="button" variant="secondary" :disabled="busy" @click="$emit('cancel')">
        {{ t('shell.cancel') }}
      </AppButton>
      <AppButton type="submit" variant="primary" :loading="busy" :disabled="busy">
        {{ t('market.report.submit') }}
      </AppButton>
    </div>
  </form>
</template>
