<script setup>
import { computed } from 'vue'
import AppButton from '../atoms/AppButton.vue'
import AppSelect from '../atoms/AppSelect.vue'
import FormField from '../molecules/FormField.vue'
import {
  REPORT_DESCRIPTION_MAX_LENGTH,
  REPORT_REASON,
  REPORT_REASON_OPTIONS,
  REPORT_TARGET_LABELS,
} from '@/constants/reporting'

const props = defineProps({
  target: { type: Object, required: true },
  reason: { type: String, default: '' },
  description: { type: String, default: '' },
  busy: Boolean,
  error: { type: String, default: '' },
  fieldError: { type: String, default: '' },
})

defineEmits(['update:reason', 'update:description', 'submit', 'cancel'])

const typeLabel = computed(() => REPORT_TARGET_LABELS[props.target?.reportable_type] ?? 'Content')
const requiresDescription = computed(() => props.reason === REPORT_REASON.OTHER)
</script>

<template>
  <form class="space-y-5" @submit.prevent="$emit('submit')">
    <div class="rounded-2xl bg-stone-100 p-4">
      <p class="text-xs font-semibold uppercase tracking-wide text-stone-500">{{ typeLabel }}</p>
      <p class="mt-1 break-words font-serif text-lg font-bold text-stone-900">
        {{ target?.title || 'Untitled content' }}
      </p>
    </div>

    <AppSelect
      id="report-reason"
      label="Reason"
      :model-value="reason"
      :options="REPORT_REASON_OPTIONS"
      :disabled="busy"
      :required="true"
      :error="fieldError"
      @update:model-value="$emit('update:reason', $event)"
    />

    <FormField
      id="report-description"
      label="Description"
      :model-value="description"
      :multiline="true"
      :maxlength="REPORT_DESCRIPTION_MAX_LENGTH"
      :disabled="busy"
      :required="requiresDescription"
      placeholder="What should the moderation team know?"
      :hint="requiresDescription ? 'Description is required when the reason is Other.' : ''"
      @update:model-value="$emit('update:description', $event)"
    />

    <p v-if="error" role="alert" class="text-sm text-red-600">{{ error }}</p>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
      <AppButton type="button" variant="secondary" :disabled="busy" @click="$emit('cancel')">
        Cancel
      </AppButton>
      <AppButton type="submit" variant="primary" :loading="busy" :disabled="busy">
        Submit report
      </AppButton>
    </div>
  </form>
</template>
