<script setup>
defineProps({
  isOnline: {
    type: Boolean,
    default: false,
  },
})
</script>

<template>
  <div
    class="w-2.5 h-2.5 rounded-full border border-white shadow-sm"
    :class="isOnline ? 'bg-moss-400 animate-pulse' : 'bg-stone-300'"
    :title="isOnline ? 'Online' : 'Offline'"
  ></div>
</template>
