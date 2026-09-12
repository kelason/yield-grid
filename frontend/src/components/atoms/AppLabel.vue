<script setup>
defineProps({
  for: { type: String, required: true },
  required: { type: Boolean, default: false }
})
</script>

<template>
  <label :for="for" class="block text-sm font-medium text-gray-700">
    <slot />
    <span v-if="required" class="text-red-500 ml-0.5">*</span>
  </label>
</template>
