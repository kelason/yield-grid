<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { useMarketStore } from '@/stores/marketStore'
import { useAuthStore } from '@/stores/auth'
import FarmerContractsList from '@/components/organisms/FarmerContractsList.vue'
import PaginationControls from '@/components/molecules/PaginationControls.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import { BanknotesIcon, DocumentTextIcon, ChartBarIcon, ClockIcon } from '@heroicons/vue/24/outline'

const marketStore = useMarketStore()
const authStore = useAuthStore()

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

const isConfirmModalOpen = ref(false)
const confirmModalConfig = ref({ title: '', message: '', type: 'primary' })
let confirmAction = null

const executeConfirm = async () => {
  if (confirmAction) await confirmAction()
  isConfirmModalOpen.value = false
}

const cancelConfirm = () => {
  confirmAction = null
  isConfirmModalOpen.value = false
}

const handleCancel = (contract) => {
  confirmModalConfig.value = {
    title: 'Cancel Listing',
    message: `Are you sure you want to cancel the listing for "${contract.title}"?`,
    type: 'danger',
  }
  confirmAction = async () => {
    await marketStore.cancelContract(contract.id)
    await marketStore.fetchFarmerContractsStats()
  }
  isConfirmModalOpen.value = true
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
        class="bg-gradient-to-r from-farm-500 to-farm-600 overflow-hidden shadow rounded-lg text-white"
      >
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0 bg-white/20 rounded-md p-3">
              <BanknotesIcon class="h-6 w-6 text-white" aria-hidden="true" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-white/80 truncate">Total Revenue</dt>
                <dd>
                  <div class="text-2xl font-semibold text-white">
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
    <ConfirmModal
      :is-open="isConfirmModalOpen"
      :title="confirmModalConfig.title"
      :message="confirmModalConfig.message"
      :type="confirmModalConfig.type"
      @confirm="executeConfirm"
      @cancel="cancelConfirm"
    />
  </div>
</template>
