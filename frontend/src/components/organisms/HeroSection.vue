<script setup>
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import { ArrowRightIcon, GlobeAsiaAustraliaIcon } from '@heroicons/vue/24/outline'

const { t } = useI18n()
</script>
<template>
  <div class="break-words bg-stone-100">
    <section
      aria-labelledby="home-heading"
      class="relative isolate flex min-h-[92svh] items-center overflow-hidden bg-soil-900"
    >
      <div aria-hidden="true" class="hero-bg absolute inset-0"></div>
      <div
        aria-hidden="true"
        class="absolute inset-0 bg-gradient-to-b from-soil-900/70 via-soil-900/35 to-soil-900/75 md:bg-gradient-to-r md:from-soil-900/85 md:via-soil-900/55 md:to-soil-900/10"
      ></div>
      <div class="relative mx-auto w-full max-w-7xl px-4 py-20 sm:px-6 sm:py-24 lg:px-8">
        <div class="max-w-2xl">
          <p
            class="mb-6 flex items-center gap-3 text-xs font-semibold uppercase tracking-widest text-moss-300"
          >
            <span aria-hidden="true" class="h-px w-8 shrink-0 bg-moss-400"></span
            >{{ t('public.home.eyebrow') }}
          </p>
          <h1
            id="home-heading"
            tabindex="-1"
            class="text-balance font-serif text-4xl font-bold leading-tight tracking-tight text-white sm:text-5xl"
          >
            {{ t('public.home.title') }}
          </h1>
          <p class="mt-6 max-w-xl text-base leading-relaxed text-stone-200 sm:text-lg">
            {{ t('public.home.lede') }}
          </p>
          <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-5">
            <RouterLink
              to="/auth/register"
              class="inline-flex min-h-12 items-center justify-center gap-3 rounded-xl bg-gradient-to-br from-moss-500 to-moss-600 px-6 py-3 font-medium text-white shadow-soft transition-all duration-300 hover:scale-[1.02] hover:from-moss-600 hover:to-moss-700 motion-reduce:transform-none"
              >{{ t('public.home.cta') }}<ArrowRightIcon class="h-5 w-5" aria-hidden="true"
            /></RouterLink>
            <RouterLink
              to="/how-it-works"
              class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-4 font-medium text-stone-200 transition-colors duration-200 hover:text-white"
              >{{ t('public.home.learn') }}</RouterLink
            >
          </div>
          <p class="mt-8 flex items-center gap-2 text-sm text-stone-300">
            <GlobeAsiaAustraliaIcon class="h-5 w-5 shrink-0 text-moss-300" aria-hidden="true" />
            {{ t('public.home.community_note') }}
          </p>
        </div>
      </div>
      <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 bottom-0">
        <svg
          viewBox="0 0 1440 48"
          preserveAspectRatio="none"
          class="block h-8 w-full text-white sm:h-12"
        >
          <path
            d="M0 30C240 48 420 8 720 22C1020 36 1200 0 1440 10V48H0Z"
            fill="currentColor"
          ></path>
        </svg>
      </div>
    </section>
  </div>
</template>

<style scoped>
.hero-bg {
  background-image: url('/images/field-landscape-small.jpg');
  background-size: cover;
  background-position: center;
}

@media (min-width: 1024px) {
  .hero-bg {
    background-image: url('/images/field-landscape.jpg');
    background-attachment: fixed;
  }
}

@media (prefers-reduced-motion: reduce) {
  .hero-bg {
    background-attachment: scroll;
  }
}
</style>
