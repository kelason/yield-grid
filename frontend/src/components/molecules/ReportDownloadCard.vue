<script setup>
import { computed } from 'vue'
import AppButton from '@/components/atoms/AppButton.vue'
import { CREDIT_REPORT_STATUS } from '@/constants/creditScoring'

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
    return props.expiresAt ? `Ready — expires ${props.expiresAt}` : 'Ready to download'
  }
  if (isFailed.value) {
    return 'Generation failed — please try again'
  }
  if (isBusy.value) {
    return 'Generating your report…'
  }
  return 'Not generated yet'
})
</script>

<template>
  <div class="bg-white rounded-2xl shadow-soft border border-stone-200 p-6">
    <h3 class="font-serif text-2xl font-bold text-stone-900">PDF Report</h3>
    <p class="text-base text-stone-600 font-light leading-relaxed mt-2">
      Generate a shareable report for Land Bank, ACPC, or rural bank loan applications.
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
        {{ isBusy ? 'Generating…' : 'Generate PDF Report' }}
      </AppButton>
      <AppButton
        v-if="isReady"
        variant="harvest"
        :loading="isDownloading"
        @click="emit('download')"
      >
        Download Report
      </AppButton>
      <AppButton v-if="isReady" variant="outline" :disabled="isBusy" @click="emit('generate')">
        Regenerate
      </AppButton>
    </div>
  </div>
</template>
