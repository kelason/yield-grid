<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AppAlert from '@/components/atoms/AppAlert.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import ClaimForm from '@/components/organisms/ClaimForm.vue'
import ClaimTracker from '@/components/organisms/ClaimTracker.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import EnrollmentGuide from '@/components/organisms/EnrollmentGuide.vue'
import EnrollmentTracker from '@/components/organisms/EnrollmentTracker.vue'
import LanguageToggle from '@/components/molecules/LanguageToggle.vue'
import OfficeDirectory from '@/components/organisms/OfficeDirectory.vue'
import ReminderCenter from '@/components/organisms/ReminderCenter.vue'
import RsbsaPanel from '@/components/organisms/RsbsaPanel.vue'
import { useConfirmModal } from '@/composables/useConfirmModal'
import { useInsurancePageActions } from '@/composables/useInsurancePageActions'
import { useInsuranceStore } from '@/stores/insuranceStore'
import { useFarmingStore } from '@/stores/farming'

const { t } = useI18n()
const insuranceStore = useInsuranceStore()
const farmingStore = useFarmingStore()
const { isOpen, isExecuting, config, confirm, execute, cancel } = useConfirmModal()

const entered = ref(false)
const selectedEnrollmentId = ref(null)
const filingClaim = ref(false)

const selectedEnrollment = computed(
  () => insuranceStore.enrollments.find((item) => item.id === selectedEnrollmentId.value) || null,
)
const selectedClaims = computed(
  () => insuranceStore.claimsByEnrollment[selectedEnrollmentId.value] || [],
)

const {
  handleSaveProfile,
  handleCreateEnrollment,
  handleRequestPack,
  handleAdvanceEnrollment,
  handleRecordPolicyDetails,
  handleFileClaim,
  handleAdvanceClaim,
  handleRecordPayout,
} = useInsurancePageActions({
  store: insuranceStore,
  confirm,
  selectedEnrollmentId,
  closeClaimForm: () => {
    filingClaim.value = false
  },
})

const selectEnrollment = (id) => {
  selectedEnrollmentId.value = id
  filingClaim.value = false
  insuranceStore.fetchClaims(id)
}

watch(
  () => insuranceStore.enrollments,
  (enrollments) => {
    if (selectedEnrollmentId.value === null && enrollments.length > 0) {
      selectEnrollment(enrollments[0].id)
    }
  },
)

onMounted(async () => {
  await Promise.all([insuranceStore.fetchDashboard(), farmingStore.fetchAllPlots()])
  requestAnimationFrame(() => {
    entered.value = true
  })
})
</script>

<template>
  <div
    class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 transition-all duration-500 ease-out"
    :class="entered ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-1.5'"
  >
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="font-serif text-4xl sm:text-5xl font-bold tracking-tight text-stone-900">
          {{ t('insurance.page.title') }}
        </h1>
        <p class="text-base text-stone-600 font-light leading-relaxed mt-2">
          {{ t('insurance.page.subtitle') }}
        </p>
      </div>
      <LanguageToggle />
    </div>

    <div v-if="insuranceStore.errorMessage" class="mt-6">
      <AppAlert type="error">{{ insuranceStore.errorMessage }}</AppAlert>
      <AppButton size="sm" variant="outline" class="mt-3" @click="insuranceStore.fetchDashboard()">
        {{ t('insurance.common.retry') }}
      </AppButton>
    </div>

    <div v-if="insuranceStore.isLoading" class="mt-6 space-y-6" aria-live="polite">
      <div class="bg-stone-200 rounded-2xl h-40 animate-pulse" />
      <div class="bg-stone-200 rounded-2xl h-64 animate-pulse" />
    </div>

    <div v-else class="mt-6 space-y-6">
      <RsbsaPanel
        :profile="insuranceStore.profile"
        :is-saving="insuranceStore.isSaving"
        @save-profile="handleSaveProfile"
      />

      <EnrollmentGuide
        :profile="insuranceStore.profile"
        :enrollments="insuranceStore.enrollments"
        :plots="farmingStore.allPlots"
        @create-enrollment="handleCreateEnrollment"
      />

      <EnrollmentTracker
        :enrollments="insuranceStore.enrollments"
        :is-pack-busy="insuranceStore.isGeneratingPack || insuranceStore.isDownloading"
        @request-pack="handleRequestPack"
        @advance-enrollment="handleAdvanceEnrollment"
        @record-policy-details="handleRecordPolicyDetails"
      />

      <div v-if="selectedEnrollment">
        <ClaimTracker
          :claims="selectedClaims"
          :enrollment-id="selectedEnrollment.id"
          @file-claim="filingClaim = true"
          @advance-claim="handleAdvanceClaim"
          @record-payout="handleRecordPayout"
        />

        <ClaimForm
          v-if="filingClaim"
          class="mt-6"
          :is-saving="insuranceStore.isSaving"
          @submit-claim="handleFileClaim"
          @cancel="filingClaim = false"
        />
      </div>

      <ReminderCenter :reminders="insuranceStore.reminders" />
      <OfficeDirectory :offices="insuranceStore.offices" />
    </div>

    <ConfirmModal
      :is-open="isOpen"
      :title="config.title"
      :message="config.message"
      :type="config.type"
      :confirm-text="config.confirmText"
      :cancel-text="config.cancelText"
      :loading="isExecuting"
      @confirm="execute"
      @cancel="cancel"
    />
  </div>
</template>
