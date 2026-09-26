<script setup>
import { onMounted, ref } from 'vue'
import { useMarketStore } from '@/stores/marketStore'
import { useNotificationStore } from '@/stores/notificationStore'
import { useChatEntry } from '@/composables/useChatEntry'
import PriceTag from '@/components/atoms/PriceTag.vue'
import AppCard from '@/components/atoms/AppCard.vue'
import StatusBadge from '@/components/atoms/StatusBadge.vue'

const marketStore = useMarketStore()
const notificationStore = useNotificationStore()
const { openChat } = useChatEntry()

const messageBuyer = (purchase) => {
  openChat(purchase.buyer?.id)
}

onMounted(() => {
  marketStore.fetchFarmerPurchases()
})

const isApproving = ref({})

const promptModal = ref({
  isOpen: false,
  purchaseId: null,
  type: null,
  amount: 0,
  title: '',
})

const openApproveModal = (purchase, type) => {
  const suggestedAmount =
    type === 'partial' ? purchase.total_contract_amount * 0.1 : purchase.total_contract_amount

  promptModal.value = {
    isOpen: true,
    purchaseId: purchase.id,
    type,
    amount: suggestedAmount,
    title: `Approve ${type === 'partial' ? 'Partial (10%)' : 'Full'} Payment`,
  }
}

const closeApproveModal = () => {
  promptModal.value.isOpen = false
}

