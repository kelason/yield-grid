<template>
  <main
    id="main-content"
    tabindex="-1"
    class="min-h-screen flex items-center justify-center bg-stone-50 px-4 py-16 focus:outline-none"
  >
    <AppCard padding="p-6 sm:p-10" class="w-full max-w-2xl"
      ><p class="mb-5 text-sm font-semibold text-red-600">500</p>
      <PageHeader
        title="Server Error"
        description="Something went wrong on our end. Please try again later."
      />
      <div class="mt-8 flex flex-wrap gap-3">
        <AppButton @click="goHome">Go back home</AppButton
        ><AppButton variant="outline" @click="goContact">Contact support</AppButton>
      </div></AppCard
    >
  </main>
</template>
<script setup>
import AppCard from '@/components/atoms/AppCard.vue'
import PageHeader from '@/components/molecules/PageHeader.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import { useRouter } from 'vue-router'
const router = useRouter()
const goHome = () => router.push('/')
const goContact = () => router.push('/contact')
</script>
