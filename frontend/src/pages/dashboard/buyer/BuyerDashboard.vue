<script setup>
import { onMounted, computed } from 'vue'
import { useMarketStore } from '@/stores/marketStore'
import { useAuthStore } from '@/stores/auth'
import AppCard from '@/components/atoms/AppCard.vue'

const marketStore = useMarketStore()
const authStore = useAuthStore()

const totalSpent = computed(() =>
  marketStore.buyerPurchases.reduce((sum, p) => sum + parseFloat(p.amount_paid || 0), 0),
)

const activePurchases = computed(
  () => marketStore.buyerPurchases.filter((p) => p.payment_status === 'paid').length,
)

onMounted(() => {
  marketStore.fetchBuyerPurchases()
})

function formatCurrency(amount) {
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount)
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Intl.DateTimeFormat('en-PH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(new Date(dateStr))
}

const statusStyles = {
  paid: 'bg-green-100 text-green-800',
  pending: 'bg-yellow-100 text-yellow-800',
  failed: 'bg-red-100 text-red-800',
}
</script>

<template>
  <div class="space-y-8">
    <!-- Welcome Banner -->
    <div class="rounded-2xl bg-gradient-to-r from-farm-600 to-farm-800 p-6 text-white shadow-lg">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <p class="text-farm-200 text-sm font-medium mb-1">Welcome back,</p>
          <h2 class="text-2xl font-bold">{{ authStore.user?.name }}</h2>
          <p class="text-farm-300 text-sm mt-1">
            Browse fresh forward contracts from Filipino farmers.
          </p>
        </div>
        <router-link
          :to="{ name: 'buyer-marketplace' }"
          class="inline-flex items-center gap-2 bg-white text-farm-700 font-semibold text-sm px-5 py-2.5 rounded-xl hover:bg-farm-50 transition-colors shadow-sm flex-shrink-0"
        >
          🛒 Browse Marketplace
        </router-link>
      </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
      <!-- Total Purchases -->
      <AppCard variant="gradient" class="!rounded-2xl">
        <div class="flex items-center gap-4">
          <div
            class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center text-2xl flex-shrink-0 shadow-sm"
          >
            📦
          </div>
          <div>
            <dt class="text-sm font-medium text-farm-100">Total Purchases</dt>
            <dd class="text-4xl font-bold text-white mt-1">
              {{ marketStore.buyerPurchases.length }}
            </dd>
          </div>
        </div>
      </AppCard>

      <!-- Active Contracts -->
      <AppCard>
        <div class="flex items-center gap-4">
          <div
            class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-2xl flex-shrink-0"
          >
            📋
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Active Contracts</dt>
            <dd class="text-4xl font-bold text-gray-900 mt-1">{{ activePurchases }}</dd>
          </div>
        </div>
      </AppCard>

      <!-- Total Spent -->
      <AppCard>
        <div class="flex items-center gap-4">
          <div
            class="w-12 h-12 rounded-xl bg-earth-50 flex items-center justify-center text-2xl flex-shrink-0"
          >
            💰
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Total Spent</dt>
            <dd class="text-xl font-bold text-gray-900 mt-1">{{ formatCurrency(totalSpent) }}</dd>
          </div>
        </div>
      </AppCard>
    </div>

    <!-- Recent Purchases -->
    <div>
      <div class="flex items-center gap-3 mb-4">
        <h2 class="text-lg font-bold text-gray-900">Recent Purchases</h2>
        <div class="flex-1 h-px bg-gradient-to-r from-gray-200 to-transparent"></div>
        <router-link
          :to="{ name: 'buyer-purchases' }"
          class="text-sm font-medium text-farm-600 hover:text-farm-700 transition-colors"
        >
          View all →
        </router-link>
      </div>

      <!-- Loading -->
      <AppCard v-if="marketStore.loading?.purchases">
        <div class="animate-pulse space-y-3">
          <div class="h-4 bg-gray-200 rounded w-3/4"></div>
          <div class="h-4 bg-gray-200 rounded w-1/2"></div>
          <div class="h-4 bg-gray-200 rounded w-2/3"></div>
        </div>
      </AppCard>

      <!-- Empty -->
      <AppCard v-else-if="marketStore.buyerPurchases.length === 0">
        <div class="text-center py-10">
          <div class="text-5xl mb-3">🌾</div>
          <h3 class="text-sm font-semibold text-gray-700 mb-1">No purchases yet</h3>
          <p class="text-sm text-gray-500 mb-4">
            Forward contracts let you secure crops before harvest at a fixed price.
          </p>
          <router-link
            :to="{ name: 'buyer-marketplace' }"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-farm-600 hover:text-farm-700 transition-colors"
          >
            Browse available contracts →
          </router-link>
        </div>
      </AppCard>

      <!-- Purchase rows -->
      <div v-else class="space-y-3">
        <AppCard
          v-for="purchase in marketStore.buyerPurchases.slice(0, 5)"
          :key="purchase.id"
          class="hover:border-farm-200 hover:shadow-md transition-all duration-200"
        >
          <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
              <div
                class="w-10 h-10 rounded-xl bg-farm-50 flex items-center justify-center text-xl flex-shrink-0"
              >
                🌱
              </div>
              <div class="min-w-0">
                <p class="font-semibold text-gray-900 text-sm truncate">
                  {{ purchase.contract?.crop_name || 'Forward Contract' }}
                </p>
                <p class="text-xs text-gray-500 mt-0.5">
                  {{ purchase.contract?.quantity_kg ?? '—' }} kg &nbsp;·&nbsp; Harvest
                  {{ formatDate(purchase.contract?.estimated_harvest_date) }}
                </p>
              </div>
            </div>
            <div class="flex items-center gap-3 flex-shrink-0">
              <span
                :class="statusStyles[purchase.payment_status] || 'bg-gray-100 text-gray-600'"
                class="text-xs font-semibold px-2.5 py-1 rounded-full capitalize"
              >
                {{ purchase.payment_status }}
              </span>
              <p class="text-sm font-bold text-gray-900 w-24 text-right">
                {{ formatCurrency(purchase.amount_paid) }}
              </p>
            </div>
          </div>
        </AppCard>
      </div>
    </div>
  </div>
</template>
