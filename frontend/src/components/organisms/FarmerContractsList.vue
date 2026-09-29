<script setup>
import StatusBadge from '../atoms/StatusBadge.vue'
import PriceTag from '../atoms/PriceTag.vue'
import { DocumentTextIcon, SparklesIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  contracts: {
    type: Array,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  pagination: {
    type: Object,
    required: true,
  },
  currentTab: {
    type: String,
    default: 'all',
  },
})

const emit = defineEmits(['cancel-contract', 'tab-change'])

const CONTRACT_SKELETON_ROW_COUNT = 5

const tabs = [
  { id: 'all', name: 'All Contracts' },
  { id: 'available', name: 'Available' },
  { id: 'reserved', name: 'Reserved' },
  { id: 'sold', name: 'Sold' },
  { id: 'expired', name: 'Expired/Cancelled' },
]

const handleTabChange = (tabId) => {
  if (tabId !== props.currentTab) {
    emit('tab-change', tabId)
  }
}
</script>

<template>
  <div class="bg-white shadow rounded-lg border border-gray-200">
    <div class="border-b border-gray-200">
      <nav class="-mb-px flex space-x-8 px-6 overflow-x-auto no-scrollbar" aria-label="Tabs">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="handleTabChange(tab.id)"
          :class="[
            currentTab === tab.id
              ? 'border-green-500 text-green-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm',
          ]"
        >
          {{ tab.name }}
        </button>
      </nav>
    </div>

    <div class="p-0">
      <div v-if="loading && contracts.length === 0" role="status" aria-label="Loading contracts">
        <!-- Desktop: skeleton table rows mirroring the contracts table -->
        <div class="hidden lg:block overflow-x-auto" aria-hidden="true">
          <table class="min-w-full divide-y divide-stone-200">
            <tbody class="bg-white divide-y divide-stone-200">
              <tr
                v-for="n in CONTRACT_SKELETON_ROW_COUNT"
                :key="n"
                class="animate-pulse motion-reduce:animate-none"
              >
                <td class="px-6 py-4">
                  <div class="space-y-2">
                    <div class="h-4 bg-stone-200 rounded-lg w-48"></div>
                    <div class="h-3 bg-stone-200 rounded-lg w-32"></div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="h-6 bg-stone-200 rounded-full w-20"></div>
                </td>
                <td class="px-6 py-4">
                  <div class="h-4 bg-stone-200 rounded-lg w-24"></div>
                </td>
                <td class="px-6 py-4">
                  <div class="h-4 bg-stone-200 rounded-lg w-28"></div>
                </td>
                <td class="px-6 py-4">
                  <div class="h-4 bg-stone-200 rounded-lg w-16 ml-auto"></div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- Mobile/Tablet: skeleton cards mirroring the card list -->
        <div class="lg:hidden divide-y divide-stone-200" aria-hidden="true">
          <div
            v-for="n in CONTRACT_SKELETON_ROW_COUNT"
            :key="n"
            class="animate-pulse motion-reduce:animate-none p-4 space-y-3"
          >
            <div class="flex justify-between items-center gap-3">
              <div class="h-4 bg-stone-200 rounded-lg w-1/2"></div>
              <div class="h-6 bg-stone-200 rounded-full w-16 flex-shrink-0"></div>
            </div>
            <div class="h-16 bg-stone-200 rounded-xl"></div>
          </div>
        </div>
        <span class="sr-only">Loading contracts...</span>
      </div>

      <div v-else-if="contracts.length === 0" class="p-12 text-center text-gray-500">
        <div
          class="bg-gray-50 rounded-full h-16 w-16 flex items-center justify-center mx-auto mb-4"
        >
          <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            />
          </svg>
        </div>
        <p>No contracts found in this category.</p>
      </div>

      <div v-else>
        <!-- Desktop Table -->
        <div class="hidden lg:block overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th
                  scope="col"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Contract Details
                </th>
                <th
                  scope="col"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Status
                </th>
                <th
                  scope="col"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Total Value
                </th>
                <th
                  scope="col"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Harvest Date
                </th>
                <th
                  scope="col"
                  class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="contract in contracts"
                :key="contract.id"
                class="hover:bg-gray-50 transition-colors"
              >
                <td class="px-6 py-4 align-top">
                  <div class="flex flex-col">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                      <span class="text-sm font-medium text-stone-900">{{ contract.title }}</span>
                      <span
                        v-if="contract.type === 'listing'"
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-stone-100 text-stone-700 text-xs font-medium border border-stone-200 whitespace-nowrap"
                      >
                        <DocumentTextIcon class="w-3.5 h-3.5" />
                        Manual Listing
                      </span>
                      <span
                        v-else
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-dew-100 text-dew-700 text-xs font-medium border border-dew-200 whitespace-nowrap"
                      >
                        <SparklesIcon class="w-3.5 h-3.5" />
                        AI Recommended
                      </span>
                    </div>
                    <span class="text-sm text-stone-500"
                      >{{ contract.quantity_kg }}kg {{ contract.crop_name }}</span
                    >
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap align-top">
                  <StatusBadge :status="contract.status" size="sm" />
                </td>
                <td class="px-6 py-4 whitespace-nowrap align-top">
                  <PriceTag
                    :amount="contract.total_price"
                    :currency="contract.currency"
                    size="sm"
                  />
                  <div class="text-xs text-gray-400 mt-1">@ ₱{{ contract.price_per_kg }}/kg</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 align-top">
                  {{ contract.estimated_harvest_date }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium align-top">
                  <div class="flex justify-end gap-3">
                    <button
                      v-if="contract.status === 'available'"
                      @click="$emit('cancel-contract', contract)"
                      class="text-red-600 hover:text-red-900"
                    >
                      Cancel
                    </button>
                    <router-link
                      v-else-if="
                        contract.status === 'reserved' || contract.status === 'partially_paid'
                      "
                      :to="{ name: 'farmer-cash-approvals' }"
                      class="text-moss-600 hover:text-moss-900"
                    >
                      Review Payment
                    </router-link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile/Tablet Card List -->
        <div class="lg:hidden divide-y divide-gray-200">
          <div
            v-for="contract in contracts"
            :key="contract.id"
            class="p-4 bg-white hover:bg-gray-50 transition-colors"
          >
            <div class="flex justify-between items-start mb-3">
              <div class="flex flex-col pr-4">
                <span class="text-sm font-bold text-stone-900">{{ contract.title }}</span>
                <span class="text-xs text-stone-500 mt-0.5"
                  >{{ contract.quantity_kg }}kg {{ contract.crop_name }}</span
                >
                <div class="mt-2">
                  <span
                    v-if="contract.type === 'listing'"
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-stone-100 text-stone-700 text-xs font-medium border border-stone-200"
                  >
                    <DocumentTextIcon class="w-3.5 h-3.5" />
                    Manual Listing
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-dew-100 text-dew-700 text-xs font-medium border border-dew-200"
                  >
                    <SparklesIcon class="w-3.5 h-3.5" />
                    AI Recommended
                  </span>
                </div>
              </div>
              <StatusBadge :status="contract.status" size="sm" />
            </div>

            <div
              class="grid grid-cols-2 gap-4 mt-4 text-sm bg-gray-50 p-3 rounded-lg border border-gray-100"
            >
              <div>
                <span class="block text-xs font-medium text-gray-500 mb-1">Total Value</span>
                <PriceTag :amount="contract.total_price" :currency="contract.currency" size="sm" />
                <div class="text-xs text-gray-400 mt-0.5">@ ₱{{ contract.price_per_kg }}/kg</div>
              </div>
              <div>
                <span class="block text-xs font-medium text-gray-500 mb-1">Harvest Date</span>
                <span class="text-gray-900">{{ contract.estimated_harvest_date }}</span>
              </div>
            </div>

            <div
              class="flex justify-end pt-3 mt-3 border-t border-gray-100"
              v-if="
                contract.status === 'available' ||
                contract.status === 'reserved' ||
                contract.status === 'partially_paid'
              "
            >
              <button
                v-if="contract.status === 'available'"
                @click="$emit('cancel-contract', contract)"
                class="inline-flex items-center justify-center px-4 py-2 border border-red-200 rounded-lg text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 transition-colors w-full sm:w-auto"
              >
                Cancel Listing
              </button>
              <router-link
                v-else
                :to="{ name: 'farmer-cash-approvals' }"
                class="inline-flex items-center justify-center px-4 py-2 border border-moss-200 rounded-lg text-sm font-medium text-moss-600 bg-moss-50 hover:bg-moss-100 transition-colors w-full sm:w-auto"
              >
                Review Payment
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
