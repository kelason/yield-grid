<script setup>
import { useI18n } from 'vue-i18n'
import ContactForm from '../../components/organisms/ContactForm.vue'
import PageHeader from '@/components/molecules/PageHeader.vue'

const { t } = useI18n()
</script>
<template>
  <div class="max-w-3xl mx-auto py-16 px-4 sm:px-6 lg:px-8 space-y-8">
    <PageHeader
      :title="t('public.contact.title')"
      :description="t('public.contact.description')"
    /><ContactForm />
  </div>
</template>
