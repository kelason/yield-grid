<script setup>
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'

defineProps({
  modelValue: {
    type: String,
    required: true,
  },
  placeholder: {
    type: String,
    default: 'Search...',
  },
})

defineEmits(['update:modelValue'])
</script>

<template>
  <div class="relative w-full sm:w-96">
    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
      <MagnifyingGlassIcon class="h-4 w-4 text-stone-400" aria-hidden="true" />
    </div>
    <input
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      type="text"
      class="block w-full pl-10 pr-4 py-2.5 border border-stone-300 rounded-full leading-5 bg-stone-50 placeholder-stone-400 text-stone-900 focus:outline-none focus:ring-2 focus:ring-moss-500 focus:border-moss-500 focus:bg-white sm:text-sm transition-all duration-200 shadow-sm hover:border-stone-400"
      :placeholder="placeholder"
    />
  </div>
</template>
