<script setup>
import { onMounted, ref } from 'vue'
import { useMarketStore } from '@/stores/marketStore'
import ContractFilter from '@/components/molecules/ContractFilter.vue'
import ContractGrid from '@/components/organisms/ContractGrid.vue'
import CheckoutSummary from '@/components/organisms/CheckoutSummary.vue'
import { usePayment } from '@/composables/usePayment'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const marketStore = useMarketStore()
const { startCheckout, loading: checkoutLoading } = usePayment()

const showCheckoutPanel = ref(false)

onMounted(() => {
  marketStore.fetchMarketContracts()
})

const handleSearch = () => {
  marketStore.fetchMarketContracts(1)
}

const loadMore = () => {
  if (marketStore.pagination.currentPage < marketStore.pagination.lastPage) {
    marketStore.fetchMarketContracts(marketStore.pagination.currentPage + 1)
  }
}

const handleViewContract = async (contract) => {
  await marketStore.fetchContractDetail(contract.id)
  showCheckoutPanel.value = true
}

const handleConfirmCheckout = async () => {
  if (marketStore.activeContract) {
    await startCheckout(marketStore.activeContract.id)
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="rounded-2xl bg-gradient-to-r from-farm-600 to-farm-800 p-6 text-white shadow-lg">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
          <h1 class="text-2xl font-bold">The Harvest Exchange</h1>
          <p class="text-farm-200 text-sm mt-1">
            Secure your supply directly from Filipino farmers at a fixed price.
          </p>
        </div>
        <span
          class="inline-flex items-center gap-1.5 bg-white/10 rounded-lg px-3 py-1.5 text-sm font-medium self-start sm:self-auto"
        >
          🌾 Forward Contracts
        </span>
      </div>
    </div>

    <!-- Filter Bar -->
    <ContractFilter v-model="marketStore.filters" @search="handleSearch" />

    <!-- Contract Grid -->
    <ContractGrid
      :contracts="marketStore.contracts"
      :loading="marketStore.loading.contracts"
      :has-more="marketStore.pagination.currentPage < marketStore.pagination.lastPage"
      @load-more="loadMore"
      @view-contract="handleViewContract"
    />

    <!-- Centered Modal for Checkout -->
    <Teleport to="body">
      <div
        v-if="showCheckoutPanel"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
      >
        <!-- Backdrop -->
        <div
          class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
          @click="showCheckoutPanel = false"
        ></div>

        <!-- Modal Panel -->
        <div
          class="relative w-full max-w-3xl max-h-[90vh] flex flex-col bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all"
        >
          <!-- Header -->
          <div
            class="flex items-center justify-between px-6 py-4 bg-white border-b border-gray-100 z-10"
          >
            <h2 class="text-xl font-semibold text-gray-900">Contract Details</h2>
            <button
              type="button"
              @click="showCheckoutPanel = false"
              class="rounded-full p-2 bg-gray-50 text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-farm-500 transition-colors"
            >
              <span class="sr-only">Close panel</span>
              <XMarkIcon class="h-5 w-5" aria-hidden="true" />
            </button>
          </div>

          <!-- Content -->
          <div class="flex-1 overflow-y-auto p-0 bg-gray-50 relative">
            <div v-if="marketStore.loading.details" class="flex justify-center items-center h-64">
              <svg
                class="animate-spin h-8 w-8 text-farm-500"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                ></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
              </svg>
            </div>
            <div v-else-if="marketStore.activeContract" class="p-6">
              <CheckoutSummary
                :contract="marketStore.activeContract"
                :loading="checkoutLoading"
                @confirm="handleConfirmCheckout"
                @cancel="showCheckoutPanel = false"
              />
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
