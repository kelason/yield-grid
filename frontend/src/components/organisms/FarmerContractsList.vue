<script setup>
import { ref, computed } from 'vue'
import StatusBadge from '../atoms/StatusBadge.vue'
import PriceTag from '../atoms/PriceTag.vue'

const props = defineProps({
  contracts: {
    type: Array,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  }
})

defineEmits(['cancel-contract', 'view-contract'])

const currentTab = ref('all')

const tabs = [
  { id: 'all', name: 'All Contracts' },
  { id: 'available', name: 'Available' },
  { id: 'reserved', name: 'Reserved' },
  { id: 'sold', name: 'Sold' },
  { id: 'expired', name: 'Expired/Cancelled' }
]

const filteredContracts = computed(() => {
  if (currentTab.value === 'all') return props.contracts
  if (currentTab.value === 'expired') return props.contracts.filter(c => ['expired', 'cancelled'].includes(c.status))
  return props.contracts.filter(c => c.status === currentTab.value)
})
</script>

<template>
  <div class="bg-white shadow rounded-lg border border-gray-200">
    <div class="border-b border-gray-200">
      <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="currentTab = tab.id"
          :class="[
            currentTab === tab.id
              ? 'border-farm-500 text-farm-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
          ]"
        >
          {{ tab.name }}
        </button>
      </nav>
    </div>

    <div class="p-0">
      <div v-if="loading && contracts.length === 0" class="p-8 text-center text-gray-500">
        <svg class="animate-spin h-8 w-8 text-farm-500 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Loading contracts...
      </div>
      
      <div v-else-if="filteredContracts.length === 0" class="p-12 text-center text-gray-500">
        <div class="bg-gray-50 rounded-full h-16 w-16 flex items-center justify-center mx-auto mb-4">
          <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <p>No contracts found in this category.</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contract Details</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Value</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harvest Date</th>
              <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="contract in filteredContracts" :key="contract.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex flex-col">
                  <span class="text-sm font-medium text-gray-900">{{ contract.title }}</span>
                  <span class="text-sm text-gray-500">{{ contract.quantity_kg }}kg {{ contract.crop_name }}</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <StatusBadge :status="contract.status" size="sm" />
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <PriceTag :amount="contract.total_price" :currency="contract.currency" size="sm" />
                <div class="text-xs text-gray-400 mt-1">@ ₱{{ contract.price_per_kg }}/kg</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ contract.estimated_harvest_date }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex justify-end gap-3">
                  <button @click="$emit('view-contract', contract)" class="text-farm-600 hover:text-farm-900">View</button>
                  <button v-if="contract.status === 'available'" @click="$emit('cancel-contract', contract)" class="text-red-600 hover:text-red-900">Cancel</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
