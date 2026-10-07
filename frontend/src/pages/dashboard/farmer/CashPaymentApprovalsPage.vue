<script setup>
import { onMounted, ref, computed } from 'vue'
import { useMarketStore } from '@/stores/marketStore'
import { useNotificationStore } from '@/stores/notificationStore'
import { useChatEntry } from '@/composables/useChatEntry'
import { PAYMENT_CONSTANTS, CASH_PAYMENT_TYPE, CASH_PAYMENT_LIMITS } from '@/constants/payment'
import SkeletonCard from '@/components/atoms/SkeletonCard.vue'
import AppModal from '@/components/molecules/AppModal.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import PageHeader from '@/components/molecules/PageHeader.vue'
import FarmerPurchaseCard from '@/components/molecules/FarmerPurchaseCard.vue'
import CashPaymentReviewPanel from '@/components/organisms/CashPaymentReviewPanel.vue'
import { usePendingConfirmation } from '@/composables/useConfirmModal'

const PURCHASE_SKELETON_COUNT = 4
const CASH_AMOUNT_DECIMAL_PLACES = 2

const marketStore = useMarketStore()
const notificationStore = useNotificationStore()
const { openChat } = useChatEntry()

const messageBuyer = (purchase) => {
  pendingConfirm.value = { action: 'message', recipientId: purchase.buyer?.id }
}

onMounted(() => {
  marketStore.fetchFarmerPurchases()
})

const pendingConfirm = ref(null)
const approvalError = ref('')
const { isExecuting, execute, cancel } = usePendingConfirmation(pendingConfirm)
const confirmConfig = computed(() =>
  pendingConfirm.value?.action === 'message'
    ? {
        title: 'Open conversation?',
        message: 'Open a conversation with this buyer?',
        confirmText: 'Open conversation',
      }
    : {
        title: 'Confirm cash payment?',
        message: `Confirm receipt of ₱${Number(pendingConfirm.value?.amount || 0).toLocaleString('en-PH')}?`,
        confirmText: 'Confirm payment',
      },
)

const isApproving = ref({})

const promptModal = ref({
  isOpen: false,
  purchaseId: null,
  type: null,
  amount: 0,
  title: '',
})

const openApproveModal = (purchase, type) => {
  approvalError.value = ''
  const suggestedAmount =
    type === CASH_PAYMENT_TYPE.PARTIAL
      ? Math.min(
          CASH_PAYMENT_LIMITS.MAX,
          Math.max(
            CASH_PAYMENT_LIMITS.MIN,
            Number(
              (purchase.total_contract_amount * PAYMENT_CONSTANTS.DOWNPAYMENT_PERCENTAGE).toFixed(
                CASH_AMOUNT_DECIMAL_PLACES,
              ),
            ),
          ),
        )
      : purchase.total_contract_amount

  promptModal.value = {
    isOpen: true,
    purchaseId: purchase.id,
    type,
    amount: suggestedAmount,
    title: `Approve ${type === CASH_PAYMENT_TYPE.PARTIAL ? 'Partial (10%)' : 'Full'} Payment`,
  }
}

const closeApproveModal = () => {
  promptModal.value.isOpen = false
}

const reviewApproval = () => {
  const amount = Number(promptModal.value.amount)
  if (
    !Number.isFinite(amount) ||
    amount < CASH_PAYMENT_LIMITS.MIN ||
    amount > CASH_PAYMENT_LIMITS.MAX
  ) {
    approvalError.value = 'Enter a received amount between ₱0.01 and ₱99,999,999.'
    return
  }
  pendingConfirm.value = { ...promptModal.value, action: 'approve', amount }
}

const performApproval = async ({ action, recipientId, purchaseId, type, amount }) => {
  if (action === 'message') return openChat(recipientId)
  isApproving.value[purchaseId] = true
  approvalError.value = ''
  try {
    await marketStore.approveCashPayment(purchaseId, type, amount)
    notificationStore.success(`Successfully approved ${type} cash payment!`)
    closeApproveModal()
  } catch (err) {
    approvalError.value = err.response?.data?.message || 'Failed to approve payment'
    notificationStore.error(approvalError.value)
  } finally {
    isApproving.value[purchaseId] = false
  }
}
const confirmApprove = () => execute(performApproval)
</script>

<template>
  <div class="py-6 space-y-6">
    <PageHeader
      title="Cash Payment Approvals"
      description="Review and approve cash/off-site payments from buyers."
    />

    <div
      v-if="marketStore.loading.purchases"
      class="space-y-4"
      role="status"
      aria-label="Loading purchases"
    >
      <SkeletonCard v-for="n in PURCHASE_SKELETON_COUNT" :key="n" withAvatar withAction />
      <span class="sr-only">Loading purchases...</span>
    </div>

    <div
      v-else-if="marketStore.farmerPurchases.length === 0"
      class="text-center py-12 bg-white rounded-2xl shadow-soft border border-stone-200"
    >
      <p class="text-stone-500">No purchases found.</p>
    </div>

    <div v-else class="space-y-4">
      <FarmerPurchaseCard
        v-for="purchase in marketStore.farmerPurchases"
        :key="purchase.id"
        :purchase="purchase"
        :loading="isApproving[purchase.id]"
        @message="messageBuyer"
        @approve="openApproveModal"
      />
    </div>

    <AppModal
      :is-open="promptModal.isOpen"
      :title="promptModal.title"
      :busy="isExecuting"
      @close="closeApproveModal"
    >
      <CashPaymentReviewPanel
        v-model:amount="promptModal.amount"
        :payment-type="promptModal.type"
        :error="approvalError"
        :loading="isExecuting"
        @submit="reviewApproval"
        @cancel="closeApproveModal"
      />
    </AppModal>
    <ConfirmModal
      :is-open="pendingConfirm !== null"
      :title="confirmConfig.title"
      :message="confirmConfig.message"
      :confirm-text="confirmConfig.confirmText"
      :loading="isExecuting"
      @confirm="confirmApprove"
      @cancel="cancel"
    />
  </div>
</template>
