<script setup>
import ContractCard from '../molecules/ContractCard.vue'
import { InboxIcon } from '@heroicons/vue/24/outline'

defineProps({
  contracts: {
    type: Array,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  hasMore: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['load-more', 'view-contract'])
</script>

<template>
  <div>
    <!-- Loading State -->
    <div
      v-if="loading && contracts.length === 0"
      class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"
    >
      <div
        v-for="i in 6"
        :key="i"
        class="bg-white rounded-lg border border-gray-100 shadow-sm p-5 h-64 animate-pulse flex flex-col"
      >
        <div class="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
        <div class="h-3 bg-gray-200 rounded w-1/2 mb-6"></div>
        <div class="space-y-3 flex-grow">
          <div class="h-3 bg-gray-200 rounded w-full"></div>
          <div class="h-3 bg-gray-200 rounded w-5/6"></div>
        </div>
        <div class="mt-auto flex justify-between items-end pt-4 border-t border-gray-50">
          <div class="h-6 bg-gray-200 rounded w-1/3"></div>
          <div class="h-8 bg-gray-200 rounded w-24"></div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="contracts.length === 0"
      class="text-center py-16 bg-white rounded-lg border border-gray-100 border-dashed"
    >
      <InboxIcon class="mx-auto h-12 w-12 text-gray-300" aria-hidden="true" />
      <h3 class="mt-2 text-sm font-semibold text-gray-900">No contracts found</h3>
      <p class="mt-1 text-sm text-gray-500">
        Try adjusting your search or filters to find what you're looking for.
      </p>
    </div>

    <!-- Data Grid -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <ContractCard
        v-for="contract in contracts"
        :key="contract.id"
        :contract="contract"
        @view-details="$emit('view-contract', contract)"
      />
    </div>

    <!-- Load More -->
    <div v-if="hasMore" class="mt-8 text-center">
      <button
        @click="$emit('load-more')"
        :disabled="loading"
        class="inline-flex items-center px-6 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-farm-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <svg
          v-if="loading"
          class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-500"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          ></circle>
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          ></path>
        </svg>
        {{ loading ? 'Loading...' : 'Load More Contracts' }}
      </button>
    </div>
  </div>
</template>
