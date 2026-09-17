<script setup>
defineProps({
  modelValue: {
    type: String,
    required: true,
  },
  options: {
    type: Array,
    required: true,
  },
})

defineEmits(['update:modelValue'])
</script>

<template>
  <div class="w-full sm:w-auto">
    <select
      :value="modelValue"
      @change="$emit('update:modelValue', $event.target.value)"
      class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-farm-500 focus:border-farm-500 sm:text-sm rounded-xl shadow-sm"
    >
      <option v-for="option in options" :key="option.value" :value="option.value">
        {{ option.label }}
      </option>
    </select>
  </div>
</template>
