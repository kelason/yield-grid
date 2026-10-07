<script setup>
import { onMounted, computed, ref, watch } from 'vue'
import { useMarketStore } from '@/stores/marketStore'
import { useNotificationStore } from '@/stores/notificationStore'
import { ShoppingCartIcon } from '@heroicons/vue/24/outline'
import PageHeader from '@/components/molecules/PageHeader.vue'
import StatCard from '@/components/molecules/StatCard.vue'
import LoadingState from '@/components/molecules/LoadingState.vue'
import SkeletonCard from '@/components/atoms/SkeletonCard.vue'
import EmptyState from '@/components/molecules/EmptyState.vue'
import AppAlert from '@/components/atoms/AppAlert.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import { CATALOG_LIMITS } from '@/constants/catalog'
import SearchInput from '@/components/molecules/SearchInput.vue'
import SortSelect from '@/components/molecules/SortSelect.vue'
import PaginationControls from '@/components/molecules/PaginationControls.vue'
import PurchaseCard from '@/components/molecules/PurchaseCard.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import { useApi } from '@/composables/useApi'
import { useConfirmModal } from '@/composables/useConfirmModal'
import { useChatEntry } from '@/composables/useChatEntry'

const marketStore = useMarketStore()
const notificationStore = useNotificationStore()
const api = useApi()
const SEARCH_DEBOUNCE_MS = 300
const PURCHASE_SKELETON_COUNT = 3
const { isOpen, isExecuting, config, confirm, execute, cancel } = useConfirmModal()
const { openChat } = useChatEntry()

const PURCHASE_TABS = [
  { id: 'all', name: 'All Purchases', status: null },
  { id: 'pending', name: 'Pending', status: 'pending' },
  { id: 'partially_paid', name: 'Partially Paid', status: 'partially_paid' },
  { id: 'paid', name: 'Paid', status: 'paid' },
  { id: 'failed', name: 'Failed', status: 'failed' },
]

const currentTab = ref('all')

const activeTab = computed(() => PURCHASE_TABS.find((tab) => tab.id === currentTab.value))

const emptyTitle = computed(() =>
  currentTab.value === 'all'
    ? 'No purchases yet'
    : `No ${activeTab.value.name.toLowerCase()} purchases`,
)

const emptyDescription = computed(() =>
  currentTab.value === 'all'
    ? 'Forward contracts let you secure crops before harvest at a guaranteed price.'
    : 'Purchases with this status will appear here.',
)

function handleTabChange(tabId) {
  if (tabId === currentTab.value) return
  currentTab.value = tabId
  marketStore.buyerPurchasesFilters.status = activeTab.value?.status || null
  marketStore.fetchBuyerPurchases(1)
}

const messageFarmer = (purchase) => {
  confirm(
    {
      title: 'Open conversation?',
      message: 'Open a conversation with this farmer?',
      confirmText: 'Open conversation',
    },
    () => openChat(purchase.contract?.farmer?.id || purchase.demand_offer?.farmer?.id),
  )
}

onMounted(() => {
  marketStore.fetchBuyerPurchases()
})

let searchTimeout = null
watch(
  () => marketStore.buyerPurchasesFilters.search,
  () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
      marketStore.fetchBuyerPurchases(1)
    }, SEARCH_DEBOUNCE_MS)
  },
)

watch(
  () => marketStore.buyerPurchasesFilters.sort,
  () => {
    marketStore.fetchBuyerPurchases(1)
  },
)

const totalSpent = computed(() =>
  marketStore.buyerPurchases.reduce((sum, p) => sum + parseFloat(p.amount_paid || 0), 0),
)

const activePurchases = computed(
  () => marketStore.buyerPurchases.filter((p) => p.payment_status === 'completed').length,
)

const totalOrders = computed(
  () => marketStore.buyerPurchases.filter((p) => p.payment_status !== 'failed').length,
)

function formatCurrency(amount) {
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount)
}

const cancelPurchase = (purchase) => {
  confirm(
    {
      title: 'Cancel Purchase',
      message:
        'Are you sure you want to cancel this pending purchase? The pending payment will be voided.',
      type: 'danger',
    },
    async () => {
      try {
        await api.post(`/checkout/${purchase.id}/cancel`)
        marketStore.fetchBuyerPurchases() // Refresh list
      } catch (err) {
        console.error('Failed to cancel purchase:', err)
        notificationStore.error('Failed to cancel purchase. Please try again.')
      }
    },
  )
}
</script>

