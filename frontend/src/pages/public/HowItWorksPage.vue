<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { usePublicPageHead } from '@/composables/usePublicPageHead'
import { TUTORIAL_JOURNEYS } from '@/constants/tutorialJourneys'
import TutorialJourney from '@/components/organisms/TutorialJourney.vue'
import BrandLogo from '@/components/atoms/BrandLogo.vue'
const { t } = useI18n()
usePublicPageHead({
  title: computed(() => `${t('public.how_it_works.title')} | YieldGrid`),
  description: computed(() => t('public.how_it_works.intro')),
})
const audiences = [
  { id: 'farmers', role: 'farmer' },
  { id: 'buyers', role: 'buyer' },
]
</script>
<template>
  <div class="bg-stone-50 px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
    <div class="mx-auto max-w-5xl break-words">
      <header class="max-w-3xl">
        <BrandLogo class="mb-3" />
        <h1
          id="tutorial-heading"
          tabindex="-1"
          class="font-serif text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl"
        >
          {{ t('public.how_it_works.title') }}
        </h1>
        <p class="mt-5 text-base leading-relaxed text-stone-600">
          {{ t('public.how_it_works.intro') }}
        </p>
        <nav :aria-label="t('guideUi.on_page')" class="mt-6 flex flex-wrap gap-x-6 gap-y-2">
          <RouterLink
            v-for="audience in audiences"
            :key="audience.id"
            :to="{ hash: `#${audience.id}` }"
            class="inline-flex min-h-11 items-center rounded-xl font-medium text-moss-700 underline underline-offset-4 transition-colors hover:text-moss-900"
            >{{ t(`public.how_it_works.${audience.id}`) }}</RouterLink
          >
        </nav>
      </header>
      <section class="mt-10 rounded-2xl border border-stone-200 bg-white p-6 shadow-soft sm:p-8">
        <h2 class="font-serif text-2xl font-bold text-stone-900">
          {{ t('public.how_it_works.start_title') }}
        </h2>
        <p class="mt-3 max-w-2xl text-base leading-relaxed text-stone-600">
          {{ t('public.how_it_works.start') }}
        </p>
        <RouterLink
          to="/documentation/getting-started"
          class="mt-3 inline-flex min-h-11 items-center rounded-xl font-medium text-moss-700 underline underline-offset-4 transition-colors hover:text-moss-900"
          >{{ t('public.how_it_works.full_guide') }}</RouterLink
        >
        <p class="mt-4 text-base leading-relaxed text-stone-600">
          {{ t('guides.profile-addresses-language.summary') }}
        </p>
        <RouterLink
          to="/documentation/profile-addresses-language"
          class="mt-2 inline-flex min-h-11 items-center rounded-xl font-medium text-moss-700 underline underline-offset-4 transition-colors hover:text-moss-900"
          >{{ t('guides.profile-addresses-language.title') }}</RouterLink
        >
      </section>
      <section
        v-for="audience in audiences"
        :key="audience.id"
        :aria-labelledby="audience.id"
        class="mt-16 space-y-8"
      >
        <h2 :id="audience.id" tabindex="-1" class="font-serif text-3xl font-bold text-stone-900">
          {{ t(`public.how_it_works.${audience.id}`) }}
        </h2>
        <TutorialJourney
          v-for="journey in TUTORIAL_JOURNEYS.filter((item) => item.role === audience.role)"
          :key="journey.id"
          :journey="journey"
        />
      </section>
    </div>
  </div>
</template>
