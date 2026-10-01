<script setup>
import { onMounted, computed, ref, watch } from 'vue'
import { useMarketStore } from '@/stores/marketStore'
import { useNotificationStore } from '@/stores/notificationStore'
import { ShoppingCartIcon } from '@heroicons/vue/24/outline'
import AppCard from '@/components/atoms/AppCard.vue'
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
const { isOpen, config, confirm, execute, cancel } = useConfirmModal()
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
  openChat(purchase.contract?.farmer?.id || purchase.demand_offer?.farmer?.id)
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
    <!-- Page Header -->
    <div
      class="rounded-2xl bg-gradient-to-r from-moss-600 to-moss-800 p-6 text-white shadow-organic"
    >
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
          <h1 class="font-serif text-2xl font-bold">Purchase History</h1>
          <p class="text-moss-200 text-sm mt-1">All your forward contracts and payment records.</p>
        </div>
        <router-link
          :to="{ name: 'buyer-marketplace' }"
          class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 transition-colors text-white text-sm font-medium rounded-xl px-4 py-2 self-start sm:self-auto"
        >
          <ShoppingCartIcon class="w-5 h-5" /> Browse More
        </router-link>
      </div>
    </div>

    <!-- KPI Summary -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
      <AppCard variant="gradient" class="!rounded-2xl">
        <div class="flex items-center gap-4">
          <div
            class="w-11 h-11 rounded-xl bg-white/20 flex items-center justify-center text-xl flex-shrink-0"
          >
            📦
          </div>
          <div>
            <dt class="text-xs font-medium text-moss-100">Total Orders</dt>
            <dd class="text-3xl font-bold text-white mt-0.5">
              {{ totalOrders }}
            </dd>
          </div>
        </div>
      </AppCard>

      <AppCard>
        <div class="flex items-center gap-4">
          <div
            class="w-11 h-11 rounded-xl bg-moss-50 flex items-center justify-center text-xl flex-shrink-0"
          >
            ✅
          </div>
          <div>
            <dt class="text-xs font-medium text-stone-500">Active Contracts</dt>
            <dd class="text-3xl font-bold text-stone-900 mt-0.5">{{ activePurchases }}</dd>
          </div>
        </div>
      </AppCard>

      <AppCard>
        <div class="flex items-center gap-4">
          <div
            class="w-11 h-11 rounded-xl bg-stone-50 flex items-center justify-center text-xl flex-shrink-0"
          >
            💰
          </div>
          <div>
            <dt class="text-xs font-medium text-stone-500">Total Spent</dt>
            <dd class="text-lg font-bold text-stone-900 mt-0.5">
              {{ formatCurrency(totalSpent) }}
            </dd>
          </div>
        </div>
      </AppCard>
    </div>

    <!-- Controls: Search and Sort -->
    <div class="bg-white/80 backdrop-blur-sm border border-stone-200 shadow-soft rounded-2xl p-4">
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <!-- Search -->
        <div class="w-full md:w-1/3">
          <label class="block text-xs font-medium text-soil-700 mb-1.5 ml-1">Search Crop</label>
          <SearchInput
            v-model="marketStore.buyerPurchasesFilters.search"
            placeholder="e.g. Rice, Corn..."
          />
        </div>

        <!-- Sort -->
        <div class="w-full md:w-1/4">
          <label class="block text-xs font-medium text-soil-700 mb-1.5 ml-1">Sort By</label>
          <SortSelect
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

      <!-- Loading -->
      <div v-if="marketStore.loading.purchases" class="p-5">
        <div class="animate-pulse space-y-4">
          <div class="h-4 bg-stone-200 rounded-xl w-3/4"></div>
          <div class="h-4 bg-stone-200 rounded-xl w-1/2"></div>
          <div class="h-4 bg-stone-200 rounded-xl w-2/3"></div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="marketStore.buyerPurchases.length === 0" class="text-center py-12 px-6">
        <div class="text-5xl mb-3">🌾</div>
        <h3 class="font-serif text-sm font-semibold text-stone-700 mb-1">{{ emptyTitle }}</h3>
        <p class="text-sm text-stone-500 mb-5">{{ emptyDescription }}</p>
        <router-link
          v-if="currentTab === 'all'"
          :to="{ name: 'buyer-marketplace' }"
          class="inline-flex items-center gap-1.5 bg-gradient-to-br from-moss-500 to-moss-600 hover:from-moss-600 hover:to-moss-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-all duration-300 hover:scale-[1.02]"
        >
          <ShoppingCartIcon class="w-5 h-5" /> Browse Marketplace
        </router-link>
      </div>

      <!-- Purchase List -->
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
      :is-open="isOpen"
      :title="config.title"
      :message="config.message"
      :type="config.type"
      @confirm="execute"
      @cancel="cancel"
    />
  </div>
</template>
