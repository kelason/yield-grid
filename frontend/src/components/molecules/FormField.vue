<script setup>
import AppLabel from '../atoms/AppLabel.vue'
import AppInput from '../atoms/AppInput.vue'

defineProps({
  modelValue: { type: [String, Number], default: '' },
  id: { type: String, required: true },
  label: { type: String, required: true },
  type: { type: String, default: 'text' },
  placeholder: { type: String, default: '' },
  required: { type: Boolean, default: false },
  error: { type: String, default: '' },
})

defineEmits(['update:modelValue'])
</script>

<template>
  <div>
    <AppLabel :for="id" :required="required">{{ label }}</AppLabel>
    <div class="mt-1">
      <AppInput
        :id="id"
        :type="type"
        :modelValue="modelValue"
        @update:modelValue="$emit('update:modelValue', $event)"
        :placeholder="placeholder"
        :required="required"
        :error="error"
      />
    </div>
    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
  </div>
</template>
