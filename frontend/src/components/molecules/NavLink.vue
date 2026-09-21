<script setup>
import { RouterLink } from 'vue-router'

defineProps({
  to: { type: [String, Object], required: true },
  label: { type: String, required: true },
  dark: { type: Boolean, default: false },
})
</script>

<template>
  <RouterLink
    :to="to"
    class="inline-flex items-center px-1 pt-1 text-sm font-medium transition-all duration-200 border-b-2 focus:outline-none"
    :class="[
      $route.path === to || $route.name === to?.name
        ? dark
          ? 'border-moss-400 text-white'
          : 'border-moss-500 text-moss-700'
        : dark
          ? 'border-transparent text-stone-300 hover:border-moss-400 hover:text-white'
          : 'border-transparent text-stone-600 hover:border-moss-300 hover:text-moss-600',
    ]"
  >
    {{ label }}
  </RouterLink>
</template>
