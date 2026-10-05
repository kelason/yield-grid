<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/atoms/AppButton.vue'
import AppCard from '@/components/atoms/AppCard.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import FormField from '@/components/molecules/FormField.vue'
import { INSURANCE_LIMITS, INSURANCE_PROGRAMS, INSURANCE_SEASONS } from '@/constants/insurance'

const props = defineProps({
  profile: { type: Object, default: null },
  enrollments: { type: Array, required: true },
  plots: { type: Array, required: true },
})

const emit = defineEmits(['create-enrollment'])

const { t } = useI18n()

const plotId = ref('')
const program = ref(INSURANCE_PROGRAMS[0])
const season = ref(INSURANCE_SEASONS[0])
const seasonYear = ref(new Date().getFullYear())
const notes = ref('')
const formError = ref('')

const ADVANCED_STATUSES = ['documents_ready', 'submitted_to_mao', 'active', 'expired']
const SUBMITTED_STATUSES = ['submitted_to_mao', 'active', 'expired']

const isRsbsaDone = computed(() => props.profile?.rsbsa_status === 'registered')
const isSelectDone = computed(() => props.enrollments.length > 0)
const isReviewDone = computed(() =>
  props.enrollments.some((item) => ADVANCED_STATUSES.includes(item.status)),
)
const isPackDone = computed(() => props.enrollments.some((item) => item.pack_status === 'ready'))
const isSubmitDone = computed(() =>
  props.enrollments.some((item) => SUBMITTED_STATUSES.includes(item.status)),
)

const steps = computed(() => [
  { key: 'rsbsa', done: isRsbsaDone.value },
  { key: 'select', done: isSelectDone.value },
  { key: 'review', done: isReviewDone.value },
  { key: 'pack', done: isPackDone.value },
  { key: 'submit', done: isSubmitDone.value },
])

const plotOptions = computed(() =>
  props.plots.map((plot) => ({
    value: plot.id,
    label: `${plot.name} (${plot.calculated_area} ha)`,
  })),
)
const programOptions = computed(() =>
  INSURANCE_PROGRAMS.map((value) => ({ value, label: t(`insurance.guide.program_${value}`) })),
)
const seasonOptions = computed(() =>
  INSURANCE_SEASONS.map((value) => ({ value, label: t(`insurance.guide.season_${value}`) })),
)

const submit = () => {
  formError.value = ''
  const year = Number(seasonYear.value)

  if (!plotId.value) {
    formError.value = t('insurance.guide.step_select_text')
    return
  }
  if (
    !Number.isInteger(year) ||
    year < INSURANCE_LIMITS.SEASON_YEAR_MIN ||
    year > INSURANCE_LIMITS.SEASON_YEAR_MAX
  ) {
    formError.value = `${t('insurance.guide.season_year_label')}: ${INSURANCE_LIMITS.SEASON_YEAR_MIN}–${INSURANCE_LIMITS.SEASON_YEAR_MAX}`
    return
  }
  if (notes.value.length > INSURANCE_LIMITS.NOTES_MAX_LENGTH) {
    formError.value = `${t('insurance.guide.notes_label')}: max ${INSURANCE_LIMITS.NOTES_MAX_LENGTH}`
    return
  }

  emit('create-enrollment', {
    plot_id: Number(plotId.value),
    program: program.value,
    season: season.value,
    season_year: year,
    notes: notes.value,
  })
}
</script>

<template>
  <AppCard>
    <h2 class="font-serif text-2xl font-bold text-stone-900">{{ t('insurance.guide.title') }}</h2>
    <p class="mt-1 text-base text-stone-600 font-light leading-relaxed">
      {{ t('insurance.guide.subtitle') }}
    </p>

    <ol class="mt-4 space-y-3">
      <li
        v-for="(step, index) in steps"
        :key="step.key"
        :data-testid="`guide-step-${step.key}`"
        class="flex items-start gap-3 rounded-2xl border p-3 transition-all duration-300"
        :class="
          step.done ? 'guide-step-done border-moss-300 bg-moss-50' : 'border-stone-200 bg-stone-50'
        "
      >
        <span
          class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-sm font-semibold"
          :class="step.done ? 'bg-moss-600 text-white' : 'bg-stone-200 text-stone-600'"
          aria-hidden="true"
        >
          {{ step.done ? '✓' : index + 1 }}
        </span>
        <span>
          <span class="block text-base font-medium text-stone-900">{{
            t(`insurance.guide.step_${step.key}_title`)
          }}</span>
          <span class="block text-sm text-stone-600 font-light">{{
            t(`insurance.guide.step_${step.key}_text`)
          }}</span>
        </span>
      </li>
    </ol>

    <form class="mt-5 space-y-4" @submit.prevent="submit">
      <AppSelect
        id="guide-plot"
        v-model="plotId"
        data-testid="guide-plot"
        :label="t('insurance.guide.plot_label')"
        :options="plotOptions"
        required
      />
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <AppSelect
          id="guide-program"
          v-model="program"
          data-testid="guide-program"
          :label="t('insurance.guide.program_label')"
          :options="programOptions"
          required
        />
        <AppSelect
          id="guide-season"
          v-model="season"
          data-testid="guide-season"
          :label="t('insurance.guide.season_label')"
          :options="seasonOptions"
          required
        />
        <FormField
          id="guide-year"
          v-model="seasonYear"
          data-testid="guide-year"
          type="number"
          :label="t('insurance.guide.season_year_label')"
          :min="INSURANCE_LIMITS.SEASON_YEAR_MIN"
          :max="INSURANCE_LIMITS.SEASON_YEAR_MAX"
          required
        />
      </div>
      <div>
        <FormField
          id="guide-notes"
          v-model="notes"
          data-testid="guide-notes"
          type="text"
          :label="t('insurance.guide.notes_label')"
          :placeholder="t('insurance.guide.notes_placeholder')"
          :maxlength="INSURANCE_LIMITS.NOTES_MAX_LENGTH"
        />
        <p class="mt-1 text-sm text-stone-600 font-light">
          {{ notes.length }} / {{ INSURANCE_LIMITS.NOTES_MAX_LENGTH }}
        </p>
      </div>
      <p
        v-if="formError"
        data-testid="guide-error"
        class="text-sm font-medium text-red-600"
        role="alert"
      >
        {{ formError }}
      </p>
      <AppButton type="submit" variant="primary" data-testid="guide-submit">
        {{ t('insurance.guide.start_enrollment') }}
      </AppButton>
    </form>
  </AppCard>
</template>
