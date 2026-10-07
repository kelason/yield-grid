<script setup>
import { computed, useId } from 'vue'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
import AppInput from '../atoms/AppInput.vue'
defineOptions({ inheritAttrs: false })
const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Search...' },
  id: { type: String, default: '' },
  label: { type: String, default: 'Search' },
  hiddenLabel: { type: Boolean, default: true },
})
defineEmits(['update:modelValue'])
const generatedId = useId()
const controlId = computed(() => props.id || `search-${generatedId}`)
</script>
<template>
  <div :class="$attrs.class" class="w-full">
    <label
      :for="controlId"
      :class="hiddenLabel ? 'sr-only' : 'mb-1 block text-sm font-medium text-soil-700'"
      >{{ label }}</label
    >
    <div class="relative">
      <MagnifyingGlassIcon
        class="pointer-events-none absolute left-4 top-3 z-10 h-5 w-5 text-stone-600"
        aria-hidden="true"
      />
      <AppInput
        :id="controlId"
        v-bind="Object.fromEntries(Object.entries($attrs).filter(([key]) => key !== 'class'))"
        :model-value="modelValue"
        type="search"
        :placeholder="placeholder"
        class="!rounded-full pl-11"
        @update:model-value="$emit('update:modelValue', $event)"
      />
    </div>
  </div>
</template>
