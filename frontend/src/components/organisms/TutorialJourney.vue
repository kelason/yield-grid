<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { getGuide } from '@/constants/userGuides'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import { TUTORIAL_SCREENSHOTS } from '@/constants/tutorialJourneys'
import TutorialExample from '@/components/molecules/TutorialExample.vue'
const props = defineProps({ journey: { type: Object, required: true } })
const auth = useAuthStore()
const action = computed(() => getGuide(props.journey.steps[0].guide.topic)?.action)
const { t } = useI18n()
</script>
<template>
  <article class="min-w-0 border-t border-stone-200 pt-8">
    <h3 :id="journey.id" tabindex="-1" class="font-serif text-2xl font-bold text-stone-900">
      {{ t(journey.titleKey) }}
    </h3>
    <p class="mt-3 max-w-2xl text-base leading-relaxed text-stone-600">{{ t(journey.introKey) }}</p>
    <ol class="mt-8 space-y-10">
      <li
        v-for="(step, index) in journey.steps"
        :key="step.guide.sectionId"
        class="grid min-w-0 grid-cols-[2.5rem_minmax(0,1fr)] gap-4"
      >
        <span
          aria-hidden="true"
          class="flex h-10 w-10 items-center justify-center rounded-full bg-moss-50 font-semibold text-moss-700"
          >{{ index + 1 }}</span
        >
        <div class="min-w-0 max-w-[70ch]">
          <h4 class="font-serif text-xl font-semibold text-stone-900">
            <span class="sr-only">{{ index + 1 }}. </span>{{ t(step.titleKey) }}
          </h4>
          <p class="mt-3 text-base leading-relaxed text-stone-900">{{ t(step.instructionKey) }}</p>
          <p class="mt-3 text-base leading-relaxed text-stone-600">
            <span class="font-medium text-soil-700">{{ t('guideUi.result') }} </span
            >{{ t(step.resultKey) }}
          </p>
          <RouterLink
            :to="{
              name: 'documentation-topic',
              params: { topic: step.guide.topic },
              hash: `#${step.guide.sectionId}`,
            }"
            class="mt-3 inline-flex min-h-11 items-center rounded-xl text-sm font-medium text-moss-700 underline underline-offset-4 transition-colors hover:text-moss-900"
            >{{ t('public.how_it_works.full_guide') }}</RouterLink
          >
          <TutorialExample
            v-if="step.screenshotId"
            :example="TUTORIAL_SCREENSHOTS[step.screenshotId]"
          />
        </div>
      </li>
    </ol>
    <RouterLink
      v-if="!auth.isAuthenticated"
      to="/auth/register"
      class="mt-6 inline-flex min-h-11 items-center rounded-xl font-medium text-moss-700 underline underline-offset-4 transition-colors hover:text-moss-900"
      >{{ t('common.auth.get_started') }}</RouterLink
    >
    <RouterLink
      v-else-if="action?.role === auth.userRole"
      :to="action.to"
      class="mt-6 inline-flex min-h-11 items-center rounded-xl font-medium text-moss-700 underline underline-offset-4 transition-colors hover:text-moss-900"
      >{{ t(action.labelKey) }}</RouterLink
    >
  </article>
</template>
