<script setup>
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import AppLogo from '../atoms/AppLogo.vue'
const { t } = useI18n()
</script>
<template>
  <footer
    class="border-t border-soil-700 bg-gradient-to-br from-soil-900 to-soil-800 text-stone-100"
  >
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
      <div class="flex flex-col justify-between gap-8 sm:flex-row">
        <div class="max-w-md">
          <AppLogo dark />
          <p class="mt-4 text-sm leading-relaxed text-stone-300">
            {{ t('shell.footer.tagline') }}
          </p>
        </div>
        <nav :aria-label="t('shell.footer.nav_label')" class="flex flex-wrap items-start gap-6">
          <RouterLink
            to="/about"
            class="inline-flex min-h-11 items-center rounded-xl text-sm text-stone-100 transition-colors hover:text-white"
            >{{ t('shell.footer.about') }}</RouterLink
          ><RouterLink
            to="/contact"
            class="inline-flex min-h-11 items-center rounded-xl text-sm text-stone-100 transition-colors hover:text-white"
            >{{ t('shell.footer.contact') }}</RouterLink
          >
        </nav>
      </div>
      <p class="mt-8 border-t border-soil-600 pt-6 text-sm text-stone-300">
        {{ t('shell.footer.rights', { year: new Date().getFullYear() }) }}
      </p>
    </div>
  </footer>
</template>
