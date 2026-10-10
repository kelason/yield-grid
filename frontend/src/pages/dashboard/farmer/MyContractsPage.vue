<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
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
const { t } = useI18n()
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
  const itemTypeLabel = t(
    contract.type === 'listing'
      ? 'farmer.contracts.type_listing'
      : 'farmer.contracts.type_contract',
  )
  confirm(
    {
      title: t('farmer.contracts.cancel_title', { type: itemTypeLabel }),
      message: t('farmer.contracts.cancel_message', { title: contract.title }),
      type: 'danger',
      confirmText: t('farmer.contracts.cancel_listing'),
      cancelText: t('farmer.contracts.keep_listing'),
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
      :title="t('farmer.contracts.title')"
      :description="t('farmer.contracts.description')"
    />
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <StatCard
        :label="t('farmer.contracts.stat_listed')"
        :value="marketStore.farmerStats.total_listed"
        :icon="DocumentTextIcon"
      />
      <StatCard
        :label="t('farmer.contracts.stat_reserved')"
        :value="marketStore.farmerStats.total_reserved"
        :icon="ClockIcon"
      />
      <StatCard
        :label="t('farmer.contracts.stat_sold')"
        :value="marketStore.farmerStats.total_sold"
        :icon="ChartBarIcon"
      />
      <StatCard
        :label="t('farmer.contracts.stat_revenue')"
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
      :confirm-text="config.confirmText || ''"
      :cancel-text="config.cancelText || ''"
      :type="config.type"
      @confirm="execute"
      @cancel="cancel"
    />
  </div>
</template>
