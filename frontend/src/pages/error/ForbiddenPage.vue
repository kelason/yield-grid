<template>
  <div
    class="min-h-screen bg-stone-50 px-4 py-16 sm:px-6 sm:py-24 md:grid md:place-items-center lg:px-8"
  >
    <div class="max-w-max mx-auto">
      <main class="sm:flex">
        <p class="text-4xl font-extrabold text-moss-600 sm:text-5xl">403</p>
        <div class="sm:ml-6">
          <div class="sm:border-l sm:border-stone-200 sm:pl-6">
            <h1
              class="font-serif text-4xl font-extrabold text-stone-900 tracking-tight sm:text-5xl"
            >
              Access forbidden
            </h1>
            <p class="mt-1 text-base text-stone-500">
              You don&apos;t have permission to view this page.
            </p>
          </div>
          <div class="mt-10 flex space-x-3 sm:border-l sm:border-transparent sm:pl-6">
            <AppButton variant="primary" @click="goHome"> Go back home </AppButton>
            <AppButton variant="outline" @click="goContact"> Contact support </AppButton>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
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
