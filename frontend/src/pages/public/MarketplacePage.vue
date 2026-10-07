<script setup>
import { computed, onMounted, ref } from 'vue'
import { PAYMENT_OPTION } from '@/constants/payment'
import { useMarketStore } from '@/stores/marketStore'
import { useAddressStore } from '@/stores/addressStore'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notificationStore'
import ContractFilter from '@/components/molecules/ContractFilter.vue'
import ContractGrid from '@/components/organisms/ContractGrid.vue'
import PaginationControls from '@/components/molecules/PaginationControls.vue'
import CheckoutSummary from '@/components/organisms/CheckoutSummary.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import { usePayment } from '@/composables/usePayment'
import AppModal from '@/components/molecules/AppModal.vue'
import LoadingState from '@/components/molecules/LoadingState.vue'
import PageHeader from '@/components/molecules/PageHeader.vue'
import AppAlert from '@/components/atoms/AppAlert.vue'
import { usePendingConfirmation } from '@/composables/useConfirmModal'

const marketStore = useMarketStore()
const addressStore = useAddressStore()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()
const { startCheckout, loading: checkoutLoading, error: checkoutError } = usePayment()

const showCheckoutPanel = ref(false)
const pendingConfirm = ref(null)
const { isExecuting, execute, cancel } = usePendingConfirmation(pendingConfirm)

const confirmConfig = computed(() => {
  if (!pendingConfirm.value) return null
  const cash = pendingConfirm.value.paymentOption === PAYMENT_OPTION.CASH
  return {
    title: cash ? 'Request cash payment?' : 'Proceed to payment?',
    message: cash
      ? `Request to pay ${pendingConfirm.value.quantityKg} kg in cash? The farmer must approve before the order is confirmed.`
      : `Pay for ${pendingConfirm.value.quantityKg} kg now via PayMongo? You will be redirected to complete payment.`,
    confirmText: cash ? 'Request cash payment' : 'Pay now',
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
    checkoutError.value = 'Please verify your email address to purchase contracts.'
    notificationStore.warning(checkoutError.value)
    return
  }
  pendingConfirm.value = checkoutData
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
      notificationStore.success('Cash payment request sent! Waiting for farmer approval.')
      showCheckoutPanel.value = false
      marketStore.fetchMarketContracts(marketStore.pagination.currentPage)
    }
  } catch (err) {
    notificationStore.error(
      err.response?.data?.message || err.message || 'Failed to initialize checkout session',
    )
  }
}
const handleConfirmCheckout = () => execute(performCheckout)
</script>

<template>
  <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 space-y-6">
    <PageHeader
      title="The Harvest Exchange"
      description="Secure your supply directly from Filipino farmers at a fixed price."
    />

    <!-- Filter Bar -->
    <ContractFilter v-model="marketStore.filters" @search="handleSearch" />

    <!-- Contract Grid -->
    <ContractGrid
      :contracts="marketStore.contracts"
      :loading="marketStore.loading.contracts"
      @view-contract="handleViewContract"
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
      title="Contract Details"
      size="lg"
      :is-open="showCheckoutPanel"
      :busy="isExecuting"
      @close="showCheckoutPanel = false"
    >
      <AppAlert v-if="checkoutError" type="error" class="mb-4">{{ checkoutError }}</AppAlert>
      <LoadingState v-if="marketStore.loading.details" label="Loading contract details" />
      <CheckoutSummary
        v-else-if="marketStore.activeContract"
        :contract="marketStore.activeContract"
        :loading="checkoutLoading || isExecuting"
        @confirm="askCheckoutConfirm"
        @cancel="showCheckoutPanel = false"
      />
    </AppModal>

    <ConfirmModal
      v-if="confirmConfig"
      :is-open="pendingConfirm !== null"
      :title="confirmConfig.title"
      :message="confirmConfig.message"
      :confirm-text="confirmConfig.confirmText"
      :type="confirmConfig.type"
      @confirm="handleConfirmCheckout"
      :loading="isExecuting"
      @cancel="cancel"
    />
  </div>
</template>
