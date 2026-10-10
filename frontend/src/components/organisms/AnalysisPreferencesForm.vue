<template>
  <div class="bg-white border border-stone-200 rounded-2xl shadow-soft p-6">
    <h2 class="font-serif text-2xl font-bold text-stone-900">
      {{ t('farmer.analysis.focus_title') }}
    </h2>
    <p class="mt-1 text-base text-stone-600 font-normal leading-relaxed">
      {{ t('farmer.analysis.focus_desc') }}
    </p>

    <p v-if="!taxonomy" class="mt-6 text-sm text-stone-500 animate-pulse" aria-live="polite">
      {{ t('farmer.analysis.loading_options') }}
    </p>

    <div v-else class="mt-6 space-y-6">
      <div>
        <p class="text-sm font-medium text-soil-700 mb-2">
          {{ t('farmer.analysis.crop_types') }}
        </p>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="(type, slug) in taxonomy.types"
            :key="slug"
            type="button"
            data-test="type-chip"
            :aria-pressed="selectedTypes.includes(slug)"
            :class="[
              'inline-flex items-center px-4 py-1.5 rounded-full text-sm font-medium transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-moss-500',
              selectedTypes.includes(slug)
                ? 'bg-moss-600 text-white shadow-soft'
                : 'bg-stone-100 text-stone-700 hover:bg-stone-200',
            ]"
            @click="toggleType(slug)"
          >
            {{ type.label }}
          </button>
        </div>
      </div>

      <div v-for="(type, typeSlug) in visibleTypes" :key="typeSlug">
        <p class="text-sm font-medium text-soil-700 mb-2">{{ type.label }}</p>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="(subtype, subtypeSlug) in type.subtypes"
            :key="subtypeSlug"
            type="button"
            data-test="subtype-chip"
            :aria-pressed="selectedSubtypes.includes(subtypeSlug)"
            :class="[
              'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-moss-500',
              selectedSubtypes.includes(subtypeSlug)
                ? 'bg-moss-100 text-moss-800 border-moss-300'
                : 'bg-white text-stone-600 border-stone-200 hover:border-stone-300',
            ]"
            @click="toggleSubtype(subtypeSlug)"
          >
            {{ subtype.label }} ({{ subtype.crop_count }})
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label for="pref-irrigation" class="text-sm font-medium text-soil-700">
            {{ t('farmer.analysis.irrigation') }}
          </label>
          <AppSelect
            id="pref-irrigation"
            v-model="irrigation"
            data-test="irrigation-select"
            class="mt-1"
          >
            <option value="">{{ t('farmer.analysis.no_preference') }}</option>
            <option
              v-for="level in taxonomy.irrigation_levels"
              :key="level.value"
              :value="level.value"
            >
              {{ level.label }}
            </option>
          </AppSelect>
        </div>
        <div>
          <label for="pref-goal" class="text-sm font-medium text-soil-700">
            {{ t('farmer.analysis.goal') }}
          </label>
          <AppSelect id="pref-goal" v-model="goal" data-test="goal-select" class="mt-1">
            <option value="">{{ t('farmer.analysis.no_preference') }}</option>
            <option v-for="option in taxonomy.goals" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </AppSelect>
        </div>
      </div>

      <div class="flex sm:justify-end">
        <AppButton
          variant="primary"
          size="lg"
          rounded="full"
          data-test="run-analysis"
          @click="emit('request-analysis', preferences())"
        >
          {{ t('farmer.analysis.run_button') }}
        </AppButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from '../atoms/AppButton.vue'
import AppSelect from '../atoms/AppSelect.vue'
const { t } = useI18n()

const props = defineProps({
  taxonomy: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['update:preferences', 'request-analysis'])

const selectedTypes = ref([])
const selectedSubtypes = ref([])
const irrigation = ref('')
const goal = ref('')

const visibleTypes = computed(() => {
  if (!props.taxonomy) return {}
  if (selectedTypes.value.length === 0) return props.taxonomy.types
  return Object.fromEntries(
    Object.entries(props.taxonomy.types).filter(([slug]) => selectedTypes.value.includes(slug)),
  )
})

function preferences() {
  return {
    produce_types: [...selectedTypes.value],
    subtypes: [...selectedSubtypes.value],
    irrigation: irrigation.value || null,
    goal: goal.value || null,
  }
}

function toggleType(slug) {
  if (selectedTypes.value.includes(slug)) {
    selectedTypes.value = selectedTypes.value.filter((type) => type !== slug)
    const dropped = Object.keys(props.taxonomy.types[slug].subtypes)
    selectedSubtypes.value = selectedSubtypes.value.filter((subtype) => !dropped.includes(subtype))
  } else {
    selectedTypes.value = [...selectedTypes.value, slug]
  }
}

function toggleSubtype(slug) {
  if (selectedSubtypes.value.includes(slug)) {
    selectedSubtypes.value = selectedSubtypes.value.filter((subtype) => subtype !== slug)
  } else {
    selectedSubtypes.value = [...selectedSubtypes.value, slug]
  }
}

watch([selectedTypes, selectedSubtypes, irrigation, goal], () => {
  emit('update:preferences', preferences())
})
</script>
