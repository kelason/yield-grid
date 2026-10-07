<script setup>
import { useI18n } from 'vue-i18n'
import { setInsuranceLocale } from '@/i18n'

const { t, locale } = useI18n()

const selectLocale = (next) => {
  setInsuranceLocale(next)
}

const buttonClass = (code) =>
  locale.value === code
    ? 'bg-moss-600 text-white shadow-soft'
    : 'text-stone-600 hover:text-stone-900 hover:bg-stone-100'
</script>

<template>
  <div
    class="inline-flex items-center gap-1 rounded-full bg-white border border-stone-200 shadow-soft p-1"
    role="group"
    :aria-label="t('insurance.language.label')"
  >
    <button
      type="button"
      data-testid="locale-en"
      class="min-h-11 min-w-11 px-3 py-1 rounded-full text-sm font-medium transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-moss-500"
      :class="buttonClass('en')"
      :aria-pressed="locale === 'en'"
      @click="selectLocale('en')"
    >
      {{ t('insurance.language.english') }}
    </button>
    <button
      type="button"
      data-testid="locale-tl"
      class="min-h-11 min-w-11 px-3 py-1 rounded-full text-sm font-medium transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-moss-500"
      :class="buttonClass('tl')"
      :aria-pressed="locale === 'tl'"
      @click="selectLocale('tl')"
    >
      {{ t('insurance.language.tagalog') }}
    </button>
  </div>
</template>
