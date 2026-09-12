<script setup>
import { useAuthStore } from '../../stores/auth'
import { useRouter } from 'vue-router'
import AppButton from '../../components/atoms/AppButton.vue'
import AppLogo from '../../components/atoms/AppLogo.vue'

const authStore = useAuthStore()
const router = useRouter()

async function handleLogout() {
  await authStore.logout()
  router.push({ name: 'home' })
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 font-sans">
    <nav class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <AppLogo />
            <span class="ml-2 text-gray-400 text-sm">Dashboard</span>
          </div>
          <div class="flex items-center gap-4">
            <span class="text-gray-700 text-sm">Hello, {{ authStore.user?.name }}</span>
            <AppButton variant="ghost" size="sm" @click="handleLogout">Logout</AppButton>
          </div>
        </div>
      </div>
    </nav>

    <main class="py-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Dashboard ({{ authStore.userRole }})</h1>
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
          <div class="px-4 py-5 sm:p-6 text-gray-500">
            Welcome to the YieldGrid application! The features for your role will be built here in future phases.
          </div>
        </div>
      </div>
    </main>
  </div>
</template>
