<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { PAYMENT_OPTION } from '@/constants/payment'
import { useMarketStore } from '@/stores/marketStore'
import { useAddressStore } from '@/stores/addressStore'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notificationStore'
import ContractFilter from '@/components/molecules/ContractFilter.vue'
import ContractGrid from '@/components/organisms/ContractGrid.vue'
import PaginationControls from '@/components/molecules/PaginationControls.vue'
import CheckoutSummary from '@/components/organisms/CheckoutSummary.vue'
import ContentReportForm from '@/components/organisms/ContentReportForm.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import { usePayment } from '@/composables/usePayment'
import { useContentReport } from '@/composables/useContentReport'
import AppModal from '@/components/molecules/AppModal.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import LoadingState from '@/components/molecules/LoadingState.vue'
import PageHeader from '@/components/molecules/PageHeader.vue'
import AppAlert from '@/components/atoms/AppAlert.vue'
import { usePendingConfirmation } from '@/composables/useConfirmModal'

const marketStore = useMarketStore()
const addressStore = useAddressStore()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()
const router = useRouter()
const { t } = useI18n()
const { startCheckout, loading: checkoutLoading, error: checkoutError } = usePayment()

const showCheckoutPanel = ref(false)
const pendingConfirm = ref(null)
const { isExecuting, execute, cancel } = usePendingConfirmation(pendingConfirm)
const showReportModal = ref(false)
const {
  target: reportTarget,
  reason: reportReason,
  description: reportDescription,
  busy: reportBusy,
  error: reportError,
  fieldError: reportFieldError,
  openReport,
  closeReport,
  validate: validateReport,
  submit: submitContentReport,
} = useContentReport()
const reportAccess = computed(() => {
  if (!authStore.isAuthenticated) return 'signin'
  if (!authStore.isEmailVerified) return 'verify'
  return 'ok'
})

const confirmConfig = computed(() => {
  if (!pendingConfirm.value) return null
  if (pendingConfirm.value.kind === 'report') {
    return {
      title: t('market.browse.report_confirm_title'),
      message: t('market.browse.report_confirm_msg'),
      confirmText: t('market.browse.report_confirm_ok'),
      type: 'primary',
    }
  }
  const cash = pendingConfirm.value.paymentOption === PAYMENT_OPTION.CASH
  const qty = pendingConfirm.value.quantityKg
  return {
    title: t(cash ? 'market.browse.pay_cash_title' : 'market.browse.pay_online_title'),
    message: t(cash ? 'market.browse.pay_cash_msg' : 'market.browse.pay_online_msg', { qty }),
    confirmText: t(cash ? 'market.browse.pay_cash_ok' : 'market.browse.pay_online_ok'),
    type: 'primary',
  }
})

onMounted(async () => {
  if (authStore.isAuthenticated) {
    try {
      await addressStore.fetchAddresses()
      const fallback = addressStore.defaultAddress
      if (fallback?.latitude != null && fallback?.longitude != null) {
        marketStore.viewerLocation = {
          lat: parseFloat(fallback.latitude),
          lng: parseFloat(fallback.longitude),
        }
      }
    } catch {
      // Browsing still works without a saved address.
    }
  }
  marketStore.fetchMarketContracts()
})

const handleSearch = () => {
  marketStore.fetchMarketContracts(1)
}

const handlePageChange = (page) => {
  marketStore.fetchMarketContracts(page)
}

const handleViewContract = async (contract) => {
  checkoutError.value = null
  await marketStore.fetchContractDetail(contract.id, contract.type)
  showCheckoutPanel.value = true
}

const askCheckoutConfirm = (checkoutData) => {
  if (authStore.isAuthenticated && !authStore.isEmailVerified) {
    checkoutError.value = t('market.browse.verify_purchase')
    notificationStore.addNotification({
      type: 'warning',
      messageKey: 'market.browse.verify_purchase',
    })
    return
  }
  pendingConfirm.value = { kind: 'checkout', ...checkoutData }
}

function openReportModal(payload) {
  openReport(payload)
  showReportModal.value = true
}

function closeReportModal() {
  if (reportBusy.value || isExecuting.value) return
  showReportModal.value = false
  closeReport()
}

function requestReportSubmit() {
  if (!reportTarget.value || reportBusy.value) return
  if (!validateReport()) return
  pendingConfirm.value = { kind: 'report' }
}

