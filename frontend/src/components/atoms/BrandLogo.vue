<script setup>
import { BRAND_MARK_PATH } from '@/constants/brand'

defineProps({ dark: Boolean, collapsed: Boolean })
</script>

<template>
  <span
    role="img"
    aria-label="YieldGrid"
    :class="['inline-flex min-w-0 items-center gap-3', dark ? 'text-stone-100' : 'text-moss-700']"
  >
    <svg class="h-8 w-8 shrink-0" viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
      <path :d="BRAND_MARK_PATH" />
    </svg>
    <span v-if="!collapsed" class="truncate font-serif text-2xl font-bold tracking-tight"
      >YieldGrid</span
    >
  </span>
</template>
