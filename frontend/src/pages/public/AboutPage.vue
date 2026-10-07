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
          YieldGrid connects crop planning, harvest trading, farm support and community in one
          workspace. Farmers can care for their land and manage the next harvest; buyers can find
          crops, share their requirements and build direct relationships with the people who grow
          their food.
        </p>
      </div>
      <div class="mt-16">
        <h2 class="font-serif text-3xl font-bold text-stone-900">
          Support for the whole growing season
        </h2>
        <p class="mt-4 max-w-2xl text-base text-stone-600 leading-relaxed">
          Practical tools for planning, trading, insurance records and the conversations that keep
          farmers and buyers connected.
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
      <section aria-labelledby="people-heading" class="mt-16">
        <h2 id="people-heading" class="font-serif text-3xl font-bold text-stone-900">
          Different roles. A shared harvest.
        </h2>
        <div class="mt-8 grid gap-5 sm:grid-cols-2">
          <AppCard padding="p-6 sm:p-8">
            <h3 class="font-serif text-2xl font-bold text-stone-900">For farmers</h3>
            <p class="mt-3 text-base text-stone-600 leading-relaxed">
              Map your land, compare crop options and plan around local weather. Publish harvest
              listings, respond to buyer demands and manage payment confirmations. Keep insurance
              records and your trust profile organized, with a community to learn from.
            </p>
          </AppCard>
          <AppCard padding="p-6 sm:p-8">
            <h3 class="font-serif text-2xl font-bold text-stone-900">For buyers</h3>
            <p class="mt-3 text-base text-stone-600 leading-relaxed">
              Browse harvests and forward contracts, post the crops and quantities you need, and
              compare farmer offers. Manage purchases and delivery addresses, then use direct
              messaging to discuss the details with your trading partners.
            </p>
          </AppCard>
        </div>
      </section>
    </section>
  </div>
</template>
