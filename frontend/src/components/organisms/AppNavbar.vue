<script setup>
import { RouterLink } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import AppLogo from '../atoms/AppLogo.vue'
import AppButton from '../atoms/AppButton.vue'
import NavLink from '../molecules/NavLink.vue'

const authStore = useAuthStore()
</script>

<template>
  <nav
    class="bg-gradient-to-r from-moss-900 via-moss-800 to-moss-900 shadow-soft border-b border-moss-700 sticky top-0 z-50"
  >
    <!-- Decorative organic top edge -->
    <div
      class="h-1 w-full bg-gradient-to-r from-harvest-400 via-moss-500 to-harvest-500 opacity-90"
    ></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <!-- Left: Logo + Nav -->
        <div class="flex items-center">
          <AppLogo dark />
          <div class="hidden sm:ml-10 sm:flex sm:space-x-8">
            <NavLink to="/" label="Home" dark />
            <NavLink to="/about" label="About" dark />
            <NavLink to="/contact" label="Contact" dark />
          </div>
        </div>

        <!-- Right: Auth -->
        <div class="hidden sm:ml-6 sm:flex sm:items-center space-x-3">
          <template v-if="!authStore.isAuthenticated">
            <RouterLink
              to="/auth/login"
              class="text-sm font-medium text-stone-300 hover:text-white transition-colors duration-200 px-3 py-2 rounded-xl hover:bg-white/10"
            >
              Log in
            </RouterLink>
            <AppButton
              variant="primary"
              size="sm"
              rounded="full"
              @click="$router.push('/auth/register')"
            >
              Get Started →
            </AppButton>
          </template>
          <template v-else>
            <RouterLink
              to="/dashboard"
              class="text-sm font-medium text-stone-300 hover:text-white transition-colors duration-200 px-3 py-2 rounded-xl hover:bg-white/10"
            >
              Dashboard
            </RouterLink>
            <AppButton variant="ghost-dark" size="sm" @click="authStore.logout()">Logout</AppButton>
          </template>
        </div>
      </div>
    </div>
  </nav>
</template>
