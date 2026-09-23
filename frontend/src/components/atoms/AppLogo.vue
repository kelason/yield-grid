<script setup>
import { RouterLink } from 'vue-router'

defineProps({
  dark: { type: Boolean, default: false },
  collapsed: { type: Boolean, default: false },
})
</script>

<template>
  <RouterLink to="/" class="inline-flex items-center gap-1.5 group">
    <!-- Leaf icon -->
    <span class="text-xl leading-none">🌾</span>
    <template v-if="!collapsed">
      <span :class="['text-2xl font-bold tracking-tight', dark ? 'text-moss-400' : 'text-moss-600']"
        >Yield</span
      ><span :class="['text-2xl font-bold tracking-tight', dark ? 'text-stone-100' : 'text-soil-600']"
        >Grid</span
      >
    </template>
  </RouterLink>
</template>
