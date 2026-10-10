<script setup>
import { useI18n } from 'vue-i18n'
import AppCard from '@/components/atoms/AppCard.vue'
import { PLATFORM_FEATURES } from '@/constants/platformFeatures'

const { t } = useI18n()
</script>
<template>
  <div class="bg-stone-50">
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
      <div class="max-w-3xl">
        <p class="mb-5 text-sm font-medium uppercase tracking-widest text-moss-700">
          {{ t('public.about.eyebrow') }}
        </p>
        <h1 class="font-serif text-3xl sm:text-4xl font-bold tracking-tight text-stone-900">
          {{ t('public.about.title') }}
        </h1>
        <p class="mt-6 text-lg text-stone-600 leading-relaxed">
          {{ t('public.about.lede') }}
        </p>
      </div>
      <div class="mt-16">
        <h2 class="font-serif text-3xl font-bold text-stone-900">
          {{ t('public.about.support_title') }}
        </h2>
        <p class="mt-4 max-w-2xl text-base text-stone-600 leading-relaxed">
          {{ t('public.about.support_desc') }}
        </p>
        <div class="mt-8 grid gap-5 sm:grid-cols-2">
          <AppCard v-for="feature in PLATFORM_FEATURES" :key="feature.title" padding="p-6 sm:p-8"
            ><component :is="feature.icon" class="mb-5 h-7 w-7 text-moss-700" aria-hidden="true" />
            <h3 class="font-serif text-2xl font-bold text-stone-900">{{ t(feature.titleKey) }}</h3>
            <p class="mt-3 text-base text-stone-600 leading-relaxed">
              {{ t(feature.descKey) }}
            </p></AppCard
          >
        </div>
      </div>
      <section aria-labelledby="people-heading" class="mt-16">
        <h2 id="people-heading" class="font-serif text-3xl font-bold text-stone-900">
          {{ t('public.about.people_title') }}
        </h2>
        <div class="mt-8 grid gap-5 sm:grid-cols-2">
          <AppCard padding="p-6 sm:p-8">
            <h3 class="font-serif text-2xl font-bold text-stone-900">
              {{ t('public.about.farmers_title') }}
            </h3>
            <p class="mt-3 text-base text-stone-600 leading-relaxed">
              {{ t('public.about.farmers_desc') }}
            </p>
          </AppCard>
          <AppCard padding="p-6 sm:p-8">
            <h3 class="font-serif text-2xl font-bold text-stone-900">
              {{ t('public.about.buyers_title') }}
            </h3>
            <p class="mt-3 text-base text-stone-600 leading-relaxed">
              {{ t('public.about.buyers_desc') }}
            </p>
          </AppCard>
        </div>
      </section>
    </section>
  </div>
</template>
