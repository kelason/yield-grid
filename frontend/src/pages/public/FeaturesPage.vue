<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { PLATFORM_FEATURES, FEATURE_GROUPS } from '@/constants/platformFeatures'
import { usePublicPageHead } from '@/composables/usePublicPageHead'
import AppCard from '@/components/atoms/AppCard.vue'
import BrandLogo from '@/components/atoms/BrandLogo.vue'
const { t } = useI18n()
usePublicPageHead({
  title: computed(() => `${t('public.features.title')} | YieldGrid`),
  description: computed(() => t('public.features.intro')),
})
</script>
<template>
  <div class="break-words bg-stone-50 px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
    <div class="mx-auto max-w-7xl">
      <header class="max-w-3xl">
        <BrandLogo class="mb-3" />
        <h1
          id="features-heading"
          tabindex="-1"
          class="font-serif text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl"
        >
          {{ t('public.features.title') }}
        </h1>
        <p class="mt-5 text-base leading-relaxed text-stone-600">
          {{ t('public.features.intro') }}
        </p>
      </header>
      <section
        v-for="group in FEATURE_GROUPS"
        :key="group.id"
        :aria-labelledby="group.id"
        class="mt-14"
      >
        <h2 :id="group.id" tabindex="-1" class="font-serif text-2xl font-bold text-stone-900">
          {{ t(group.titleKey) }}
        </h2>
        <div class="mt-6 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
          <AppCard
            v-for="feature in PLATFORM_FEATURES.filter((item) => item.groupId === group.id)"
            :key="feature.id"
          >
            <component :is="feature.icon" aria-hidden="true" class="mb-5 h-7 w-7 text-moss-700" />
            <p class="mb-2 text-sm font-medium text-soil-700">{{ t(feature.audienceKey) }}</p>
            <h3 class="font-serif text-xl font-semibold text-stone-900">
              {{ t(feature.titleKey) }}
            </h3>
            <p class="mt-3 text-base leading-relaxed text-stone-600">{{ t(feature.descKey) }}</p>
            <p class="mt-4 text-sm leading-relaxed text-stone-600">
              {{ t(`public.features.limits.${feature.id}`) }}
            </p>
            <RouterLink
              :to="{
                name: 'documentation-topic',
                params: { topic: feature.guide.topic },
                hash: `#${feature.guide.sectionId}`,
              }"
              class="mt-4 inline-flex min-h-11 items-center rounded-xl font-medium text-moss-700 underline underline-offset-4 transition-colors hover:text-moss-900"
              >{{ t('public.how_it_works.full_guide') }}</RouterLink
            >
          </AppCard>
        </div>
      </section>
    </div>
  </div>
</template>
