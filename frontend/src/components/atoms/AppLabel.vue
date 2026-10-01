<script setup>
const props = defineProps({
  for: { type: String, required: true },
  required: { type: Boolean, default: false },
})
</script>

<template>
  <span class="inline-flex items-center gap-1.5">
    <label :for="props.for" class="block text-sm font-medium text-soil-700">
      <slot />
      <span v-if="required" class="text-red-500 ml-0.5" aria-hidden="true">*</span>
    </label>
    <slot name="suffix" />
  </span>
</template>
