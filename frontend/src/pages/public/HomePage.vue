<script setup>
import { useI18n } from 'vue-i18n'
import { useHead } from '@unhead/vue'
import HeroSection from '../../components/organisms/HeroSection.vue'

const { t } = useI18n()

useHead({
  title: t('public.home.meta_title'),
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
