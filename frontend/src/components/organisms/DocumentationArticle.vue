<script setup>
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import { getGuide } from '@/constants/userGuides'

defineProps({ guide: { type: Object, required: true } })
const { t } = useI18n()
</script>

<template>
  <article class="min-w-0 max-w-[70ch] space-y-10 break-words">
    <header>
      <p class="mb-3 text-sm font-medium text-moss-700">
        {{ t(`guideUi.audiences.${guide.audience}`) }}
      </p>
      <h1
        id="guide-heading"
        tabindex="-1"
        class="font-serif text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl"
      >
        {{ t(guide.titleKey) }}
      </h1>
      <p class="mt-4 text-base leading-relaxed text-stone-600">{{ t(guide.summaryKey) }}</p>
    </header>
    <div
      v-if="guide.prerequisiteKeys.length"
      class="rounded-2xl border border-stone-200 bg-white p-5 shadow-soft"
    >
      <h2 class="font-serif text-xl font-semibold text-stone-900">{{ t('guideUi.before') }}</h2>
      <ul class="mt-3 list-disc space-y-2 pl-5 text-base leading-relaxed text-stone-600">
        <li v-for="key in guide.prerequisiteKeys" :key="key">{{ t(key) }}</li>
      </ul>
    </div>
    <nav :aria-label="t('guideUi.on_page')" class="border-y border-stone-200 py-5">
      <p class="mb-2 text-sm font-semibold text-soil-700">{{ t('guideUi.on_page') }}</p>
      <ul class="space-y-1">
        <li v-for="section in guide.sections" :key="section.id">
          <RouterLink
            :to="{ hash: `#${section.id}` }"
            class="inline-flex min-h-11 items-center rounded-xl text-moss-700 underline decoration-moss-200 underline-offset-4 transition-colors hover:text-moss-900"
            >{{ t(section.titleKey) }}</RouterLink
          >
        </li>
      </ul>
    </nav>
    <section v-for="section in guide.sections" :key="section.id" :aria-labelledby="section.id">
      <h2 :id="section.id" tabindex="-1" class="font-serif text-2xl font-bold text-stone-900">
        {{ t(section.titleKey) }}
      </h2>
      <p class="mt-3 text-base leading-relaxed text-stone-600">{{ t(section.introKey) }}</p>
      <ol
        class="mt-5 list-decimal space-y-5 pl-6 text-base leading-relaxed text-stone-900 marker:font-semibold marker:text-moss-700"
      >
        <li v-for="step in section.steps" :key="step.id" class="pl-1">
          <p>{{ t(step.instructionKey) }}</p>
          <p class="mt-2 text-stone-600">
            <span class="font-medium text-soil-700">{{ t('guideUi.result') }} </span
            >{{ t(step.resultKey) }}
          </p>
        </li>
      </ol>
      <p
        v-for="key in section.noteKeys"
        :key="key"
        class="mt-5 rounded-2xl bg-dew-50 p-4 text-base leading-relaxed text-dew-800"
      >
        {{ t(key) }}
      </p>
      <div v-if="section.recoveryKeys.length" class="mt-5 border-l-2 border-harvest-300 pl-4">
        <h3 class="font-serif text-lg font-semibold text-stone-900">{{ t('guideUi.if_stuck') }}</h3>
        <p
          v-for="key in section.recoveryKeys"
          :key="key"
          class="mt-2 text-base leading-relaxed text-stone-600"
        >
          {{ t(key) }}
        </p>
      </div>
    </section>
    <footer class="border-t border-stone-200 pt-6">
      <h2 class="font-serif text-2xl font-bold text-stone-900">{{ t('guideUi.next') }}</h2>
      <ul class="mt-3 space-y-1">
        <li v-for="topic in guide.relatedTopics" :key="topic">
          <RouterLink
            :to="{ name: 'documentation-topic', params: { topic } }"
            class="inline-flex min-h-11 items-center rounded-xl text-moss-700 underline underline-offset-4 transition-colors hover:text-moss-900"
            >{{ t(getGuide(topic).titleKey) }}</RouterLink
          >
        </li>
      </ul>
      <RouterLink
        to="/contact"
        class="mt-4 inline-flex min-h-11 items-center rounded-xl text-stone-600 underline underline-offset-4 transition-colors hover:text-moss-700"
        >{{ t('guideUi.contact') }}</RouterLink
      >
    </footer>
  </article>
</template>
