<script setup>
import { computed } from 'vue'
import AppLabel from './AppLabel.vue'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'
import { useControlAttrs } from '@/composables/useControlAttrs'
import { CONTROL_CLASSES, CONTROL_BORDER, CONTROL_ERROR } from '@/constants/forms'
defineOptions({ inheritAttrs: false })
const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  id: { type: String, required: true },
  label: { type: String, default: '' },
  options: { type: Array, default: null },
  required: Boolean,
  disabled: Boolean,
  error: { type: String, default: '' },
})
defineEmits(['update:modelValue'])
const { t } = useI18n()
const { wrapperAttrs, controlAttrs } = useControlAttrs()
const describedBy = computed(
  () =>
    [controlAttrs.value['aria-describedby'], props.error && `${props.id}-error`]
      .filter(Boolean)
      .join(' ') || undefined,
)
</script>
<template>
  <div v-bind="wrapperAttrs">
    <AppLabel v-if="label" :for="id" :required="required">{{ label }}</AppLabel>
    <div class="relative" :class="{ 'mt-1': label }">
      <select
        v-bind="controlAttrs"
        :id="id"
        :value="modelValue"
        :required="required"
        :disabled="disabled"
        :aria-invalid="error ? 'true' : undefined"
        :aria-describedby="describedBy"
        :class="[CONTROL_CLASSES, 'appearance-none pr-10', error ? CONTROL_ERROR : CONTROL_BORDER]"
        @change="$emit('update:modelValue', $event.target.value)"
      >
        <slot v-if="$slots.default" />
        <template v-else
          ><option value="" disabled>{{ t('shell.select_option') }}</option>
          <option v-for="option in options ?? []" :key="option.value" :value="option.value">
            {{ option.label }}
          </option></template
        >
      </select>
      <ChevronDownIcon
        class="pointer-events-none absolute right-3 top-3 h-5 w-5 text-stone-600"
        aria-hidden="true"
      />
    </div>
    <p v-if="error" :id="`${id}-error`" class="mt-1 text-sm text-red-600">{{ error }}</p>
  </div>
</template>
