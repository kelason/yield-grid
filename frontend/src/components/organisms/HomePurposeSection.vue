<script setup>
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import { ShoppingBagIcon, ChartBarIcon, SunIcon } from '@heroicons/vue/24/outline'
const { t } = useI18n()
const needs = [
  {
    id: 'market',
    icon: ShoppingBagIcon,
    source: 'World Bank',
    href: 'https://www.worldbank.org/en/results/2026/04/07/scaling-rural-transformation-in-the-philippines-connecting-farmers-to-markets-jobs-and-opportunity',
  },
  {
    id: 'price',
    icon: ChartBarIcon,
    source: 'Department of Agriculture',
    href: 'https://www.da.gov.ph/price-monitoring/',
  },
  {
    id: 'planning',
    icon: SunIcon,
    source: 'DOST-PAGASA',
    href: 'https://www.pagasa.dost.gov.ph/ten-day-regional-agri-weather',
  },
]
</script>
<template>
  <section aria-labelledby="purpose-heading" class="bg-white px-4 py-14 sm:px-6 sm:py-20 lg:px-8">
    <div class="mx-auto max-w-7xl">
      <div class="grid min-w-0 gap-8 lg:grid-cols-2 lg:gap-16">
        <div class="min-w-0">
          <p class="mb-4 text-sm font-semibold uppercase tracking-widest text-moss-700">
            {{ t('public.home.purpose_eyebrow') }}
          </p>
          <h2
            id="purpose-heading"
            class="max-w-xl font-serif text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl"
          >
            {{ t('public.home.purpose_title') }}
          </h2>
        </div>
        <div class="min-w-0">
          <p class="max-w-xl text-base leading-relaxed text-stone-600">
            {{ t('public.home.purpose_desc') }}
          </p>
          <RouterLink
            to="/features"
            class="mt-4 inline-flex min-h-11 items-center rounded-xl font-medium text-moss-700 underline underline-offset-4 transition-colors hover:text-moss-900"
            >{{ t('public.home.features_link') }}</RouterLink
          >
        </div>
      </div>
      <h3 class="mt-16 font-serif text-2xl font-bold text-stone-900">
        {{ t('public.home.needs_title') }}
      </h3>
      <div class="mt-8 divide-y divide-stone-200">
        <div
          v-for="need in needs"
          :key="need.id"
          class="grid min-w-0 gap-5 py-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)] lg:gap-16"
        >
          <div class="min-w-0">
            <component :is="need.icon" class="mb-4 h-7 w-7 text-moss-700" aria-hidden="true" />
            <h4 class="font-serif text-xl font-semibold text-stone-900">
              {{ t(`public.home.${need.id}_title`) }}
            </h4>
            <p class="mt-3 max-w-xl text-base leading-relaxed text-stone-600">
              {{ t(`public.home.${need.id}_problem`) }}
            </p>
          </div>
          <div class="min-w-0 lg:pt-11">
            <p class="max-w-2xl text-base leading-relaxed text-stone-600">
              {{ t(`public.home.${need.id}_response`) }}
            </p>
            <p class="mt-4 text-sm text-stone-600">
              {{ t('public.home.source_label') }}
              <a
                :href="need.href"
                class="inline-flex min-h-11 items-center rounded-xl text-moss-700 underline underline-offset-4 transition-colors hover:text-moss-900"
                >{{ need.source }}</a
              >
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
