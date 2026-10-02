<script setup>
import { computed } from 'vue'
import { CREDIT_TIER_STYLES } from '@/constants/creditScoring'

const props = defineProps({
  tier: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    default: '',
  },
})

const pillClasses = computed(
  () => CREDIT_TIER_STYLES[props.tier]?.pill || CREDIT_TIER_STYLES.new.pill,
)
</script>

<template>
  <span
    data-testid="tier-badge"
    class="inline-flex items-center rounded-full border px-4 py-1.5 text-sm font-semibold transition-colors duration-200"
    :class="pillClasses"
  >
    {{ label || tier }}
  </span>
</template>
