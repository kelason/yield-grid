<script setup>
defineProps({ shape: { type: String, default: 'line' } })
const shapes = { line: 'h-4 rounded-xl', block: 'rounded-xl', circle: 'rounded-full' }
</script>
<template>
  <span
    aria-hidden="true"
    :class="['block animate-pulse bg-stone-200 motion-reduce:animate-none', shapes[shape]]"
  />
</template>
