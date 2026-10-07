<script setup>
import { computed } from 'vue'
import AppLabel from '../atoms/AppLabel.vue'
import AppInput from '../atoms/AppInput.vue'
import AppTextarea from '../atoms/AppTextarea.vue'
import { useControlAttrs } from '@/composables/useControlAttrs'
defineOptions({ inheritAttrs: false })
const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  id: { type: String, required: true },
  label: { type: String, required: true },
  type: { type: String, default: 'text' },
  placeholder: { type: String, default: '' },
  required: Boolean,
  disabled: Boolean,
  multiline: Boolean,
  error: { type: String, default: '' },
  hint: { type: String, default: '' },
  maxlength: { type: [Number, String], default: null },
  min: { type: [Number, String], default: null },
  max: { type: [Number, String], default: null },
})
defineEmits(['update:modelValue'])
const { wrapperAttrs, controlAttrs } = useControlAttrs()
const describedBy = computed(
  () =>
    [
      controlAttrs.value['aria-describedby'],
      props.hint && `${props.id}-hint`,
      props.error && `${props.id}-error`,
      props.multiline && props.maxlength != null && `${props.id}-counter`,
    ]
      .filter(Boolean)
      .join(' ') || undefined,
)
</script>
<template>
  <div v-bind="wrapperAttrs">
    <AppLabel :for="id" :required="required"
      >{{ label }}<template #suffix><slot name="labelSuffix" /></template
    ></AppLabel>
    <component
      :is="multiline ? AppTextarea : AppInput"
      v-bind="controlAttrs"
      :id="id"
      :type="multiline ? undefined : type"
      :model-value="modelValue ?? ''"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :error="error"
      :maxlength="maxlength"
      :min="multiline ? undefined : min"
      :max="multiline ? undefined : max"
      :aria-describedby="describedBy"
      class="mt-1"
      @update:model-value="$emit('update:modelValue', $event)"
    />
    <div class="mt-1 flex items-start justify-between gap-3 text-sm">
      <p v-if="hint" :id="`${id}-hint`" class="text-stone-600">{{ hint }}</p>
      <p
        v-if="multiline && maxlength != null"
        :id="`${id}-counter`"
        class="ml-auto shrink-0 tabular-nums text-stone-600"
      >
        {{ String(modelValue ?? '').length }} / {{ maxlength }}
      </p>
    </div>
    <p v-if="error" :id="`${id}-error`" class="mt-1 text-sm text-red-600">{{ error }}</p>
  </div>
</template>