const confirmApprove = async () => {
  const { purchaseId, type, amount } = promptModal.value
  const parsedAmount = parseFloat(amount)

  if (isNaN(parsedAmount) || parsedAmount <= 0) {
    notificationStore.error('Invalid amount entered.')
    return
  }

  closeApproveModal()
  isApproving.value[purchaseId] = true

  try {
    await marketStore.approveCashPayment(purchaseId, type, parsedAmount)
    notificationStore.success(`Successfully approved ${type} cash payment!`)
  } catch (err) {
    notificationStore.error(err.response?.data?.message || 'Failed to approve payment')
  } finally {
    isApproving.value[purchaseId] = false
  }
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Intl.DateTimeFormat('en-PH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(new Date(dateStr))
}

function getConfirmedPaid(purchase) {
  if (purchase.payment_method !== 'cash' && purchase.payment_status === 'completed') {
    return purchase.amount_paid || 0
  }
  return purchase.cash_amount_confirmed || 0
}
</script>

<template>
  <div class="py-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="font-serif text-3xl font-bold text-stone-900">Cash Payment Approvals</h2>
        <p class="mt-1 text-sm text-stone-600">
          Review and approve cash/off-site payments from buyers.
        </p>
      </div>
    </div>

    <div v-if="marketStore.loading.purchases" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-moss-600"></div>
    </div>

    <div
      v-else-if="marketStore.farmerPurchases.length === 0"
      class="text-center py-12 bg-white rounded-2xl shadow-soft border border-stone-200"
    >
      <p class="text-stone-500">No purchases found.</p>
    </div>

    <div v-else class="space-y-4">
      <AppCard
        v-for="purchase in marketStore.farmerPurchases"
        :key="purchase.id"
        class="group hover:border-moss-200 hover:shadow-organic transition-all duration-300 bg-white"
        body-class="p-5 sm:p-6"
      >
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
          <!-- Left: Crop & Buyer Info -->
          <div class="flex items-start gap-4 min-w-0">
            <div
              class="w-12 h-12 rounded-2xl bg-gradient-to-br from-moss-50 to-moss-100/50 flex items-center justify-center text-2xl flex-shrink-0 border border-moss-100 mt-1 lg:mt-0"
            >
              🌱
            </div>
            <div class="min-w-0">
              <h3 class="font-bold text-stone-900 text-[17px] leading-tight truncate font-serif">
                {{ purchase.contract?.title || 'Unknown Item' }}
              </h3>

              <div
                class="flex flex-wrap items-center gap-x-2 gap-y-1.5 text-[13px] text-stone-500 mt-1.5"
              >
                <span class="font-medium text-stone-700">{{
                  purchase.buyer?.name || 'Unknown Buyer'
                }}</span>
                <span class="text-stone-300 hidden sm:inline">•</span>
                <span
                  class="uppercase tracking-wider text-[11px] font-semibold text-stone-500 bg-stone-100 px-2 py-0.5 rounded-md"
                  >{{ purchase.payment_method || '—' }}</span
                >
                <span class="text-stone-300 hidden sm:inline">•</span>
                <span>{{ formatDate(purchase.created_at) }}</span>
              </div>

              <div class="flex flex-wrap items-center gap-2 mt-3">
                <StatusBadge :status="purchase.payment_status" size="sm" />
                <StatusBadge
                  v-if="purchase.cash_payment_status"
                  :status="purchase.cash_payment_status"
                  size="sm"
                />
              </div>
            </div>
          </div>

          <!-- Right: Amounts & Actions -->
          <div
            class="flex flex-col sm:flex-row items-start sm:items-center justify-between lg:justify-end gap-5 w-full lg:w-auto mt-2 lg:mt-0 pt-4 lg:pt-0 border-t lg:border-0 border-stone-100"
          >
            <!-- Amounts -->
            <div
              class="flex flex-row sm:flex-col items-center sm:items-end justify-between w-full sm:w-auto gap-4 sm:gap-1.5 bg-stone-50 sm:bg-transparent p-3 sm:p-0 rounded-xl sm:rounded-none border border-stone-100 sm:border-0"
            >
              <div class="text-left sm:text-right">
                <p
                  class="text-[11px] font-medium text-stone-400 uppercase tracking-wider mb-0.5 sm:mb-0"
                >
                  Total Due
                </p>
                <PriceTag
                  :amount="purchase.total_contract_amount"
                  :currency="purchase.currency"
                  class="text-[15px] font-extrabold text-stone-900 leading-none"
                />
              </div>

              <div class="hidden sm:block w-8 border-t border-stone-200 my-0.5"></div>

              <div class="text-right">
                <p
                  class="text-[11px] font-medium text-stone-400 uppercase tracking-wider mb-0.5 sm:mb-0"
                >
                  Confirmed Paid
                </p>
                <PriceTag
                  :amount="getConfirmedPaid(purchase)"
                  :currency="purchase.currency"
                  class="text-[15px] font-extrabold text-moss-600 leading-none"
                />
              </div>
            </div>

            <!-- Message buyer (transaction partner) -->
            <div v-if="purchase.buyer?.id" class="flex w-full sm:w-auto mt-2 sm:mt-0 flex-shrink-0">
              <button
                @click="messageBuyer(purchase)"
                class="px-4 py-2.5 text-xs font-semibold rounded-xl text-moss-700 bg-moss-50 border border-moss-200 hover:bg-moss-100 transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-sm w-full sm:w-auto text-center"
              >
                Message buyer
              </button>
            </div>

            <!-- Actions -->
            <div
              class="flex flex-wrap sm:flex-col gap-2.5 w-full sm:w-auto mt-2 sm:mt-0 flex-shrink-0"
              v-if="
                purchase.payment_status === 'pending' ||
                purchase.cash_payment_status === 'pending' ||
                purchase.cash_payment_status === 'partially_paid'
              "
            >
              <button
                @click="openApproveModal(purchase, 'partial')"
                :disabled="isApproving[purchase.id]"
                v-if="purchase.cash_payment_status !== 'partially_paid'"
                class="px-4 py-2.5 text-xs font-semibold rounded-xl text-moss-700 bg-moss-50 border border-moss-200 hover:bg-moss-100 transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-sm w-full sm:w-auto text-center"
              >
                Approve 10%
              </button>
              <button
                @click="openApproveModal(purchase, 'full')"
                :disabled="isApproving[purchase.id]"
                class="px-4 py-2.5 text-xs font-semibold rounded-xl text-white bg-gradient-to-br from-moss-500 to-moss-600 hover:from-moss-600 hover:to-moss-700 transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-soft hover:shadow-organic focus-visible:outline-moss-600 w-full sm:w-auto text-center"
              >
                Approve Full
              </button>
            </div>

            <!-- Visual spacer when no actions -->
            <div v-else class="hidden lg:block w-[110px]"></div>
          </div>
        </div>
      </AppCard>
    </div>

    <!-- Amount Prompt Modal -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 scale-95 translate-y-2"
        enter-to-class="opacity-100 scale-100 translate-y-0"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 scale-100 translate-y-0"
        leave-to-class="opacity-0 scale-95 translate-y-2"
      >
        <div
          v-if="promptModal.isOpen"
          class="fixed inset-0 z-[110] flex items-center justify-center p-4 sm:p-6"
        >
          <div
            class="fixed inset-0 bg-soil-900/50 backdrop-blur-sm transition-opacity"
            @click="closeApproveModal"
            aria-hidden="true"
          ></div>

          <div
            class="relative w-full max-w-md bg-white rounded-3xl shadow-organic overflow-hidden transform transition-all border border-stone-100"
          >
            <div class="h-1.5 w-full bg-gradient-to-r from-moss-500 to-moss-600"></div>

            <div class="p-7">
              <div class="flex items-center justify-center mb-4">
                <div
                  class="w-14 h-14 rounded-full flex items-center justify-center text-2xl bg-moss-100"
                >
                  <span>💵</span>
                </div>
              </div>

              <h3 class="text-xl font-bold text-stone-900 mb-2 font-serif text-center">
                {{ promptModal.title }}
              </h3>
              <p class="text-sm text-stone-500 mb-5 leading-relaxed text-center">
                Please confirm the exact amount of cash you have received from the buyer.
              </p>

              <div class="mb-6">
                <label class="block text-sm font-medium text-stone-700 mb-1"
                  >Amount Received (₱)</label
                >
                <input
                  type="number"
                  v-model="promptModal.amount"
                  class="w-full px-4 py-3 rounded-xl border border-stone-200 focus:ring-2 focus:ring-moss-500 focus:border-moss-500 text-stone-900 font-medium text-lg transition-shadow bg-stone-50 focus:bg-white disabled:opacity-75 disabled:cursor-not-allowed disabled:bg-stone-100 disabled:text-stone-500"
                  min="1"
                  step="0.01"
                  :disabled="promptModal.type === 'full'"
                  @keyup.enter="promptModal.type !== 'full' ? confirmApprove() : null"
                />
                <p v-if="promptModal.type === 'full'" class="mt-2 text-xs text-stone-500">
                  The amount is locked for full payments to ensure the contract total is met
                  exactly.
                </p>
              </div>

              <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <button
                  type="button"
                  @click="closeApproveModal"
                  class="w-full sm:w-auto rounded-xl bg-stone-100 px-5 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 transition-all duration-200 hover:scale-[1.02] active:scale-[0.99]"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  @click="confirmApprove"
                  class="w-full sm:w-auto rounded-xl px-5 py-2.5 text-sm font-semibold text-white bg-gradient-to-br from-moss-500 to-moss-600 hover:from-moss-600 hover:to-moss-700 shadow-soft hover:shadow-organic focus-visible:outline-moss-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 transition-all duration-200 hover:scale-[1.02] active:scale-[0.99]"
                >
                  Confirm Payment
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>
