<script setup>
const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  type: { type: String, default: 'text' },
  id: { type: String, required: true },
  placeholder: { type: String, default: '' },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  error: { type: String, default: '' },
  maxlength: { type: [Number, String], default: null },
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
    @input="onInput"
    :class="[
      'block w-full px-4 py-2.5 border rounded-xl shadow-sm placeholder-stone-400 transition-all duration-200 motion-reduce:transition-none sm:text-sm bg-stone-50',
      'focus:outline-none focus-visible:ring-2 focus-visible:ring-moss-500 focus:border-moss-500 focus:bg-white',
      error
        ? 'border-red-300 text-red-900 focus:ring-red-400 focus:border-red-400 bg-red-50'
        : 'border-stone-300 text-soil-700 hover:border-stone-400',
      { 'bg-stone-200 cursor-not-allowed opacity-60 border-stone-200': disabled },
    ]"
  />
</template>
