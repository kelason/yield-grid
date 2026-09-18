<script setup>
import { onMounted, computed } from 'vue'
import { useMarketStore } from '@/stores/marketStore'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notificationStore'
import { useApi } from '@/composables/useApi'
import { ShoppingCartIcon } from '@heroicons/vue/24/outline'
import AppCard from '@/components/atoms/AppCard.vue'
import PurchaseCard from '@/components/molecules/PurchaseCard.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import { useConfirmModal } from '@/composables/useConfirmModal'

const marketStore = useMarketStore()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()
const api = useApi()
const { isOpen, config, confirm, execute, cancel } = useConfirmModal()

const totalSpent = computed(() =>
  marketStore.buyerPurchases.reduce((sum, p) => sum + parseFloat(p.amount_paid || 0), 0),
)

const activePurchases = computed(
  () => marketStore.buyerPurchases.filter((p) => p.payment_status === 'completed').length,
)

onMounted(() => {
  marketStore.fetchBuyerPurchases()
})

function formatCurrency(amount) {
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount)
}

const cancelPurchase = (purchase) => {
  confirm(
    {
      title: 'Cancel Purchase',
      message:
        'Are you sure you want to cancel this pending purchase? This will release the forward contract.',
      type: 'danger',
    },
    async () => {
      try {
        await api.post(`/checkout/${purchase.session_id}/cancel`)
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
          class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 transition-colors text-white text-sm font-medium rounded-xl px-4 py-2 self-start sm:self-auto"
        >
          <ShoppingCartIcon class="w-5 h-5" /> Browse Marketplace
        </router-link>
      </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
      <!-- Total Purchases -->
      <AppCard variant="gradient" class="!rounded-2xl">
        <div class="flex items-center gap-4">
          <div
            class="w-11 h-11 rounded-xl bg-white/20 flex items-center justify-center text-xl flex-shrink-0 shadow-sm"
          >
            📦
          </div>
          <div>
            <dt class="text-xs font-medium text-farm-100">Total Purchases</dt>
            <dd class="text-3xl font-bold text-white mt-0.5">
              {{ marketStore.buyerPurchases.length }}
            </dd>
          </div>
        </div>
      </AppCard>

      <!-- Active Contracts -->
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

      <!-- Total Spent -->
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
            class="inline-flex items-center gap-1.5 bg-farm-600 hover:bg-farm-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-colors"
          >
            <ShoppingCartIcon class="w-5 h-5" /> Browse Marketplace
          </router-link>
        </div>
      </AppCard>

      <!-- Purchase rows -->
      <div v-else class="space-y-4">
        <PurchaseCard
          v-for="purchase in marketStore.buyerPurchases.slice(0, 5)"
          :key="purchase.id"
          :purchase="purchase"
          @cancel="cancelPurchase"
        />
      </div>
    </div>
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
