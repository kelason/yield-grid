<script setup>
defineProps({
  modelValue: { type: [String, Number], default: '' },
  type: { type: String, default: 'text' },
  id: { type: String, required: true },
  placeholder: { type: String, default: '' },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  error: { type: String, default: '' },
})

defineEmits(['update:modelValue'])
</script>

<template>
  <input
    :id="id"
    :type="type"
    :value="modelValue"
    :placeholder="placeholder"
    :required="required"
    :disabled="disabled"
    @input="$emit('update:modelValue', $event.target.value)"
    :class="[
      'block w-full px-3 py-2 border rounded-md shadow-sm placeholder-gray-400 transition-colors duration-200 sm:text-sm',
      'focus:outline-none focus:ring-farm-500 focus:border-farm-500',
      error ? 'border-red-300 text-red-900' : 'border-gray-300 text-gray-900',
      { 'bg-gray-100 cursor-not-allowed': disabled },
    ]"
  />
</template>
