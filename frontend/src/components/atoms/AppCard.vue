<script setup>
defineProps({
  padding: {
    type: String,
    default: 'p-6'
  }
})
</script>

<template>
  <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-100">
    <div :class="padding">
      <slot></slot>
    </div>
  </div>
</template>
