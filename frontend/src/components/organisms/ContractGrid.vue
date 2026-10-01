<script setup>
import { watch } from 'vue'
import ContractCard from '../molecules/ContractCard.vue'
import { usePriceGuide } from '@/composables/usePriceGuide'
import { InboxIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
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

const { prefetchCrops } = usePriceGuide()

watch(
  () => props.contracts,
  (contracts) => {
    prefetchCrops((contracts ?? []).map((contract) => contract.crop_name))
  },
  { immediate: true },
)
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
        class="bg-white rounded-2xl border border-stone-100 shadow-soft p-5 h-64 animate-pulse flex flex-col"
      >
        <div class="h-4 bg-stone-200 rounded-xl w-3/4 mb-4"></div>
        <div class="h-3 bg-stone-200 rounded-xl w-1/2 mb-6"></div>
        <div class="space-y-3 flex-grow">
          <div class="h-3 bg-stone-200 rounded-xl w-full"></div>
          <div class="h-3 bg-stone-200 rounded-xl w-5/6"></div>
        </div>
        <div
          class="mt-auto flex justify-between items-end pt-4 border-t border-stone-50 shadow-soft border-stone-300"
        >
          <div class="h-6 bg-stone-200 rounded-xl w-1/3"></div>
          <div class="h-8 bg-stone-200 rounded-xl w-24"></div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="contracts.length === 0" class="text-center py-16 bg-white rounded-2xl border">
      <InboxIcon class="mx-auto h-12 w-12 text-stone-300" aria-hidden="true" />
      <h3 class="font-serif mt-2 text-sm font-semibold text-stone-900">No contracts found</h3>
      <p class="mt-1 text-sm text-stone-500">
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
