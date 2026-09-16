<script setup>
import StatusBadge from '../atoms/StatusBadge.vue'
import PriceTag from '../atoms/PriceTag.vue'
import PaymentMethodIcon from '../atoms/PaymentMethodIcon.vue'
import { CalendarIcon, DocumentTextIcon, BuildingStorefrontIcon } from '@heroicons/vue/24/outline'
import { format } from 'date-fns'

defineProps({
  purchase: {
    type: Object,
    required: true,
  },
})

function formatDate(dateString) {
  if (!dateString) return 'Pending'
  try {
    return format(new Date(dateString), 'MMM d, yyyy h:mm a')
  } catch {
    return dateString
  }
}
</script>

<template>
  <div
    class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200 p-5 hover:border-farm-300 transition-colors"
  >
    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
      <!-- Contract Info -->
      <div class="flex-grow">
        <div class="flex items-center gap-2 mb-2">
          <StatusBadge :status="purchase.payment_status" size="sm" />
          <span class="text-xs text-gray-500">{{ formatDate(purchase.purchased_at) }}</span>
        </div>

        <h3 class="text-lg font-bold text-gray-900 mb-1">
          {{ purchase.contract?.title || 'Unknown Contract' }}
        </h3>

        <div class="flex flex-wrap gap-x-4 gap-y-2 mt-3 text-sm text-gray-600">
          <div class="flex items-center">
            <BuildingStorefrontIcon class="h-4 w-4 mr-1.5 text-gray-400" />
            {{ purchase.contract?.farmer?.name || 'Farmer' }}
          </div>
          <div class="flex items-center">
            <DocumentTextIcon class="h-4 w-4 mr-1.5 text-gray-400" />
            {{ purchase.contract?.quantity_kg || 0 }}kg {{ purchase.contract?.crop_name }}
          </div>
          <div class="flex items-center">
            <CalendarIcon class="h-4 w-4 mr-1.5 text-gray-400" />
            Harvest: {{ purchase.contract?.estimated_harvest_date }}
          </div>
        </div>
      </div>

      <!-- Payment Info -->
      <div
        class="flex flex-col items-start sm:items-end bg-gray-50 p-4 rounded-md sm:min-w-[200px]"
      >
        <div class="text-xs text-gray-500 mb-1">Total Paid</div>
        <PriceTag
          :amount="purchase.amount_paid"
          :currency="purchase.currency"
          size="md"
          class="mb-2"
        />

        <div class="flex items-center gap-2 mt-auto">
          <span class="text-xs text-gray-500">via</span>
          <PaymentMethodIcon v-if="purchase.payment_method" :method="purchase.payment_method" />
          <span v-else class="text-xs text-gray-400 italic">Not set</span>
        </div>
      </div>
    </div>
  </div>
</template>
