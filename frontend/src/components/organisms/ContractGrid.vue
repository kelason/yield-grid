<script setup>
import { watch } from 'vue'
import ContractCard from '../molecules/ContractCard.vue'
import { usePriceGuide } from '@/composables/usePriceGuide'
import LoadingState from '../molecules/LoadingState.vue'
import SkeletonCard from '../atoms/SkeletonCard.vue'
import EmptyState from '../molecules/EmptyState.vue'

const CONTRACT_SKELETON_COUNT = 6

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

defineEmits(['load-more', 'view-contract', 'report'])

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
    <LoadingState
      v-if="loading && !contracts?.length"
      label="Loading contracts"
      class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"
      ><SkeletonCard v-for="n in CONTRACT_SKELETON_COUNT" :key="n" withAction
    /></LoadingState>
    <EmptyState
      v-else-if="!contracts?.length"
      title="No contracts found"
      description="Try adjusting your search or filters to find what you're looking for."
    />

    <!-- Data Grid -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <ContractCard
        v-for="contract in contracts"
        :key="contract.id"
        :contract="contract"
        @view-details="$emit('view-contract', contract)"
        @report="$emit('report', $event)"
      />
    </div>
  </div>
</template>
