<script setup>
import ContactForm from '../../components/organisms/ContactForm.vue'
import PageHeader from '@/components/molecules/PageHeader.vue'
</script>
<template>
  <div class="max-w-3xl mx-auto py-16 px-4 sm:px-6 lg:px-8 space-y-8">
    <PageHeader
      title="Contact Us"
      description="Have a question about YieldGrid? Send us a message."
    /><ContactForm />
  </div>
</template>
