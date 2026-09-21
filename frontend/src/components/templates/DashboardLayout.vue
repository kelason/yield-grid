<script setup>
import { RouterView } from 'vue-router'
import AppSidebar from '../organisms/AppSidebar.vue'
import { useAuthStore } from '../../stores/auth'
import { useRouter } from 'vue-router'
import AppButton from '../atoms/AppButton.vue'
import EmailVerificationBanner from '../molecules/EmailVerificationBanner.vue'

const authStore = useAuthStore()
const router = useRouter()

async function handleLogout() {
  await authStore.logout()
  router.push({ name: 'home' })
}
</script>

<template>
  <div class="h-screen flex flex-col overflow-hidden bg-stone-50 font-sans">
    <EmailVerificationBanner v-if="authStore.isAuthenticated && !authStore.isEmailVerified" />
    <div class="flex flex-1 overflow-hidden">
      <AppSidebar />

      <div class="flex flex-col w-0 flex-1 overflow-hidden">
        <!-- Mobile header -->
        <div
          class="md:hidden pl-3 pt-3 pr-3 pb-2 flex justify-between items-center bg-white border-b border-stone-200 shadow-soft"
        >
          <div class="flex items-center gap-2">
            <span class="text-xl">🌾</span>
            <span class="text-lg font-bold text-moss-600 tracking-tight font-serif">YieldGrid</span>
          </div>
          <AppButton variant="ghost" size="sm" @click="handleLogout">Logout</AppButton>
        </div>

        <!-- Main scrollable area -->
        <main class="flex-1 relative z-0 overflow-y-auto focus:outline-none">
          <!-- Page header strip -->
          <div
            class="bg-gradient-to-r from-stone-100 to-white border-b border-stone-200 h-16 flex items-center"
          >
            <div
              class="w-full max-w-7xl mx-auto px-4 sm:px-6 md:px-8 flex justify-between items-center"
            >
              <div>
                <h1 class="text-2xl font-bold text-stone-900 tracking-tight font-serif">
                  <slot name="title">Dashboard</slot>
                </h1>
                <p v-if="$slots.subtitle" class="text-sm text-stone-500 mt-0.5">
                  <slot name="subtitle" />
                </p>
              </div>
              <div class="hidden md:flex items-center gap-3">
                <div class="text-right hidden lg:block">
                  <p class="text-sm font-semibold text-stone-800">{{ authStore.user?.name }}</p>
                  <p class="text-xs text-stone-400 capitalize">{{ authStore.userRole }}</p>
                </div>
                <AppButton variant="ghost" size="sm" @click="handleLogout">Logout</AppButton>
              </div>
            </div>
          </div>

          <!-- Content -->
          <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 py-8">
            <RouterView />
          </div>
        </main>
      </div>
    </div>
  </div>
</template>
