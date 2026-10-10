<script setup>
import { useI18n } from 'vue-i18n'
import { RouterLink, useRouter } from 'vue-router'
import { ArrowRightIcon, GlobeAsiaAustraliaIcon } from '@heroicons/vue/24/outline'
import AppButton from '../atoms/AppButton.vue'
import AppCard from '../atoms/AppCard.vue'
import { PLATFORM_FEATURES } from '@/constants/platformFeatures'

const { t } = useI18n()
const router = useRouter()
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
            class="text-balance font-serif text-4xl font-bold leading-tight tracking-tight text-white sm:text-5xl"
          >
            {{ t('public.home.heading_a') }}<br /><span class="text-moss-300">{{
              t('public.home.heading_b')
            }}</span>
          </h1>
          <p class="mt-6 max-w-xl text-base leading-relaxed text-stone-200 sm:text-lg">
            {{ t('public.home.lede') }}
          </p>
          <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-5">
            <AppButton size="lg" class="w-full sm:w-auto" @click="router.push('/auth/register')"
              >{{ t('public.home.cta')
              }}<template #icon
                ><ArrowRightIcon class="h-5 w-5" aria-hidden="true" /></template></AppButton
            ><RouterLink
              to="/about"
              class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-4 font-medium text-stone-200 transition-colors duration-200 hover:text-white"
              >{{ t('public.home.learn') }}</RouterLink
            >
          </div>
          <p class="mt-8 flex items-center gap-2 text-sm text-stone-300">
            <GlobeAsiaAustraliaIcon class="h-5 w-5 shrink-0 text-moss-300" aria-hidden="true" />
            {{ t('public.home.community_note') }}
          </p>
          <div class="mt-10 max-w-xl border-t border-white/20 pt-5">
            <p class="text-xs font-semibold uppercase tracking-widest text-moss-300">
              {{ t('public.home.visual_eyebrow') }}
            </p>
            <p class="mt-1 font-serif text-lg text-white">{{ t('shell.tagline') }}</p>
            <p class="mt-1 text-sm leading-relaxed text-stone-300">
              {{ t('public.home.visual_note') }}
            </p>
          </div>
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
    <section
      aria-labelledby="platform-heading"
      class="bg-white px-4 py-14 sm:px-6 sm:py-20 lg:px-8"
    >
      <div class="mx-auto max-w-7xl">
        <div class="mb-10 grid grid-cols-1 items-start gap-5 lg:grid-cols-2 lg:gap-16">
          <h2
            id="platform-heading"
            class="max-w-lg text-balance font-serif text-3xl font-bold leading-tight text-stone-900 sm:text-4xl"
          >
            {{ t('public.home.platform_title') }}
          </h2>
          <p class="max-w-xl text-base leading-relaxed text-stone-600 lg:pt-1">
            {{ t('public.home.platform_desc') }}
          </p>
        </div>
        <div class="grid grid-cols-1 gap-x-8 gap-y-8 sm:grid-cols-2 lg:grid-cols-4">
          <div
            v-for="feature in PLATFORM_FEATURES"
            :key="feature.title"
            class="border-t border-stone-200 pt-6"
          >
            <div
              class="mb-5 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-moss-50"
            >
              <component :is="feature.icon" class="h-6 w-6 text-moss-700" aria-hidden="true" />
            </div>
            <h3 class="font-serif text-xl font-semibold text-stone-900">
              {{ t(feature.titleKey) }}
            </h3>
            <p class="mt-3 text-sm leading-relaxed text-stone-600">
              {{ t(feature.descKey) }}
            </p>
          </div>
        </div>
      </div>
    </section>
    <section
      aria-labelledby="home-community-heading"
      class="mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-20 lg:px-8"
    >
      <AppCard padding="p-6 sm:p-10 lg:p-12">
        <div class="grid grid-cols-1 items-center gap-8 xl:grid-cols-2 xl:gap-16">
          <div class="min-w-0">
            <p class="mb-4 text-xs font-semibold uppercase tracking-widest text-moss-700">
              {{ t('public.home.community_eyebrow') }}
            </p>
            <h2
              id="home-community-heading"
              class="text-balance font-serif text-3xl font-bold leading-tight text-stone-900 sm:text-4xl"
            >
              {{ t('public.home.community_title') }}
            </h2>
            <p class="mt-4 text-base leading-relaxed text-stone-600">
              {{ t('public.home.community_desc') }}
            </p>
          </div>
          <RouterLink
            to="/marketplace"
            class="inline-flex min-h-11 min-w-0 items-center justify-center gap-3 rounded-xl bg-gradient-to-br from-moss-500 to-moss-600 px-6 py-4 font-medium text-white shadow-soft transition-all duration-300 hover:scale-[1.02] hover:from-moss-600 hover:to-moss-700 hover:shadow-organic motion-reduce:transform-none xl:justify-self-end"
            ><span class="min-w-0">{{ t('nav.browse_market') }}</span
            ><ArrowRightIcon class="h-5 w-5 shrink-0" aria-hidden="true"
          /></RouterLink>
        </div>
      </AppCard>
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
