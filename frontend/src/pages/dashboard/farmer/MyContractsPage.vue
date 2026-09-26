<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { useMarketStore } from '@/stores/marketStore'
import { useAuthStore } from '@/stores/auth'
import FarmerContractsList from '@/components/organisms/FarmerContractsList.vue'
import PaginationControls from '@/components/molecules/PaginationControls.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import { BanknotesIcon, DocumentTextIcon, ChartBarIcon, ClockIcon } from '@heroicons/vue/24/outline'
import { useConfirmModal } from '@/composables/useConfirmModal'

const marketStore = useMarketStore()
const authStore = useAuthStore()
const { isOpen, config, confirm, execute, cancel } = useConfirmModal()

onMounted(() => {
  marketStore.fetchFarmerContracts()
  marketStore.fetchFarmerContractsStats()

  // Listen for contract purchases
  if (window.Echo) {
    window.Echo.private(`user.${authStore.user?.id || ''}`).listen('.ContractPurchased', (e) => {
      marketStore.handleContractPurchased(e)
    })
  }
})

onUnmounted(() => {
  if (window.Echo) {
    window.Echo.leave(`user.${authStore.user?.id || ''}`)
  }
})

const handleCancel = (contract) => {
  const itemTypeLabel = contract.type === 'listing' ? 'Manual Listing' : 'Forward Contract'
  confirm(
    {
      title: `Cancel ${itemTypeLabel}`,
      message: `Are you sure you want to cancel the listing for '${contract.title}'? This action cannot be undone.`,
      type: 'danger',
      confirmText: 'Cancel Listing',
      cancelText: 'Keep Listing',
    },
    async () => {
      await marketStore.cancelContract(contract.id, contract.type)
      await marketStore.fetchFarmerContractsStats()
    },
  )
}

const currentTab = ref('all')

const handleTabChange = (tabId) => {
  currentTab.value = tabId
  const status = tabId === 'all' ? null : tabId
  marketStore.fetchFarmerContracts(1, status)
}

const handlePageChange = (page) => {
  const status = currentTab.value === 'all' ? null : currentTab.value
  marketStore.fetchFarmerContracts(page, status)
}
</script>

<template>
  <div class="py-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          My Forward Contracts
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          Manage your published crop listings and track sales.
        </p>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <div
        class="bg-gradient-to-br from-white to-gray-50 overflow-hidden shadow rounded-lg border border-gray-100"
      >
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
              <DocumentTextIcon class="h-6 w-6 text-blue-600" aria-hidden="true" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Total Listed</dt>
                <dd>
                  <div class="text-2xl font-semibold text-gray-900">
                    {{ marketStore.farmerStats.total_listed }}
                  </div>
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div
        class="bg-gradient-to-br from-white to-gray-50 overflow-hidden shadow rounded-lg border border-gray-100"
      >
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0 bg-yellow-100 rounded-md p-3">
              <ClockIcon class="h-6 w-6 text-yellow-600" aria-hidden="true" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Total Reserved</dt>
                <dd>
                  <div class="text-2xl font-semibold text-gray-900">
                    {{ marketStore.farmerStats.total_reserved }}
                  </div>
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div
        class="bg-gradient-to-br from-white to-gray-50 overflow-hidden shadow rounded-lg border border-gray-100"
      >
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
              <ChartBarIcon class="h-6 w-6 text-green-600" aria-hidden="true" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Total Sold</dt>
                <dd>
                  <div class="text-2xl font-semibold text-gray-900">
                    {{ marketStore.farmerStats.total_sold }}
                  </div>
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div
        class="bg-gradient-to-r from-green-500 to-green-600 overflow-hidden shadow rounded-lg text-white"
      >
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0 bg-red-100 rounded-md p-3">
              <BanknotesIcon class="h-6 w-6 text-red-600" aria-hidden="true" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Total Revenue</dt>
                <dd>
                  <div class="text-2xl font-semibold text-gray-900">
                    ₱{{ parseFloat(marketStore.farmerStats.total_revenue || 0).toLocaleString() }}
                  </div>
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Contract List -->
    <FarmerContractsList
      :contracts="marketStore.farmerContracts"
      :loading="marketStore.loading.farmerContracts"
      :pagination="marketStore.farmerPagination"
      :current-tab="currentTab"
      @cancel-contract="handleCancel"
      @tab-change="handleTabChange"
    />

    <!-- Pagination -->
    <PaginationControls
      :current-page="marketStore.farmerPagination.currentPage"
      :last-page="marketStore.farmerPagination.lastPage"
      :total="marketStore.farmerPagination.total"
      @page-change="handlePageChange"
    />
    <!-- Cancel Confirmation Modal -->
    <ConfirmModal
      :is-open="isOpen"
      :title="config.title"
      :message="config.message"
      :confirm-text="config.confirmText || 'Confirm'"
      :cancel-text="config.cancelText || 'Cancel'"
      :type="config.type"
      @confirm="execute"
      @cancel="cancel"
    />
  </div>
</template>
