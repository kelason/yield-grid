<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppSelect from '../atoms/AppSelect.vue'
const { t } = useI18n()
const props = defineProps({
  modelValue: { type: String, required: true },
  id: { type: String, default: 'soil-type' },
  label: { type: String, default: '' },
  required: Boolean,
  error: { type: String, default: '' },
})
defineEmits(['update:modelValue'])
const SOIL_TYPES = [
  { value: 'clay', photo: '/images/soils/clay.jpg' },
  { value: 'sandy', photo: '/images/soils/sandy.jpg' },
  { value: 'loamy', photo: '/images/soils/loamy.jpg' },
  { value: 'silt', photo: '/images/soils/silt.jpg' },
  { value: 'peat', photo: '/images/soils/peat.jpg' },
  { value: 'chalky', photo: '/images/soils/chalky.jpg' },
]

const options = computed(() =>
  SOIL_TYPES.map((soil) => ({
    value: soil.value,
    label: t(`shell.soil.${soil.value}.label`),
  })),
)
const selectedSoil = computed(() => SOIL_TYPES.find((soil) => soil.value === props.modelValue))
</script>
<template>
  <div class="space-y-3">
    <AppSelect
      :id="id"
      :label="label || t('shell.soil.type_label')"
      :required="required"
      :error="error"
      :options="options"
      :model-value="modelValue"
      @update:model-value="$emit('update:modelValue', $event)"
    />
    <details v-if="selectedSoil" class="rounded-xl border border-stone-200 bg-stone-50 px-4">
      <summary class="min-h-11 cursor-pointer py-3 text-sm font-medium text-moss-700">
        {{ t('shell.soil.about_soil', { soil: t(`shell.soil.${selectedSoil.value}.label`) }) }}
      </summary>
      <div class="pb-4 space-y-3 text-sm text-stone-600 leading-relaxed">
        <div class="flex items-center gap-3">
          <img
            :src="selectedSoil.photo"
            :alt="t('shell.soil.about_soil', { soil: t(`shell.soil.${selectedSoil.value}.label`) })"
            class="h-12 w-12 rounded-xl object-cover"
          /><strong>{{ t(`shell.soil.${selectedSoil.value}.badge`) }}</strong>
        </div>
        <p>{{ t(`shell.soil.${selectedSoil.value}.description`) }}</p>
        <p>
          <strong>{{ t('shell.soil.best_for_label') }}</strong>
          {{ t(`shell.soil.${selectedSoil.value}.best_for`) }}
        </p>
      </div>
    </details>
  </div>
</template>
