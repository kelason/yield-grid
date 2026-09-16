<script setup>
import { RouterLink } from 'vue-router'

defineProps({
  to: { type: String, required: true },
  label: { type: String, required: true },
})
</script>

<template>
  <RouterLink
    :to="to"
    class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-farm-600 hover:border-farm-400 transition-all duration-200"
    active-class="!text-farm-600 !border-farm-500 !font-semibold"
  >
    {{ label }}
  </RouterLink>
</template>
