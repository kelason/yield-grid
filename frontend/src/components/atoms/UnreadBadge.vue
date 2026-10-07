<script setup>
const DISPLAY_COUNT_MAX = 99
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
    :aria-label="`${count} unread messages`"
    class="inline-flex items-center justify-center bg-harvest-100 text-harvest-700 text-[10px] font-bold rounded-full min-w-[1.25rem] h-5 px-1.5 shadow-soft"
  >
    {{ count > DISPLAY_COUNT_MAX ? `${DISPLAY_COUNT_MAX}+` : count }}
  </span>
</template>
