<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
  <RouterLink to="/" class="inline-flex items-center gap-1.5 group">
    <!-- Leaf icon -->
    <span class="text-xl leading-none">🌾</span>
    <span class="text-2xl font-bold tracking-tight text-moss-600">Yield</span
    ><span class="text-2xl font-bold tracking-tight text-soil-600">Grid</span>
  </RouterLink>
</template>
