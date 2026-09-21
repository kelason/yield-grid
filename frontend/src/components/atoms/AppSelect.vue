<script setup>
import AppLabel from './AppLabel.vue'

defineProps({
  modelValue: { type: [String, Number], default: '' },
  id: { type: String, required: true },
  label: { type: String, required: true },
  options: { type: Array, required: true },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  error: { type: String, default: '' },
})

defineEmits(['update:modelValue'])
</script>

<template>
  <div>
    <AppLabel :for="id" :required="required">{{ label }}</AppLabel>
    <div class="mt-1 relative">
      <!-- Custom chevron -->
      <div
        class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
        aria-hidden="true"
      >
        <svg class="h-4 w-4 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M19 9l-7 7-7-7"
          />
        </svg>
      </div>
      <select
        :id="id"
        :value="modelValue"
        @change="$emit('update:modelValue', $event.target.value)"
        :required="required"
        :disabled="disabled"
        class="appearance-none block w-full px-4 py-2.5 pr-9 border rounded-xl shadow-sm bg-stone-50 text-stone-900 focus:outline-none focus:ring-2 focus:ring-moss-500 focus:border-moss-500 focus:bg-white sm:text-sm transition-all duration-200 hover:border-stone-400"
        :class="[
          error
            ? 'border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500'
            : 'border-stone-300',
          { 'bg-stone-200 cursor-not-allowed opacity-60': disabled },
        ]"
      >
        <option value="" disabled selected>Select an option</option>
        <option v-for="option in options" :key="option.value" :value="option.value">
          {{ option.label }}
        </option>
      </select>
    </div>
    <p v-if="error" class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
      <svg
        class="h-3.5 w-3.5 flex-shrink-0"
        fill="currentColor"
        viewBox="0 0 20 20"
        aria-hidden="true"
      >
        <path
          fill-rule="evenodd"
          d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
          clip-rule="evenodd"
        />
      </svg>
      {{ error }}
    </p>
  </div>
</template>
