<script setup>
import { CONTROL_CLASSES, CONTROL_BORDER, CONTROL_ERROR } from '@/constants/forms'
const props = defineProps({
  id: { type: String, required: true },
  modelValue: { type: String, default: '' },
  maxlength: { type: [Number, String], default: null },
  minlength: { type: [Number, String], default: null },
  rows: { type: [Number, String], default: 4 },
  disabled: Boolean,
  required: Boolean,
  error: { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue'])
function onInput(event) {
  const value =
    props.maxlength == null
      ? event.target.value
      : event.target.value.slice(0, Number(props.maxlength))
  event.target.value = value
  emit('update:modelValue', value)
}
</script>
<template>
  <textarea
    :id="id"
    :value="modelValue"
    :rows="rows"
    :maxlength="maxlength"
    :minlength="minlength"
    :disabled="disabled"
    :required="required"
    :aria-invalid="error ? 'true' : undefined"
    :class="[CONTROL_CLASSES, error ? CONTROL_ERROR : CONTROL_BORDER]"
    @input="onInput"
  />
</template>
