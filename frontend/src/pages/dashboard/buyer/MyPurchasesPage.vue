<script setup>
import { onMounted, computed } from 'vue'
import { useMarketStore } from '@/stores/marketStore'
import AppCard from '@/components/atoms/AppCard.vue'

const marketStore = useMarketStore()

onMounted(() => {
  marketStore.fetchBuyerPurchases()
})

const totalSpent = computed(() =>
  marketStore.buyerPurchases.reduce((sum, p) => sum + parseFloat(p.amount_paid || 0), 0)
)

const activePurchases = computed(() =>
  marketStore.buyerPurchases.filter(p => p.payment_status === 'paid').length
)

function formatCurrency(amount) {
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount)
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Intl.DateTimeFormat('en-PH', { year: 'numeric', month: 'short', day: 'numeric' }).format(new Date(dateStr))
}

const statusStyles = {
  paid: 'bg-green-100 text-green-800',
  pending: 'bg-yellow-100 text-yellow-800',
  failed: 'bg-red-100 text-red-800',
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
          🛒 Browse More
        </router-link>
      </div>
    </div>

    <!-- KPI Summary -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
      <AppCard variant="gradient" class="!rounded-2xl">
        <div class="flex items-center gap-4">
          <div class="w-11 h-11 rounded-xl bg-white/20 flex items-center justify-center text-xl flex-shrink-0">📦</div>
          <div>
            <dt class="text-xs font-medium text-farm-100">Total Orders</dt>
            <dd class="text-3xl font-bold text-white mt-0.5">{{ marketStore.buyerPurchases.length }}</dd>
          </div>
        </div>
      </AppCard>

      <AppCard>
        <div class="flex items-center gap-4">
          <div class="w-11 h-11 rounded-xl bg-green-50 flex items-center justify-center text-xl flex-shrink-0">✅</div>
          <div>
            <dt class="text-xs font-medium text-gray-500">Active Contracts</dt>
            <dd class="text-3xl font-bold text-gray-900 mt-0.5">{{ activePurchases }}</dd>
          </div>
        </div>
      </AppCard>

      <AppCard>
        <div class="flex items-center gap-4">
          <div class="w-11 h-11 rounded-xl bg-earth-50 flex items-center justify-center text-xl flex-shrink-0">💰</div>
          <div>
            <dt class="text-xs font-medium text-gray-500">Total Spent</dt>
            <dd class="text-lg font-bold text-gray-900 mt-0.5">{{ formatCurrency(totalSpent) }}</dd>
          </div>
        </div>
      </AppCard>
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
        <p class="text-sm text-gray-500 mb-5">Forward contracts let you secure crops before harvest at a guaranteed price.</p>
        <router-link
          :to="{ name: 'buyer-marketplace' }"
          class="inline-flex items-center gap-1.5 bg-farm-600 hover:bg-farm-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-colors"
        >
          🛒 Browse Marketplace
        </router-link>
      </div>
    </AppCard>

    <!-- Purchase List -->
    <div v-else class="space-y-3">
      <AppCard
        v-for="purchase in marketStore.buyerPurchases"
        :key="purchase.id"
        class="hover:border-farm-200 hover:shadow-md transition-all duration-200"
      >
        <div class="flex items-center justify-between gap-4 flex-wrap">
          <!-- Left: crop info -->
          <div class="flex items-center gap-3 min-w-0">
            <div class="w-11 h-11 rounded-xl bg-farm-50 flex items-center justify-center text-xl flex-shrink-0">🌱</div>
            <div class="min-w-0">
              <p class="font-semibold text-gray-900 truncate">
                {{ purchase.contract?.crop_name || 'Forward Contract' }}
              </p>
              <p class="text-xs text-gray-500 mt-0.5">
                {{ purchase.contract?.quantity_kg ?? '—' }} kg
                &nbsp;·&nbsp;
                Harvest {{ formatDate(purchase.contract?.estimated_harvest_date) }}
                &nbsp;·&nbsp;
                Purchased {{ formatDate(purchase.created_at) }}
              </p>
            </div>
          </div>

          <!-- Right: status + amount -->
          <div class="flex items-center gap-3 flex-shrink-0">
            <span
              :class="statusStyles[purchase.payment_status] || 'bg-gray-100 text-gray-600'"
              class="text-xs font-semibold px-2.5 py-1 rounded-full capitalize"
            >
              {{ purchase.payment_status }}
            </span>
            <div class="text-right">
              <p class="text-sm font-bold text-gray-900">{{ formatCurrency(purchase.amount_paid) }}</p>
              <p class="text-xs text-gray-400">{{ purchase.payment_method || '—' }}</p>
            </div>
          </div>
        </div>
      </AppCard>
    </div>
  </div>
</template>
