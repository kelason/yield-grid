<script setup>
import { computed } from 'vue'
import AppButton from '@/components/atoms/AppButton.vue'
import AppCard from '@/components/atoms/AppCard.vue'
import {
  REPORT_OUTCOME,
  REPORT_OUTCOME_LABELS,
  REPORT_REASON_OPTIONS,
  REPORT_STATUS,
  REPORT_STATUS_LABELS,
  REPORT_TARGET_LABELS,
  TERMINAL_REPORT_STATUSES,
} from '@/constants/reporting'

const STATUS_BADGE_CLASSES = {
  open: 'bg-harvest-100 text-harvest-800',
  reviewing: 'bg-dew-100 text-dew-800',
  resolved: 'bg-moss-100 text-moss-800',
  dismissed: 'bg-stone-200 text-stone-700',
}

const props = defineProps({
  report: { type: Object, default: null },
  busy: Boolean,
})

const emit = defineEmits(['decision'])

const status = computed(() => props.report?.status ?? '')
const isTerminal = computed(() => TERMINAL_REPORT_STATUSES.includes(status.value))
const canReview = computed(() => status.value === REPORT_STATUS.OPEN)
const canResolve = computed(
  () => status.value === REPORT_STATUS.OPEN || status.value === REPORT_STATUS.REVIEWING,
)
const target = computed(() =>
  props.report?.target_available && props.report?.target ? props.report.target : null,
)
const snapshot = computed(() => props.report?.target_snapshot ?? null)
const typeLabel = computed(() => REPORT_TARGET_LABELS[props.report?.reportable_type] ?? 'Content')
const statusLabel = computed(() => REPORT_STATUS_LABELS[status.value] ?? status.value)
const statusBadgeClass = computed(
  () => STATUS_BADGE_CLASSES[status.value] ?? 'bg-stone-200 text-stone-700',
)
const reporterName = computed(() => props.report?.reporter?.name ?? 'Former member')
const reporterEmail = computed(() => props.report?.reporter?.email ?? '')
const targetBody = computed(() => target.value?.body ?? target.value?.description ?? '')
const targetVisibility = computed(() => (target.value?.is_hidden ? 'Hidden' : 'Visible'))
const outcomeLabel = computed(() => REPORT_OUTCOME_LABELS[props.report?.outcome] ?? '')

function reasonLabel() {
  return (
    REPORT_REASON_OPTIONS.find((option) => option.value === props.report?.reason)?.label ??
    props.report?.reason ??
    'Unknown'
  )
}

function decide(statusValue, outcome) {
  if (!props.busy) emit('decision', { status: statusValue, outcome })
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center gap-2">
      <span
        class="inline-flex items-center rounded-full bg-soil-100 px-2.5 py-0.5 text-xs font-medium text-soil-800"
      >
        {{ typeLabel }}
      </span>
      <span
        class="inline-flex items-center rounded-full bg-soil-100 px-2.5 py-0.5 text-xs font-medium text-soil-800"
      >
        {{ reasonLabel() }}
      </span>
      <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
        :class="statusBadgeClass"
      >
        {{ statusLabel }}
      </span>
      <span class="text-xs text-stone-500">Version {{ report?.version ?? '—' }}</span>
    </div>

    <div>
      <h3 class="font-serif text-xl font-bold text-stone-900">Reporter</h3>
      <p class="mt-1 text-sm text-stone-600">
        {{ reporterName }}<span v-if="reporterEmail"> · {{ reporterEmail }}</span>
      </p>
      <p class="mt-2 break-words text-sm leading-relaxed text-stone-700">
        {{ report?.description || 'No description provided.' }}
      </p>
    </div>

    <AppCard padding="p-5">
      <h3 class="font-serif text-xl font-bold text-stone-900">Source snapshot</h3>
      <div v-if="snapshot" class="mt-2 space-y-1 text-sm text-stone-600">
        <p class="break-words font-medium text-stone-900">{{ snapshot.title || 'Untitled' }}</p>
        <p class="break-words leading-relaxed">{{ snapshot.excerpt || 'No excerpt.' }}</p>
        <p v-if="snapshot.status">Business status: {{ snapshot.status }}</p>
        <p class="text-xs text-stone-500">
          Captured {{ snapshot.captured_at || 'at an unknown time' }}
        </p>
      </div>
      <p v-else class="mt-2 text-sm text-stone-600">
        No snapshot was captured for this report. A missing snapshot does not mean the target is
        gone.
      </p>
    </AppCard>

    <AppCard padding="p-5">
      <h3 class="font-serif text-xl font-bold text-stone-900">Current state</h3>
      <div v-if="target" class="mt-2 space-y-1 text-sm text-stone-600">
        <p class="break-words font-medium text-stone-900">{{ target.title || 'Untitled' }}</p>
        <p v-if="targetBody" class="break-words leading-relaxed">{{ targetBody }}</p>
        <p>
          Visibility:
          <span class="font-medium text-stone-900">{{ targetVisibility }}</span>
        </p>
        <p v-if="target.is_hidden && target.hidden_reason" class="break-words">
          Hidden reason: {{ target.hidden_reason }}
        </p>
        <p v-if="target.author?.name">Author: {{ target.author.name }}</p>
      </div>
      <div v-else class="mt-2 text-sm text-stone-600">
        <p class="font-medium text-stone-900">Target unavailable</p>
        <p>This content is gone. Only dismissal or no-action resolution is available.</p>
      </div>
    </AppCard>

    <AppCard v-if="isTerminal" padding="p-5">
      <h3 class="font-serif text-xl font-bold text-stone-900">Resolution</h3>
      <div class="mt-2 space-y-1 text-sm text-stone-600">
        <p v-if="outcomeLabel">Outcome: {{ outcomeLabel }}</p>
        <p class="break-words">{{ report?.resolution_note || 'No note recorded.' }}</p>
        <p v-if="report?.reviewed_at" class="text-xs text-stone-500">
          Reviewed {{ report.reviewed_at }}
        </p>
      </div>
    </AppCard>

    <div v-if="!isTerminal" class="flex flex-wrap gap-3">
      <AppButton
        v-if="canReview"
        variant="outline"
        :disabled="busy"
        data-testid="report-decision-reviewing"
        @click="decide(REPORT_STATUS.REVIEWING, null)"
      >
        Mark as reviewing
      </AppButton>
      <AppButton
        v-if="canResolve && target"
        variant="danger"
        :disabled="busy"
        data-testid="report-decision-resolve-hidden"
        @click="decide(REPORT_STATUS.RESOLVED, REPORT_OUTCOME.HIDDEN)"
      >
        Resolve and hide
      </AppButton>
      <AppButton
        v-if="canResolve"
        variant="outline"
        :disabled="busy"
        data-testid="report-decision-resolve-no-action"
        @click="decide(REPORT_STATUS.RESOLVED, REPORT_OUTCOME.NO_ACTION)"
      >
        Resolve with no action
      </AppButton>
      <AppButton
        v-if="canResolve"
        variant="secondary"
        :disabled="busy"
        data-testid="report-decision-dismiss"
        @click="decide(REPORT_STATUS.DISMISSED, null)"
      >
        Dismiss
      </AppButton>
    </div>
  </div>
</template>
