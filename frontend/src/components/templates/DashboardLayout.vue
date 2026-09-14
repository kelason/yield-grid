<script setup>
import { RouterView } from 'vue-router'
import AppSidebar from '../organisms/AppSidebar.vue'
import { useAuthStore } from '../../stores/auth'
import { useRouter } from 'vue-router'
import AppButton from '../atoms/AppButton.vue'

const authStore = useAuthStore()
const router = useRouter()

async function handleLogout() {
  await authStore.logout()
  router.push({ name: 'home' })
}
</script>

<template>
  <div class="h-screen flex overflow-hidden bg-gray-50 font-sans">
    <AppSidebar />

    <div class="flex flex-col w-0 flex-1 overflow-hidden">
      <div
        class="md:hidden pl-1 pt-1 sm:pl-3 sm:pt-3 flex justify-between bg-white border-b border-gray-200"
      >
        <!-- Mobile header -->
        <span class="text-lg font-bold text-farm-600 p-2">YieldGrid</span>
        <AppButton variant="ghost" size="sm" @click="handleLogout" class="m-2">Logout</AppButton>
      </div>
      <main class="flex-1 relative z-0 overflow-y-auto focus:outline-none">
        <div class="py-6">
          <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 flex justify-between items-center">
            <h1 class="text-2xl font-semibold text-gray-900">
              <slot name="title">Dashboard</slot>
            </h1>
            <div class="hidden md:block">
              <AppButton variant="ghost" size="sm" @click="handleLogout">Logout</AppButton>
            </div>
          </div>
          <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 mt-6">
            <RouterView />
          </div>
        </div>
      </main>
    </div>
  </div>
</template>
