<template>
  <main
    id="main-content"
    tabindex="-1"
    class="min-h-screen flex items-center justify-center bg-stone-50 px-4 py-16 focus:outline-none"
  >
    <AppCard padding="p-6 sm:p-10" class="w-full max-w-2xl"
      ><p class="mb-5 text-sm font-semibold text-moss-700">403</p>
      <PageHeader
        title="Access forbidden"
        description="You don’t have permission to view this page."
      />
      <div class="mt-8 flex flex-wrap gap-3">
        <AppButton @click="goHome">Go back home</AppButton
        ><AppButton variant="outline" @click="goContact">Contact support</AppButton>
      </div></AppCard
    >
  </main>
</template>
<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import AppCard from '@/components/atoms/AppCard.vue'
import PageHeader from '@/components/molecules/PageHeader.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const homeTarget = computed(() => {
  if (authStore.userRole === 'buyer') return { name: 'buyer-dashboard' }
  if (authStore.userRole === 'farmer') return { name: 'farmer-dashboard' }
  return '/'
})

const goHome = () => router.push(homeTarget.value)
const goContact = () => router.push('/contact')
</script>
