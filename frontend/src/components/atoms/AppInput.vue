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
      'block w-full px-4 py-2.5 border rounded-xl shadow-sm placeholder-stone-400 transition-all duration-200 sm:text-sm bg-stone-50',
      'focus:outline-none focus:ring-2 focus:ring-moss-500 focus:border-moss-500 focus:bg-white',
      error
        ? 'border-red-300 text-red-900 focus:ring-red-400 focus:border-red-400 bg-red-50'
        : 'border-stone-300 text-soil-700 hover:border-stone-400',
      { 'bg-stone-200 cursor-not-allowed opacity-60 border-stone-200': disabled },
    ]"
  />
</template>
