<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/atoms/AppButton.vue'
import AppCard from '@/components/atoms/AppCard.vue'
import {
  REPORT_OUTCOME,
  REPORT_OUTCOME_LABEL_KEYS,
  REPORT_REASON,
  REPORT_STATUS,
  REPORT_STATUS_LABEL_KEYS,
  REPORT_TARGET_LABEL_KEYS,
  TERMINAL_REPORT_STATUSES,
} from '@/constants/reporting'

const { t } = useI18n()

const REASON_LABEL_KEYS = {
  [REPORT_REASON.SPAM]: 'market.report.reason_spam',
  [REPORT_REASON.INAPPROPRIATE]: 'market.report.reason_inappropriate',
  [REPORT_REASON.MISINFORMATION]: 'market.report.reason_misinformation',
  [REPORT_REASON.HARASSMENT]: 'market.report.reason_harassment',
  [REPORT_REASON.OFF_TOPIC]: 'market.report.reason_off_topic',
  [REPORT_REASON.OTHER]: 'market.report.reason_other',
  [REPORT_REASON.SUSPECTED_FRAUD]: 'market.report.reason_suspected_fraud',
  [REPORT_REASON.PROHIBITED_ITEM]: 'market.report.reason_prohibited_item',
}

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
const typeLabel = computed(() =>
  t(REPORT_TARGET_LABEL_KEYS[props.report?.reportable_type] ?? 'admin.reports.content_fallback'),
)
const statusLabel = computed(() => {
  const labelKey = REPORT_STATUS_LABEL_KEYS[status.value]
  return labelKey ? t(labelKey) : status.value
})
const statusBadgeClass = computed(
  () => STATUS_BADGE_CLASSES[status.value] ?? 'bg-stone-200 text-stone-700',
)
const reporterName = computed(
  () => props.report?.reporter?.name ?? t('admin.reports.former_member'),
)
const reporterEmail = computed(() => props.report?.reporter?.email ?? '')
const targetBody = computed(() => target.value?.body ?? target.value?.description ?? '')
const targetVisibility = computed(() =>
  t(target.value?.is_hidden ? 'admin.reports.hidden' : 'admin.reports.visible'),
)
const outcomeLabel = computed(() => {
  const labelKey = REPORT_OUTCOME_LABEL_KEYS[props.report?.outcome]
  return labelKey ? t(labelKey) : ''
})

function reasonLabel() {
  const labelKey = REASON_LABEL_KEYS[props.report?.reason]
  return labelKey ? t(labelKey) : (props.report?.reason ?? t('admin.reports.unknown'))
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
      <span class="text-xs text-stone-500">{{
        t('admin.reports.version', { version: report?.version ?? '—' })
      }}</span>
    </div>

    <div>
      <h3 class="font-serif text-xl font-bold text-stone-900">{{ t('admin.reports.reporter') }}</h3>
      <p class="mt-1 text-sm text-stone-600">
        {{ reporterName }}<span v-if="reporterEmail"> · {{ reporterEmail }}</span>
      </p>
      <p class="mt-2 break-words text-sm leading-relaxed text-stone-700">
        {{ report?.description || t('admin.reports.no_desc') }}
      </p>
    </div>

    <AppCard padding="p-5">
      <h3 class="font-serif text-xl font-bold text-stone-900">{{ t('admin.reports.snapshot') }}</h3>
      <div v-if="snapshot" class="mt-2 space-y-1 text-sm text-stone-600">
        <p class="break-words font-medium text-stone-900">
          {{ snapshot.title || t('admin.reports.untitled') }}
        </p>
        <p class="break-words leading-relaxed">
          {{ snapshot.excerpt || t('admin.reports.no_excerpt') }}
        </p>
        <p v-if="snapshot.status">
          {{ t('admin.reports.business_status', { status: snapshot.status }) }}
        </p>
        <p class="text-xs text-stone-500">
          {{
            t('admin.reports.captured', {
              when: snapshot.captured_at || t('admin.reports.captured_unknown'),
            })
          }}
        </p>
      </div>
      <p v-else class="mt-2 text-sm text-stone-600">
        {{ t('admin.reports.no_snapshot') }}
      </p>
    </AppCard>

    <AppCard padding="p-5">
      <h3 class="font-serif text-xl font-bold text-stone-900">{{ t('admin.reports.current') }}</h3>
      <div v-if="target" class="mt-2 space-y-1 text-sm text-stone-600">
        <p class="break-words font-medium text-stone-900">
          {{ target.title || t('admin.reports.untitled') }}
        </p>
        <p v-if="targetBody" class="break-words leading-relaxed">{{ targetBody }}</p>
        <p>
          {{ t('admin.reports.visibility') }}
          <span class="font-medium text-stone-900">{{ targetVisibility }}</span>
        </p>
        <p v-if="target.is_hidden && target.hidden_reason" class="break-words">
          {{ t('admin.reports.hidden_reason', { reason: target.hidden_reason }) }}
        </p>
        <p v-if="target.author?.name">
          {{ t('admin.reports.author', { name: target.author.name }) }}
        </p>
      </div>
      <div v-else class="mt-2 text-sm text-stone-600">
        <p class="font-medium text-stone-900">{{ t('admin.reports.target_gone') }}</p>
        <p>{{ t('admin.reports.gone_desc') }}</p>
      </div>
    </AppCard>

    <AppCard v-if="isTerminal" padding="p-5">
      <h3 class="font-serif text-xl font-bold text-stone-900">
        {{ t('admin.reports.resolution') }}
      </h3>
      <div class="mt-2 space-y-1 text-sm text-stone-600">
        <p v-if="outcomeLabel">{{ t('admin.reports.outcome', { label: outcomeLabel }) }}</p>
        <p class="break-words">{{ report?.resolution_note || t('admin.reports.no_note') }}</p>
        <p v-if="report?.reviewed_at" class="text-xs text-stone-500">
          {{ t('admin.reports.reviewed', { when: report.reviewed_at }) }}
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
        {{ t('admin.reports.btn_reviewing') }}
      </AppButton>
      <AppButton
        v-if="canResolve && target"
        variant="danger"
        :disabled="busy"
        data-testid="report-decision-resolve-hidden"
        @click="decide(REPORT_STATUS.RESOLVED, REPORT_OUTCOME.HIDDEN)"
      >
        {{ t('admin.reports.btn_hide') }}
      </AppButton>
      <AppButton
        v-if="canResolve"
        variant="outline"
        :disabled="busy"
        data-testid="report-decision-resolve-no-action"
        @click="decide(REPORT_STATUS.RESOLVED, REPORT_OUTCOME.NO_ACTION)"
      >
        {{ t('admin.reports.btn_noaction') }}
      </AppButton>
      <AppButton
        v-if="canResolve"
        variant="secondary"
        :disabled="busy"
        data-testid="report-decision-dismiss"
        @click="decide(REPORT_STATUS.DISMISSED, null)"
      >
        {{ t('admin.reports.btn_dismiss') }}
      </AppButton>
    </div>
  </div>
</template>
