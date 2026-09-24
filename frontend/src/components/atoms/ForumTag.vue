<script setup>
defineProps({
  tag: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <span
    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-dew-50 text-dew-700 border border-dew-100 hover:bg-dew-100 transition-colors duration-200 cursor-pointer"
  >
    {{ tag.name }}
  </span>
</template>
