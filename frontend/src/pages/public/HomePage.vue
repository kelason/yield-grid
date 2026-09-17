<script setup>
import { useHead } from '@unhead/vue'
import HeroSection from '../../components/organisms/HeroSection.vue'

useHead({
  title: 'YieldGrid - AI-Powered Smart Farming & Forward-Market Platform',
  meta: [
    {
      name: 'description',
      content: 'From soil to sale — let data grow your harvest. YieldGrid provides predictive agriculture tools, GIS mapping, and a forward contracts marketplace for modern farmers and buyers.',
    },
    {
      property: 'og:title',
      content: 'YieldGrid - Smart Farming Platform',
    },
    {
      property: 'og:description',
      content: 'Predictive agriculture platform combining AI crop recommendations, GIS mapping, and direct B2B forward contract sales.',
    },
    {
      property: 'og:image',
      content: '/og-image-home.jpg', // Placeholder for actual image
    }
  ],
})
</script>

<template>
  <div class="bg-white">
    <HeroSection />
  </div>
</template>
