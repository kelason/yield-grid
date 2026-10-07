<script setup>
import AppCard from '@/components/atoms/AppCard.vue'
import { PLATFORM_FEATURES } from '@/constants/platformFeatures'
</script>
<template>
  <div class="bg-stone-50">
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
      <div class="max-w-3xl">
        <p class="mb-5 text-sm font-medium uppercase tracking-widest text-moss-700">Our purpose</p>
        <h1 class="font-serif text-3xl sm:text-4xl font-bold tracking-tight text-stone-900">
          Growing together, season after season.
        </h1>
        <p class="mt-6 text-lg text-stone-600 leading-relaxed">
          YieldGrid brings crop planning and market connections into one workspace. We help farmers
          understand their growing areas and connect directly with buyers, from the first planting
          decision to the next harvest.
        </p>
      </div>
      <div class="mt-16">
        <h2 class="font-serif text-3xl font-bold text-stone-900">
          Built for everyday farming decisions
        </h2>
        <p class="mt-4 max-w-2xl text-base text-stone-600 leading-relaxed">
          Practical tools for your land, your harvest and your community.
        </p>
        <div class="mt-8 grid gap-5 sm:grid-cols-2">
          <AppCard v-for="feature in PLATFORM_FEATURES" :key="feature.title" padding="p-6 sm:p-8"
            ><component :is="feature.icon" class="mb-5 h-7 w-7 text-moss-700" aria-hidden="true" />
            <h3 class="font-serif text-2xl font-bold text-stone-900">{{ feature.title }}</h3>
            <p class="mt-3 text-base text-stone-600 leading-relaxed">
              {{ feature.description }}
            </p></AppCard
          >
        </div>
      </div>
    </section>
  </div>
</template>
