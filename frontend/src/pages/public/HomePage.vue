<script setup>
import { useHead } from '@unhead/vue'
import HeroSection from '../../components/organisms/HeroSection.vue'
const PAGE_TITLE = 'YieldGrid - Farming, Marketplace & Community'
const PAGE_DESCRIPTION =
  'Plan crops, trade harvests, post buyer demands, track insurance and build your farmer profile with YieldGrid’s connected farming community.'

useHead({
  title: PAGE_TITLE,
  meta: [
    {
      name: 'description',
      content: PAGE_DESCRIPTION,
    },
    {
      property: 'og:title',
      content: PAGE_TITLE,
    },
    {
      property: 'og:description',
      content: PAGE_DESCRIPTION,
    },
  ],
})
</script>

<template>
  <div class="bg-stone-100">
    <HeroSection />
  </div>
</template>
