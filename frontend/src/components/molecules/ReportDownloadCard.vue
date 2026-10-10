<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/atoms/AppButton.vue'
import { CREDIT_REPORT_STATUS } from '@/constants/creditScoring'
const { t } = useI18n()

const props = defineProps({
  reportStatus: {
    type: String,
    default: CREDIT_REPORT_STATUS.NONE,
  },
  expiresAt: {
    type: String,
    default: '',
  },
  isGenerating: {
    type: Boolean,
    default: false,
  },
  isDownloading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['generate', 'download'])

const isReady = computed(() => props.reportStatus === CREDIT_REPORT_STATUS.READY)
const isFailed = computed(() => props.reportStatus === CREDIT_REPORT_STATUS.FAILED)
const isBusy = computed(
  () => props.isGenerating || props.reportStatus === CREDIT_REPORT_STATUS.GENERATING,
)

const statusText = computed(() => {
  if (isReady.value) {
    return props.expiresAt
      ? t('farmer.credit_report.ready_expiry', { date: props.expiresAt })
      : t('farmer.credit_report.ready')
  }
  if (isFailed.value) {
    return t('farmer.credit_report.failed')
  }
  if (isBusy.value) {
    return t('farmer.credit_report.busy')
  }
  return t('farmer.credit_report.none')
})
</script>

<template>
  <div class="bg-white rounded-2xl shadow-soft border border-stone-200 p-6">
    <h3 class="font-serif text-2xl font-bold text-stone-900">
      {{ t('farmer.credit_report.title') }}
    </h3>
    <p class="text-base text-stone-600 font-normal leading-relaxed mt-2">
      {{ t('farmer.credit_report.description') }}
    </p>
    <p
      data-testid="report-status"
      aria-live="polite"
      class="text-sm font-medium text-soil-700 mt-3"
    >
      {{ statusText }}
    </p>
    <div class="flex flex-wrap gap-3 mt-4">
      <AppButton
        v-if="!isReady"
        variant="primary"
        :loading="isGenerating"
        :disabled="isBusy"
        @click="emit('generate')"
      >
        {{ isBusy ? t('farmer.credit_report.generating') : t('farmer.credit.generate_title') }}
      </AppButton>
      <AppButton
        v-if="isReady"
        variant="harvest"
        :loading="isDownloading"
        @click="emit('download')"
      >
        {{ t('farmer.credit_report.download') }}
      </AppButton>
      <AppButton v-if="isReady" variant="outline" :disabled="isBusy" @click="emit('generate')">
        {{ t('farmer.credit_report.regenerate') }}
      </AppButton>
    </div>
  </div>
</template>
