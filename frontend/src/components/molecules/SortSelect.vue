<script setup>
import { computed, useId } from 'vue'
import AppSelect from '../atoms/AppSelect.vue'
const props = defineProps({
  modelValue: { type: String, required: true },
  options: { type: Array, required: true },
  id: { type: String, default: '' },
  label: { type: String, default: 'Sort by' },
  hiddenLabel: { type: Boolean, default: true },
})
defineEmits(['update:modelValue'])
const generatedId = useId()
const controlId = computed(() => props.id || `sort-${generatedId}`)
</script>
<template>
  <div class="w-full sm:w-auto">
    <label
      :for="controlId"
      :class="hiddenLabel ? 'sr-only' : 'mb-1 block text-sm font-medium text-soil-700'"
      >{{ label }}</label
    >
    <AppSelect
      :id="controlId"
      :model-value="modelValue"
      :options="options"
      @update:model-value="$emit('update:modelValue', $event)"
    />
  </div>
</template>
