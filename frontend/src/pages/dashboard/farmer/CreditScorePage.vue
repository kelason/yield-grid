<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import AppButton from '@/components/atoms/AppButton.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import CreditScoreGauge from '@/components/organisms/CreditScoreGauge.vue'
import ScoreBreakdownChart from '@/components/organisms/ScoreBreakdownChart.vue'
import ScoreHistoryTimeline from '@/components/organisms/ScoreHistoryTimeline.vue'
import ImprovementTips from '@/components/organisms/ImprovementTips.vue'
import ReportDownloadCard from '@/components/molecules/ReportDownloadCard.vue'
import { useConfirmModal } from '@/composables/useConfirmModal'
import { useCreditScoreStore } from '@/stores/creditScoreStore'
import { CREDIT_TIER_STYLES } from '@/constants/creditScoring'

const creditScoreStore = useCreditScoreStore()
const { isOpen, config, confirm, execute, cancel } = useConfirmModal()
const entered = ref(false)

const tierStyle = computed(
  () => CREDIT_TIER_STYLES[creditScoreStore.score?.tier] || CREDIT_TIER_STYLES.new,
)

const formattedExpiry = computed(() => {
  const expiresAt = creditScoreStore.score?.report_expires_at
  if (!expiresAt) {
    return ''
  }
  return new Date(expiresAt).toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
})

onMounted(async () => {
  await Promise.all([creditScoreStore.fetchScore(), creditScoreStore.fetchHistory()])
  requestAnimationFrame(() => {
    entered.value = true
  })
})

onUnmounted(() => {
  creditScoreStore.cancelPolling()
})

function handleGenerate() {
  confirm(
    {
      title: 'Generate PDF Report',
      message:
        'Generate a shareable Trust Score report for loan applications? You can create up to 3 reports per day.',
      type: 'primary',
      confirmText: 'Generate Report',
      cancelText: 'Not Now',
    },
    async () => {
      await creditScoreStore.generateReportAndPoll()
      await creditScoreStore.fetchScore()
    },
  )
}

function handleRetry() {
  creditScoreStore.fetchScore()
  creditScoreStore.fetchHistory()
}
</script>

<template>
  <div
    class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 transition-all duration-500 ease-out"
    :class="entered ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-1.5'"
  >
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="font-serif text-4xl sm:text-5xl font-bold tracking-tight text-stone-900">
          Farmer Trust Score
        </h1>
        <p class="text-base text-stone-600 font-light leading-relaxed mt-2">
          Your creditworthiness profile for Land Bank, ACPC, and rural bank loan applications.
        </p>
      </div>
    </div>

    <div
      v-if="creditScoreStore.isLoading && !creditScoreStore.score"
      class="mt-6 space-y-6"
      aria-live="polite"
    >
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="bg-white rounded-2xl shadow-soft border border-stone-200 p-6">
          <div class="w-44 h-44 mx-auto rounded-full bg-stone-200 animate-pulse" />
          <div class="w-32 h-6 mx-auto mt-4 rounded-full bg-stone-200 animate-pulse" />
        </div>
        <div class="bg-white rounded-2xl shadow-soft border border-stone-200 p-6 space-y-4">
          <div class="w-48 h-7 rounded-xl bg-stone-200 animate-pulse" />
          <div class="w-full h-2.5 rounded-full bg-stone-200 animate-pulse" />
          <div class="w-full h-2.5 rounded-full bg-stone-200 animate-pulse" />
          <div class="w-full h-2.5 rounded-full bg-stone-200 animate-pulse" />
        </div>
      </div>
    </div>

    <div
      v-else-if="creditScoreStore.errorMessage && !creditScoreStore.score"
      class="mt-6 bg-white rounded-2xl shadow-soft border border-stone-200 p-6 text-center"
    >
      <p class="text-base text-stone-600 font-light leading-relaxed">
        {{ creditScoreStore.errorMessage }}
      </p>
      <AppButton variant="primary" class="mt-4" @click="handleRetry">Try Again</AppButton>
    </div>

    <div v-else-if="creditScoreStore.score" class="mt-6 space-y-6">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <CreditScoreGauge
          :score="creditScoreStore.score.overall_score"
          :tier="creditScoreStore.score.tier"
          :tier-label="creditScoreStore.score.tier_label"
          :ring-color="tierStyle.ring"
        />
        <ScoreBreakdownChart :dimension-scores="creditScoreStore.score.dimension_scores || {}" />
      </div>

      <ReportDownloadCard
        :report-status="creditScoreStore.score.report_status"
        :expires-at="formattedExpiry"
        :is-generating="creditScoreStore.isGeneratingReport"
        :is-downloading="creditScoreStore.isDownloading"
        @generate="handleGenerate"
        @download="creditScoreStore.downloadReport()"
      />

      <p
        v-if="creditScoreStore.errorMessage"
        aria-live="polite"
        class="text-sm font-medium text-red-600"
      >
        {{ creditScoreStore.errorMessage }}
      </p>

      <ImprovementTips :tips="creditScoreStore.score.improvement_tips || []" />

      <ScoreHistoryTimeline :history="creditScoreStore.history" />
    </div>

    <ConfirmModal
      :is-open="isOpen"
      :title="config.title"
      :message="config.message"
      :type="config.type"
      :confirm-text="config.confirmText"
      :cancel-text="config.cancelText"
      @confirm="execute"
      @cancel="cancel"
    />
  </div>
</template>
