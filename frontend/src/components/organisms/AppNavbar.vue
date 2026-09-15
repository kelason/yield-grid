<script setup>
import { RouterLink } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import AppLogo from '../atoms/AppLogo.vue'
import AppButton from '../atoms/AppButton.vue'
import NavLink from '../molecules/NavLink.vue'

const authStore = useAuthStore()
</script>

<template>
  <nav class="bg-white/95 backdrop-blur-md border-b border-gray-100 shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <!-- Left: Logo + Nav -->
        <div class="flex items-center">
          <AppLogo />
          <div class="hidden sm:ml-10 sm:flex sm:space-x-8">
            <NavLink to="/" label="Home" />
            <NavLink to="/about" label="About" />
            <NavLink to="/contact" label="Contact" />
          </div>
        </div>

        <!-- Right: Auth -->
        <div class="hidden sm:ml-6 sm:flex sm:items-center space-x-3">
          <template v-if="!authStore.isAuthenticated">
            <RouterLink
              to="/auth/login"
              class="text-sm font-medium text-gray-600 hover:text-farm-600 transition-colors duration-200 px-3 py-2 rounded-md hover:bg-farm-50"
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
              class="text-sm font-medium text-farm-600 hover:text-farm-700 transition-colors duration-200 px-3 py-2 rounded-md hover:bg-farm-50"
            >
              Dashboard
            </RouterLink>
            <AppButton variant="ghost" size="sm" @click="authStore.logout()">Logout</AppButton>
          </template>
        </div>
      </div>
    </div>
  </nav>
</template>
