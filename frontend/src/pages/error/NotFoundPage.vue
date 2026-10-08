<template>
  <main
    id="main-content"
    tabindex="-1"
    class="min-h-screen flex items-center justify-center bg-stone-50 px-4 py-16 focus:outline-none"
  >
    <AppCard padding="p-6 sm:p-10" class="w-full max-w-2xl"
      ><p class="mb-5 text-sm font-semibold text-moss-700">404</p>
      <PageHeader
        title="Page not found"
        description="Please check the URL in the address bar and try again."
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
import { roleHomeTarget } from '@/constants/roles'

const router = useRouter()
const authStore = useAuthStore()

const homeTarget = computed(() => {
  if (!authStore.isAuthenticated) return '/'
  return roleHomeTarget(authStore.userRole)
})

const goHome = () => router.push(homeTarget.value)
const goContact = () => router.push('/contact')
</script>
