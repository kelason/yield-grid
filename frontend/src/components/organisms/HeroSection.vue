<script setup>
import { RouterLink, useRouter } from 'vue-router'
import { ArrowRightIcon } from '@heroicons/vue/24/outline'
import AppButton from '../atoms/AppButton.vue'
import AppCard from '../atoms/AppCard.vue'
import { PLATFORM_FEATURES } from '@/constants/platformFeatures'
const router = useRouter()
</script>
<template>
  <div class="bg-stone-100">
    <section
      class="mx-auto max-w-7xl px-4 pt-16 pb-20 sm:px-6 sm:pt-24 lg:px-8 lg:pt-28"
      aria-labelledby="home-heading"
    >
      <div class="max-w-3xl">
        <p class="mb-5 text-sm font-medium uppercase tracking-widest text-moss-700">
          From soil to sale
        </p>
        <h1
          id="home-heading"
          class="font-serif text-4xl sm:text-5xl font-bold tracking-tight leading-tight text-stone-900"
        >
          Smarter farming,<br /><span class="text-moss-700">better harvest.</span>
        </h1>
        <p class="mt-6 max-w-2xl text-lg text-stone-600 leading-relaxed">
          Plan crops, trade harvests, explore crop insurance and learn from your community. One
          connected workspace for farmers and the buyers they supply.
        </p>
        <div class="mt-8 flex flex-wrap items-center gap-4">
          <AppButton size="lg" @click="router.push('/auth/register')"
            >Get started<template #icon
              ><ArrowRightIcon class="h-5 w-5" aria-hidden="true" /></template></AppButton
          ><RouterLink
            to="/about"
            class="inline-flex min-h-11 items-center rounded-xl px-4 text-moss-700 font-medium transition-colors duration-200 hover:text-moss-900"
            >Learn more</RouterLink
          >
        </div>
      </div>
    </section>
    <svg
      viewBox="0 0 1440 48"
      preserveAspectRatio="none"
      class="h-8 sm:h-12 w-full text-white"
      aria-hidden="true"
    >
      <path d="M0 30C240 48 420 8 720 22C1020 36 1200 0 1440 10V48H0Z" fill="currentColor" />
    </svg>
    <section aria-labelledby="platform-heading" class="bg-white px-4 py-16 sm:px-6 lg:px-8">
      <div class="mx-auto max-w-7xl">
        <div class="max-w-2xl mb-10">
          <h2 id="platform-heading" class="font-serif text-3xl font-bold text-stone-900">
            A little clarity for every growing season.
          </h2>
          <p class="mt-4 text-base text-stone-600 leading-relaxed">
            From your first planting decision to buyer offers, payment confirmations and farm
            support, keep the work of every season connected.
          </p>
        </div>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
          <AppCard v-for="feature in PLATFORM_FEATURES" :key="feature.title" padding="p-6"
            ><component :is="feature.icon" class="mb-5 h-7 w-7 text-moss-700" aria-hidden="true" />
            <h3 class="font-serif text-2xl font-bold text-stone-900">{{ feature.title }}</h3>
            <p class="mt-3 text-base leading-relaxed text-stone-600">
              {{ feature.description }}
            </p></AppCard
          >
        </div>
      </div>
    </section>
  </div>
</template>
