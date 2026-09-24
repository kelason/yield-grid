<script setup>
defineProps({
  count: {
    type: Number,
    required: true,
  },
})
</script>

<template>
  <span
    v-if="count > 0"
    class="inline-flex items-center justify-center bg-harvest-500 text-white text-[10px] font-bold rounded-full min-w-[1.25rem] h-5 px-1.5 shadow-sm"
  >
    {{ count > 99 ? '99+' : count }}
  </span>
</template>
