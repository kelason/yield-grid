<script setup>
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import { ArrowRightIcon } from '@heroicons/vue/24/outline'
import AppCard from '@/components/atoms/AppCard.vue'
const { t } = useI18n()
const audiences = [
  { id: 'farmer', anchor: 'farmers' },
  { id: 'buyer', anchor: 'buyers' },
]
</script>
<template>
  <section
    aria-labelledby="vision-heading"
    class="bg-stone-100 px-4 py-14 sm:px-6 sm:py-20 lg:px-8"
  >
    <div class="mx-auto max-w-7xl">
      <div class="max-w-3xl">
        <p class="mb-4 text-sm font-semibold uppercase tracking-widest text-moss-700">
          {{ t('public.home.vision_eyebrow') }}
        </p>
        <h2
          id="vision-heading"
          class="font-serif text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl"
        >
          {{ t('public.home.vision_title') }}
        </h2>
        <p class="mt-6 text-base leading-relaxed text-stone-600 sm:text-lg">
          {{ t('public.home.vision') }}
        </p>
      </div>
      <div class="mt-16 border-t border-stone-200 pt-10">
        <h3 class="font-serif text-2xl font-bold text-stone-900">
          {{ t('public.home.next_title') }}
        </h3>
        <div class="mt-6 grid gap-5 md:grid-cols-2">
          <AppCard v-for="audience in audiences" :key="audience.id">
            <RouterLink
              :to="`/how-it-works#${audience.anchor}`"
              class="inline-flex min-h-11 min-w-0 max-w-full items-center gap-3 rounded-xl font-serif text-xl font-semibold text-moss-700 transition-colors hover:text-moss-900"
              >{{ t(`public.home.${audience.id}_link`)
              }}<ArrowRightIcon class="h-5 w-5 shrink-0" aria-hidden="true"
            /></RouterLink>
            <p class="mt-2 text-base leading-relaxed text-stone-600">
              {{ t(`public.home.${audience.id}_desc`) }}
            </p>
          </AppCard>
        </div>
        <RouterLink
          to="/marketplace"
          class="mt-8 inline-flex min-h-12 items-center justify-center gap-3 rounded-xl bg-gradient-to-br from-moss-500 to-moss-600 px-6 py-3 font-medium text-white shadow-soft transition-all duration-300 hover:scale-[1.02] hover:from-moss-600 hover:to-moss-700 motion-reduce:transform-none"
          >{{ t('nav.browse_market') }}<ArrowRightIcon class="h-5 w-5" aria-hidden="true"
        /></RouterLink>
      </div>
    </div>
  </section>
</template>
