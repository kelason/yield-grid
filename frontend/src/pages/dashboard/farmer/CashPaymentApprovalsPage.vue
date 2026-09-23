<script setup>
import { onMounted, ref } from 'vue'
import { useMarketStore } from '@/stores/marketStore'
import { useNotificationStore } from '@/stores/notificationStore'
import PriceTag from '@/components/atoms/PriceTag.vue'

const marketStore = useMarketStore()
const notificationStore = useNotificationStore()

onMounted(() => {
  marketStore.fetchFarmerPurchases()
})

const isApproving = ref({})

const handleApprove = async (purchase, type) => {
  const amount = type === 'partial' ? purchase.total_contract_amount * 0.1 : purchase.total_contract_amount
  const amountPrompt = window.prompt(`Enter amount received for ${type} payment:`, amount)
  
  if (amountPrompt === null) return // Cancelled

  const parsedAmount = parseFloat(amountPrompt)
  if (isNaN(parsedAmount) || parsedAmount <= 0) {
    notificationStore.error('Invalid amount entered.')
    return
  }

  isApproving.value[purchase.id] = true
  try {
    await marketStore.approveCashPayment(purchase.id, type, parsedAmount)
    notificationStore.success(`Successfully approved ${type} cash payment!`)
  } catch (err) {
    notificationStore.error(err.response?.data?.message || 'Failed to approve payment')
  } finally {
    isApproving.value[purchase.id] = false
  }
}
</script>

<template>
  <div class="py-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="font-serif text-3xl font-bold text-stone-900">Cash Payment Approvals</h2>
        <p class="mt-1 text-sm text-stone-600">Review and approve cash/off-site payments from buyers.</p>
      </div>
    </div>

    <div v-if="marketStore.loading.purchases" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-moss-600"></div>
    </div>
    
    <div v-else-if="marketStore.farmerPurchases.length === 0" class="text-center py-12 bg-white rounded-2xl shadow-soft border border-stone-200">
      <p class="text-stone-500">No purchases found.</p>
    </div>

    <div v-else class="bg-white rounded-2xl shadow-soft border border-stone-200 overflow-hidden">
      <ul class="divide-y divide-stone-200">
        <li v-for="purchase in marketStore.farmerPurchases" :key="purchase.id" class="p-6 hover:bg-stone-50 transition-colors">
          <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex-1 min-w-0">
              <h3 class="text-lg font-bold text-stone-900 truncate font-serif">
                {{ purchase.contract?.title || 'Unknown Item' }}
              </h3>
              <p class="text-sm text-stone-500">
                Buyer: <span class="font-medium text-stone-900">{{ purchase.buyer?.name }}</span>
              </p>
              <div class="mt-2 flex gap-4 text-sm text-stone-600">
                <div>Total Amount: <PriceTag :amount="purchase.total_contract_amount" :currency="purchase.currency" class="inline font-medium" /></div>
                <div>Confirmed Paid: <PriceTag :amount="purchase.cash_amount_confirmed || 0" :currency="purchase.currency" class="inline font-medium text-moss-600" /></div>
              </div>
              <div class="mt-2">
                 <span class="text-xs font-medium bg-stone-100 text-stone-800 px-2 py-0.5 rounded">Payment Status: {{ purchase.payment_status }}</span>
                 <span class="ml-2 text-xs font-medium bg-harvest-100 text-harvest-800 px-2 py-0.5 rounded">Cash Status: {{ purchase.cash_payment_status || 'N/A' }}</span>
              </div>
            </div>

            <div class="flex gap-2" v-if="purchase.payment_status === 'pending' || purchase.cash_payment_status === 'pending' || purchase.cash_payment_status === 'partially_paid'">
              <button 
                @click="handleApprove(purchase, 'partial')"
                :disabled="isApproving[purchase.id]"
                v-if="purchase.cash_payment_status !== 'partially_paid'"
                class="px-3 py-1.5 text-sm font-medium rounded-xl text-moss-700 bg-moss-50 border border-moss-200 hover:bg-moss-100 transition-colors"
              >
                Approve Partial (10%)
              </button>
              <button 
                @click="handleApprove(purchase, 'full')"
                :disabled="isApproving[purchase.id]"
                class="px-3 py-1.5 text-sm font-medium rounded-xl text-white bg-gradient-to-br from-moss-500 to-moss-600 hover:from-moss-600 hover:to-moss-700 transition-colors"
              >
                Approve Full Paid
              </button>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>
