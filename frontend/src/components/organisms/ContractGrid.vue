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
        <div
          class="mt-auto flex justify-between items-end pt-4 border-t border-gray-50 shadow-soft border-stone-300"
        >
          <div class="h-6 bg-gray-200 rounded w-1/3"></div>
          <div class="h-8 bg-gray-200 rounded w-24"></div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="contracts.length === 0" class="text-center py-16 bg-white rounded-lg border">
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
  </div>
</template>
