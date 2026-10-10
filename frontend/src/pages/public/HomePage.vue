<script setup>
import { useI18n } from 'vue-i18n'
import { useHead } from '@unhead/vue'
import HeroSection from '../../components/organisms/HeroSection.vue'

const { t } = useI18n()

useHead({
  title: t('public.home.meta_title'),
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
  meta: [
    {
      name: 'description',
      content: t('public.home.meta_desc'),
    },
    {
      property: 'og:title',
      content: t('public.home.meta_title'),
    },
    {
      property: 'og:description',
      content: t('public.home.meta_desc'),
    },
  ],
})
</script>

<template>
  <div class="bg-stone-100">
    <HeroSection />
  </div>
</template>
