<script setup>
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import { ArrowRightIcon } from '@heroicons/vue/24/outline'

const { t } = useI18n()
const PRINCIPLES = [
  { title: 'clarity_title', description: 'clarity_desc' },
  { title: 'connection_title', description: 'connection_desc' },
  { title: 'support_principle_title', description: 'support_principle_desc' },
]
</script>
<template>
  <div class="break-words bg-stone-50">
    <section class="bg-stone-100">
      <div class="mx-auto max-w-7xl px-4 py-14 text-center sm:px-6 sm:py-20 lg:px-8 lg:py-28">
        <div class="mx-auto min-w-0 max-w-3xl">
          <p class="mb-5 text-sm font-medium uppercase tracking-widest text-moss-700">
            {{ t('public.about.eyebrow') }}
          </p>
          <h1
            class="text-balance font-serif text-3xl font-bold leading-tight tracking-tight text-stone-900 sm:text-4xl lg:text-5xl"
          >
            {{ t('public.about.title') }}
          </h1>
        </div>
      </div>
    </section>

    <section
      aria-labelledby="story-heading"
      class="mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-20 lg:px-8 lg:py-24"
    >
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:gap-16">
        <div class="min-w-0 lg:col-span-5">
          <h2
            id="story-heading"
            class="max-w-sm text-balance font-serif text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl"
          >
            {{ t('public.about.story_title') }}
          </h2>
        </div>
        <div
          class="min-w-0 space-y-5 text-base leading-relaxed text-stone-600 sm:text-lg lg:col-span-7"
        >
          <p>{{ t('public.about.story_a') }}</p>
          <p>{{ t('public.about.story_b') }}</p>
        </div>
      </div>

      <section aria-labelledby="principles-heading" class="mt-14 sm:mt-20">
        <h2 id="principles-heading" class="font-serif text-2xl font-bold text-stone-900">
          {{ t('public.about.principles_title') }}
        </h2>
        <div class="mt-7 grid grid-cols-1 gap-8 md:grid-cols-3 lg:gap-12">
          <article
            v-for="principle in PRINCIPLES"
            :key="principle.title"
            class="min-w-0 border-t border-moss-200 pt-6"
          >
            <h3 class="text-balance font-serif text-xl font-bold text-stone-900">
              {{ t(`public.about.${principle.title}`) }}
            </h3>
            <p class="mt-3 text-base leading-relaxed text-stone-600">
              {{ t(`public.about.${principle.description}`) }}
            </p>
          </article>
        </div>
      </section>
    </section>

    <section aria-labelledby="people-heading" class="bg-stone-100">
      <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
        <h2
          id="people-heading"
          class="text-balance font-serif text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl"
        >
          {{ t('public.about.people_title') }}
        </h2>
        <div class="mt-8 grid grid-cols-1 gap-8 md:grid-cols-2 lg:gap-16">
          <article class="min-w-0 border-t border-stone-300 pt-6">
            <h3 class="font-serif text-2xl font-bold text-stone-900">
              {{ t('public.about.farmers_title') }}
            </h3>
            <p class="mt-4 text-base leading-relaxed text-stone-600">
              {{ t('public.about.farmers_note') }}
            </p>
          </article>
          <article class="min-w-0 border-t border-stone-300 pt-6">
            <h3 class="font-serif text-2xl font-bold text-stone-900">
              {{ t('public.about.buyers_title') }}
            </h3>
            <p class="mt-4 text-base leading-relaxed text-stone-600">
              {{ t('public.about.buyers_note') }}
            </p>
          </article>
        </div>
        <div class="mt-8 flex justify-center sm:mt-10">
          <RouterLink
            :to="{ name: 'register' }"
            class="inline-flex min-h-12 min-w-0 max-w-full items-center justify-center gap-3 rounded-xl bg-gradient-to-br from-moss-500 to-moss-600 px-6 py-3 text-base font-medium text-white shadow-soft transition-all duration-300 hover:scale-[1.02] hover:from-moss-600 hover:to-moss-700 motion-reduce:transform-none motion-reduce:transition-none"
          >
            <span class="min-w-0">{{ t('common.auth.get_started') }}</span>
            <ArrowRightIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
          </RouterLink>
        </div>
      </div>
    </section>
  </div>
</template>
