<script setup>
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

async function handleLogout() {
  await authStore.logout()
  router.push({ name: 'home' })
}
</script>

<template>
  <div class="min-h-screen bg-gray-100 font-sans">
    <nav class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <span class="text-2xl font-bold text-farm-600">YieldGrid App</span>
            </div>
          </div>
          <div class="flex items-center">
            <span class="text-gray-700 mr-4">Hello, {{ authStore.user?.name }}</span>
            <button @click="handleLogout" class="text-sm font-medium text-gray-500 hover:text-gray-700">Logout</button>
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