<template>
  <div class="space-y-6">
    <PageHeader
      title="Purchase History"
      description="All your forward contracts and payment records."
    >
      <template #actions
        ><router-link
          :to="{ name: 'buyer-marketplace' }"
          class="inline-flex min-h-11 items-center gap-2 rounded-xl px-4 text-moss-700 transition-colors hover:bg-moss-50"
          ><ShoppingCartIcon class="w-5 h-5" aria-hidden="true" />Browse More</router-link
        ></template
      >
    </PageHeader>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
      <StatCard
        label="Orders on this page"
        :value="marketStore.buyerPurchasesError ? null : totalOrders"
        :loading="marketStore.loading.purchases"
      />
      <StatCard
        label="Completed payments on this page"
        :value="marketStore.buyerPurchasesError ? null : activePurchases"
        :loading="marketStore.loading.purchases"
      />
      <StatCard
        label="Amount paid on this page"
        :value="marketStore.buyerPurchasesError ? null : formatCurrency(totalSpent)"
        :loading="marketStore.loading.purchases"
        tone="harvest"
      />
    </div>

    <!-- Controls: Search and Sort -->
    <div class="bg-white border border-stone-200 shadow-soft rounded-2xl p-4">
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <!-- Search -->
        <div class="w-full md:w-1/3">
          <label for="purchase-search" class="block text-sm font-medium text-soil-700 mb-1.5"
            >Search Crop</label
          >
          <SearchInput
            id="purchase-search"
            label="Search Crop"
            :maxlength="CATALOG_LIMITS.CROP_MAX_LENGTH"
            v-model="marketStore.buyerPurchasesFilters.search"
            placeholder="e.g. Rice, Corn..."
          />
        </div>

        <!-- Sort -->
        <div class="w-full md:w-1/4">
          <label for="purchase-sort" class="block text-sm font-medium text-soil-700 mb-1.5"
            >Sort By</label
          >
          <SortSelect
            id="purchase-sort"
            v-model="marketStore.buyerPurchasesFilters.sort"
            :options="[
              { value: 'newest', label: 'Newest First' },
              { value: 'oldest', label: 'Oldest First' },
              { value: 'highest_price', label: 'Highest Price' },
              { value: 'lowest_price', label: 'Lowest Price' },
            ]"
          />
        </div>
      </div>
    </div>

    <div class="bg-white shadow-soft rounded-2xl border border-stone-200 overflow-hidden">
      <div class="border-b border-stone-200">
        <nav
          class="-mb-px flex space-x-8 px-6 overflow-x-auto"
          aria-label="Filter purchases by status"
        >
          <button
            v-for="tab in PURCHASE_TABS"
            :key="tab.id"
            type="button"
            @click="handleTabChange(tab.id)"
            :class="[
              currentTab === tab.id
                ? 'border-moss-500 text-moss-700'
                : 'border-transparent text-stone-500 hover:text-stone-700 hover:border-stone-300',
              'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
            ]"
          >
            {{ tab.name }}
          </button>
        </nav>
      </div>

      <LoadingState
        v-if="marketStore.loading.purchases"
        label="Loading purchases"
        class="p-5 space-y-4"
        ><SkeletonCard v-for="n in PURCHASE_SKELETON_COUNT" :key="n" withAction
      /></LoadingState>
      <div v-else-if="marketStore.buyerPurchasesError" class="p-5 space-y-3">
        <AppAlert type="error">{{ marketStore.buyerPurchasesError }}</AppAlert
        ><AppButton @click="marketStore.fetchBuyerPurchases()">Retry</AppButton>
      </div>

      <EmptyState
        v-else-if="marketStore.buyerPurchases.length === 0"
        :title="emptyTitle"
        :description="emptyDescription"
      >
        <template #action v-if="currentTab === 'all'"
          ><router-link
            :to="{ name: 'buyer-marketplace' }"
            class="inline-flex min-h-11 items-center rounded-xl bg-moss-600 px-5 text-white transition-colors hover:bg-moss-700"
            >Browse Marketplace</router-link
          ></template
        >
      </EmptyState>

      <div v-else class="divide-y divide-stone-200">
        <div
          v-for="purchase in marketStore.buyerPurchases"
          :key="purchase.id"
          class="p-5 sm:p-6 hover:bg-stone-50 transition-colors duration-200"
        >
          <PurchaseCard :purchase="purchase" @cancel="cancelPurchase" @message="messageFarmer" />
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <PaginationControls
      :current-page="marketStore.buyerPurchasesPagination.currentPage"
      :last-page="marketStore.buyerPurchasesPagination.lastPage"
      :total="marketStore.buyerPurchasesPagination.total"
      @page-change="marketStore.fetchBuyerPurchases"
    />
    <ConfirmModal
      :loading="isExecuting"
      :is-open="isOpen"
      :title="config.title"
      :message="config.message"
      :type="config.type"
      @confirm="execute"
      @cancel="cancel"
    />
  </div>
</template>
