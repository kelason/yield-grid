<template>
  <div class="bg-white rounded-2xl border border-stone-200 shadow-soft p-6 sm:p-8">
    <h2 class="font-serif text-2xl font-bold text-stone-900">
      {{ t('farmer.compat.checker_title') }}
    </h2>
    <p class="text-base text-stone-600 font-normal leading-relaxed mt-1 mb-6">
      {{ t('farmer.compat.checker_desc') }}
    </p>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label for="checker-crop-a" class="text-sm font-medium text-soil-700">
          {{ t('farmer.compat.first_crop') }}
        </label>
        <AppSelect id="checker-crop-a" v-model="cropA" data-test="crop-a-select" class="mt-1">
          <option value="">{{ t('farmer.compat.select_crop') }}</option>
          <optgroup v-for="group in cropGroups" :key="group.label" :label="group.label">
            <option v-for="crop in group.crops" :key="crop.slug" :value="crop.slug">
              {{ crop.name }}
            </option>
          </optgroup>
        </AppSelect>
      </div>
      <div>
        <label for="checker-crop-b" class="text-sm font-medium text-soil-700">
          {{ t('farmer.compat.second_crop') }}
        </label>
        <AppSelect id="checker-crop-b" v-model="cropB" data-test="crop-b-select" class="mt-1">
          <option value="">{{ t('farmer.compat.select_crop') }}</option>
          <optgroup v-for="group in cropGroups" :key="group.label" :label="group.label">
            <option v-for="crop in group.crops" :key="crop.slug" :value="crop.slug">
              {{ crop.name }}
            </option>
          </optgroup>
        </AppSelect>
      </div>
    </div>

    <div class="flex sm:justify-end mt-6">
      <AppButton
        variant="primary"
        size="lg"
        rounded="full"
        data-test="check-button"
        :disabled="!canCheck"
        :loading="checking"
        @click="emit('check-compatibility', { cropA: cropA, cropB: cropB })"
      >
        {{ t('farmer.compat.check_button') }}
      </AppButton>
    </div>

    <p v-if="error" data-test="checker-error" role="alert" class="mt-4 text-sm text-red-600">
      {{ error }}
    </p>

    <div v-if="result" class="mt-8 border-t border-stone-200 pt-6">
      <div class="flex flex-wrap items-center gap-3">
        <span
          data-test="verdict-badge"
          :class="[
            'inline-flex items-center px-4 py-1.5 rounded-full text-sm font-bold border',
            BADGE_CLASSES[result.verdict] || BADGE_CLASSES.compatible,
          ]"
        >
          {{
            VERDICT_LABEL_KEYS[result.verdict]
              ? t(VERDICT_LABEL_KEYS[result.verdict])
              : result.verdict
          }}
        </span>
        <p class="font-serif text-lg font-bold text-stone-900">
          {{ result.crop_a?.name }} × {{ result.crop_b?.name }}
        </p>
      </div>

      <div class="grid sm:grid-cols-2 gap-4 mt-6">
        <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
          <h3 class="text-sm font-medium text-soil-700 mb-2">{{ t('farmer.compat.rotation') }}</h3>
          <ul data-test="rotation-reasons" class="space-y-1">
            <li
              v-for="(reason, index) in result.rotation?.reasons || []"
              :key="index"
              class="text-sm text-stone-600 font-normal leading-relaxed"
            >
              {{ reason }}
            </li>
          </ul>
        </div>
        <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
          <h3 class="text-sm font-medium text-soil-700 mb-2">
            {{ t('farmer.compat.companions') }}
          </h3>
          <ul data-test="companion-reasons" class="space-y-1">
            <li
              v-for="(reason, index) in result.companion?.reasons || []"
              :key="index"
              class="text-sm text-stone-600 font-normal leading-relaxed"
            >
              {{ reason }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from '../atoms/AppButton.vue'
import AppSelect from '../atoms/AppSelect.vue'
const { t } = useI18n()

const props = defineProps({
  taxonomy: {
    type: Object,
    default: null,
  },
  result: {
    type: Object,
    default: null,
  },
  checking: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['check-compatibility'])

const VERDICT_LABEL_KEYS = {
  compatible: 'farmer.compat.verdict_compatible',
  caution: 'farmer.compat.verdict_caution',
  avoid: 'farmer.compat.verdict_avoid',
}
const BADGE_CLASSES = {
  compatible: 'bg-moss-100 text-moss-700 border-moss-200',
  caution: 'bg-harvest-100 text-harvest-700 border-harvest-200',
  avoid: 'bg-red-50 text-red-600 border-red-200',
}

const cropA = ref('')
const cropB = ref('')

const cropGroups = computed(() => {
  const crops = props.taxonomy?.crops || []
  const types = props.taxonomy?.types || {}
  const groups = {}

  for (const crop of crops) {
    const label = types[crop.type]?.label || crop.type
    if (!groups[label]) groups[label] = []
    groups[label].push(crop)
  }

  return Object.entries(groups).map(([label, groupCrops]) => ({ label, crops: groupCrops }))
})

const canCheck = computed(() => cropA.value !== '' && cropB.value !== '' && !props.checking)
</script>
