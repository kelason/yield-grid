<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { useMarketStore } from '@/stores/marketStore'
import { useAuthStore } from '@/stores/auth'
import FarmerContractsList from '@/components/organisms/FarmerContractsList.vue'
import PaginationControls from '@/components/molecules/PaginationControls.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import PageHeader from '@/components/molecules/PageHeader.vue'
import StatCard from '@/components/molecules/StatCard.vue'
import { BanknotesIcon, DocumentTextIcon, ChartBarIcon, ClockIcon } from '@heroicons/vue/24/outline'
import { useConfirmModal } from '@/composables/useConfirmModal'

const marketStore = useMarketStore()
const authStore = useAuthStore()
const { isOpen, isExecuting, config, confirm, execute, cancel } = useConfirmModal()

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
    <PageHeader
      title="My Forward Contracts"
      description="Manage your published crop listings and track sales."
    />
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <StatCard
        label="Total Listed"
        :value="marketStore.farmerStats.total_listed"
        :icon="DocumentTextIcon"
      />
      <StatCard
        label="Total Reserved"
        :value="marketStore.farmerStats.total_reserved"
        :icon="ClockIcon"
      />
      <StatCard
        label="Total Sold"
        :value="marketStore.farmerStats.total_sold"
        :icon="ChartBarIcon"
      />
      <StatCard
        label="Total Revenue"
        :value="`₱${Number(marketStore.farmerStats.total_revenue || 0).toLocaleString('en-PH')}`"
        :icon="BanknotesIcon"
        tone="harvest"
      />
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
      :loading="isExecuting"
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
