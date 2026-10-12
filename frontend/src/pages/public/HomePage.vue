<script setup>
import { computed } from 'vue'
import { usePublicPageHead } from '@/composables/usePublicPageHead'
import HomePurposeSection from '@/components/organisms/HomePurposeSection.vue'
import HomeVisionSection from '@/components/organisms/HomeVisionSection.vue'
import { useI18n } from 'vue-i18n'
import { useHead } from '@unhead/vue'
import HeroSection from '../../components/organisms/HeroSection.vue'

const { t } = useI18n()

useHead({
  link: [
    {
      rel: 'preload',
      as: 'image',
      href: '/images/field-landscape-small.jpg',
      media: '(max-width: 1023px)',
      fetchpriority: 'high',
    },
    {
      rel: 'preload',
      as: 'image',
      href: '/images/field-landscape.jpg',
      media: '(min-width: 1024px)',
      fetchpriority: 'high',
    },
  ],
})
usePublicPageHead({
  title: computed(() => t('public.home.meta_title')),
  description: computed(() => t('public.home.meta_desc')),
})
</script>

<template>
  <div class="break-words bg-stone-100">
    <HeroSection />
    <HomePurposeSection />
    <HomeVisionSection />
  </div>
</template>
