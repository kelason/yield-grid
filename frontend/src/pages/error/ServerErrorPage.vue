<template>
  <main
    id="main-content"
    tabindex="-1"
    class="min-h-screen flex items-center justify-center bg-stone-50 px-4 py-16 focus:outline-none"
  >
    <AppCard padding="p-6 sm:p-10" class="w-full max-w-2xl"
      ><p class="mb-5 text-sm font-semibold text-red-600">500</p>
      <PageHeader :title="t('errors.server_title')" :description="t('errors.server_desc')" />
      <div class="mt-8 flex flex-wrap gap-3">
        <AppButton @click="goHome">{{ t('errors.home') }}</AppButton
        ><AppButton variant="outline" @click="goContact">{{ t('errors.contact') }}</AppButton>
      </div></AppCard
    >
  </main>
</template>
<script setup>
import { useI18n } from 'vue-i18n'
import AppCard from '@/components/atoms/AppCard.vue'
import PageHeader from '@/components/molecules/PageHeader.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import { useRouter } from 'vue-router'

const { t } = useI18n()
const router = useRouter()
const goHome = () => router.push('/')
const goContact = () => router.push('/contact')
</script>
