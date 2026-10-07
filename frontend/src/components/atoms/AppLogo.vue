<script setup>
import { RouterLink } from 'vue-router'
defineProps({ dark: Boolean, collapsed: Boolean })
</script>
<template>
  <RouterLink
    to="/"
    aria-label="YieldGrid home"
    :class="[
      'inline-flex min-h-11 items-center gap-3 rounded-xl transition-colors duration-200',
      dark ? 'text-stone-100' : 'text-moss-700',
    ]"
  >
    <svg
      class="h-8 w-8 shrink-0"
      viewBox="0 0 32 32"
      fill="none"
      stroke="currentColor"
      stroke-width="1.8"
      stroke-linecap="round"
      stroke-linejoin="round"
      aria-hidden="true"
    >
      <path
        d="M16 28V8M16 18C8 18 5 13 5 7c7 0 11 5 11 11ZM16 23c8 0 11-5 11-11-7 0-11 5-11 11ZM16 10c-4-3-4-6 0-8 4 2 4 5 0 8Z"
      />
    </svg>
    <span v-if="!collapsed" class="font-serif text-2xl font-bold tracking-tight">YieldGrid</span>
  </RouterLink>
</template>