async function submitReport() {
  try {
    await submitContentReport()
    showReportModal.value = false
  } catch {
    // The inline form error and draft stay visible; the modal remains open.
  }
}

function goLogin() {
  closeReportModal()
  router.push({ name: 'login' })
}

const performCheckout = async (checkoutData) => {
  try {
    await startCheckout(
      checkoutData.contractId,
      checkoutData.type,
      checkoutData.quantityKg,
      checkoutData.paymentOption,
    )
    if (checkoutData.paymentOption === PAYMENT_OPTION.CASH) {
      notificationStore.addNotification({ type: 'success', messageKey: 'buyer.demands.cash_sent' })
      showCheckoutPanel.value = false
      marketStore.fetchMarketContracts(marketStore.pagination.currentPage)
    }
  } catch (err) {
    const serverMessage = err.response?.data?.message || err.message
    if (serverMessage) {
      notificationStore.error(serverMessage)
    } else {
      notificationStore.addNotification({
        type: 'error',
        messageKey: 'market.browse.checkout_failed',
      })
    }
  }
}
const handlePendingConfirm = () =>
  execute((pending) => (pending.kind === 'report' ? submitReport() : performCheckout(pending)))
</script>

<template>
  <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 space-y-6">
    <PageHeader :title="t('market.browse.title')" :description="t('market.browse.description')" />

    <!-- Filter Bar -->
    <ContractFilter v-model="marketStore.filters" @search="handleSearch" />

    <!-- Contract Grid -->
    <ContractGrid
      :contracts="marketStore.contracts"
      :loading="marketStore.loading.contracts"
      @view-contract="handleViewContract"
      @report="openReportModal"
    />

    <PaginationControls
      v-if="!marketStore.loading.contracts"
      :current-page="marketStore.pagination.currentPage"
      :last-page="marketStore.pagination.lastPage"
      :total="marketStore.pagination.total"
      @page-change="handlePageChange"
      class="mt-6"
    />

    <AppModal
      :title="t('market.browse.details_title')"
      size="lg"
      :is-open="showCheckoutPanel"
      :busy="isExecuting"
      @close="showCheckoutPanel = false"
    >
      <AppAlert v-if="checkoutError" type="error" class="mb-4">{{ checkoutError }}</AppAlert>
      <LoadingState
        v-if="marketStore.loading.details"
        :label="t('market.browse.loading_details')"
      />
      <CheckoutSummary
        v-else-if="marketStore.activeContract"
        :contract="marketStore.activeContract"
        :loading="checkoutLoading || isExecuting"
        @confirm="askCheckoutConfirm"
        @cancel="showCheckoutPanel = false"
      />
    </AppModal>

    <AppModal
      :title="t('market.browse.report_title')"
      :is-open="showReportModal"
      :busy="reportBusy || isExecuting"
      @close="closeReportModal"
    >
      <ContentReportForm
        v-if="reportAccess === 'ok' && reportTarget"
        :target="reportTarget"
        :reason="reportReason"
        :description="reportDescription"
        :busy="reportBusy || isExecuting"
        :error="reportError"
        :field-error="reportFieldError"
        @update:reason="reportReason = $event"
        @update:description="reportDescription = $event"
        @submit="requestReportSubmit"
        @cancel="closeReportModal"
      />
      <div v-else-if="reportAccess === 'signin'" class="space-y-4">
        <p class="text-base leading-relaxed text-stone-600">
          {{ t('market.browse.signin_msg') }}
        </p>
        <div class="flex justify-end gap-3">
          <AppButton variant="secondary" @click="closeReportModal">{{
            t('shell.cancel')
          }}</AppButton>
          <AppButton variant="primary" @click="goLogin">{{ t('market.browse.signin') }}</AppButton>
        </div>
      </div>
      <div v-else class="space-y-4">
        <p class="text-base leading-relaxed text-stone-600">
          {{ t('market.browse.verify_msg') }}
        </p>
        <div class="flex justify-end">
          <AppButton variant="secondary" @click="closeReportModal">{{
            t('shell.close')
          }}</AppButton>
        </div>
      </div>
    </AppModal>

    <ConfirmModal
      v-if="confirmConfig"
      :is-open="pendingConfirm !== null"
      :title="confirmConfig.title"
      :message="confirmConfig.message"
      :confirm-text="confirmConfig.confirmText"
      :type="confirmConfig.type"
      @confirm="handlePendingConfirm"
      :loading="isExecuting"
      @cancel="cancel"
    />
  </div>
</template>
