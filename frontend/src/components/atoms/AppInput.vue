<script setup>
import { CONTROL_CLASSES, CONTROL_BORDER, CONTROL_ERROR } from '@/constants/forms'
const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  type: { type: String, default: 'text' },
  id: { type: String, required: true },
  placeholder: { type: String, default: '' },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  error: { type: String, default: '' },
  maxlength: { type: [Number, String], default: null },
  min: { type: [Number, String], default: null },
  max: { type: [Number, String], default: null },
})

const emit = defineEmits(['update:modelValue'])

// Browsers ignore maxlength on number inputs, so clamp here. Writing back to
// the DOM element keeps the display capped even when the sliced value equals
// the current prop (in which case Vue would skip patching :value).
const onInput = (event) => {
  let value = event.target.value
  const max = props.maxlength === null ? null : Number(props.maxlength)
  if (max !== null && value.length > max) {
    value = value.slice(0, max)
    event.target.value = value
  }
  emit('update:modelValue', value)
}
</script>

<template>
  <input
    :id="id"
    :type="type"
    :value="modelValue"
    :placeholder="placeholder"
    :required="required"
    :disabled="disabled"
    :maxlength="maxlength"
    :min="min"
    :max="max"
    @input="onInput"
    :aria-invalid="error ? 'true' : undefined"
    :class="[CONTROL_CLASSES, error ? CONTROL_ERROR : CONTROL_BORDER]"
  />
</template>
