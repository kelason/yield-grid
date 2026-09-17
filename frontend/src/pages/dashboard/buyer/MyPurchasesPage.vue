<script setup>
import { onMounted, computed, watch } from 'vue'
import { useMarketStore } from '@/stores/marketStore'
import { ShoppingCartIcon } from '@heroicons/vue/24/outline'
import AppCard from '@/components/atoms/AppCard.vue'
import SearchInput from '@/components/molecules/SearchInput.vue'
import SortSelect from '@/components/molecules/SortSelect.vue'
import PaginationControls from '@/components/molecules/PaginationControls.vue'
import PurchaseCard from '@/components/molecules/PurchaseCard.vue'
import { useApi } from '@/composables/useApi'

const marketStore = useMarketStore()
const api = useApi()

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
    }, 300)
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

async function cancelPurchase(purchase) {
  if (
    !confirm(
      'Are you sure you want to cancel this pending purchase? This will release the forward contract.',
    )
  ) {
    return
  }

  try {
    await api.post(`/checkout/${purchase.session_id}/cancel`)
    marketStore.fetchBuyerPurchases() // Refresh list
  } catch (err) {
    console.error('Failed to cancel purchase:', err)
    alert('Failed to cancel purchase. Please try again.')
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="rounded-2xl bg-gradient-to-r from-farm-600 to-farm-800 p-6 text-white shadow-lg">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
          <h1 class="text-2xl font-bold">Purchase History</h1>
          <p class="text-farm-200 text-sm mt-1">All your forward contracts and payment records.</p>
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
            <dt class="text-xs font-medium text-farm-100">Total Orders</dt>
            <dd class="text-3xl font-bold text-white mt-0.5">
              {{ totalOrders }}
            </dd>
          </div>
        </div>
      </AppCard>

      <AppCard>
        <div class="flex items-center gap-4">
          <div
            class="w-11 h-11 rounded-xl bg-green-50 flex items-center justify-center text-xl flex-shrink-0"
          >
            ✅
          </div>
          <div>
            <dt class="text-xs font-medium text-gray-500">Active Contracts</dt>
            <dd class="text-3xl font-bold text-gray-900 mt-0.5">{{ activePurchases }}</dd>
          </div>
        </div>
      </AppCard>

      <AppCard>
        <div class="flex items-center gap-4">
          <div
            class="w-11 h-11 rounded-xl bg-earth-50 flex items-center justify-center text-xl flex-shrink-0"
          >
            💰
          </div>
          <div>
            <dt class="text-xs font-medium text-gray-500">Total Spent</dt>
            <dd class="text-lg font-bold text-gray-900 mt-0.5">{{ formatCurrency(totalSpent) }}</dd>
          </div>
        </div>
      </AppCard>
    </div>

    <!-- Controls: Search and Sort -->
    <div class="bg-white/80 backdrop-blur-sm border border-gray-200 shadow-sm rounded-lg p-4">
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <!-- Search -->
        <div class="w-full md:w-1/3">
          <label class="block text-xs font-medium text-gray-700 mb-1.5 ml-1">Search Crop</label>
          <SearchInput
            v-model="marketStore.buyerPurchasesFilters.search"
            placeholder="e.g. Rice, Corn..."
          />
        </div>

        <!-- Sort -->
        <div class="w-full md:w-1/4">
          <label class="block text-xs font-medium text-gray-700 mb-1.5 ml-1">Sort By</label>
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

    <!-- Loading -->
    <AppCard v-if="marketStore.loading.purchases">
      <div class="animate-pulse space-y-4">
        <div class="h-4 bg-gray-200 rounded w-3/4"></div>
        <div class="h-4 bg-gray-200 rounded w-1/2"></div>
        <div class="h-4 bg-gray-200 rounded w-2/3"></div>
      </div>
    </AppCard>

    <!-- Empty State -->
    <AppCard v-else-if="marketStore.buyerPurchases.length === 0">
      <div class="text-center py-12">
        <div class="text-5xl mb-3">🌾</div>
        <h3 class="text-sm font-semibold text-gray-700 mb-1">No purchases yet</h3>
        <p class="text-sm text-gray-500 mb-5">
          Forward contracts let you secure crops before harvest at a guaranteed price.
        </p>
        <router-link
          :to="{ name: 'buyer-marketplace' }"
          class="inline-flex items-center gap-1.5 bg-farm-600 hover:bg-farm-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-colors"
        >
          <ShoppingCartIcon class="w-5 h-5" /> Browse Marketplace
        </router-link>
      </div>
    </AppCard>

    <!-- Purchase List -->
    <div v-else class="space-y-4">
      <PurchaseCard
        v-for="purchase in marketStore.buyerPurchases"
        :key="purchase.id"
        :purchase="purchase"
        @cancel="cancelPurchase"
      />
    </div>

    <!-- Pagination -->
    <PaginationControls
      :current-page="marketStore.buyerPurchasesPagination.currentPage"
      :last-page="marketStore.buyerPurchasesPagination.lastPage"
      :total="marketStore.buyerPurchasesPagination.total"
      @page-change="marketStore.fetchBuyerPurchases"
    />
  </div>
</template>
