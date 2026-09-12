<script setup>
import { RouterLink } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import AppLogo from '../atoms/AppLogo.vue'
import AppButton from '../atoms/AppButton.vue'
import NavLink from '../molecules/NavLink.vue'

const authStore = useAuthStore()
</script>

<template>
  <nav class="bg-white shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex items-center">
          <AppLogo />
          <div class="hidden sm:ml-8 sm:flex sm:space-x-6">
            <NavLink to="/" label="Home" />
            <NavLink to="/about" label="About" />
            <NavLink to="/contact" label="Contact" />
          </div>
        </div>
        <div class="hidden sm:ml-6 sm:flex sm:items-center space-x-4">
          <template v-if="!authStore.isAuthenticated">
            <RouterLink to="/auth/login" class="text-farm-600 hover:text-farm-700 font-medium transition-colors">Log in</RouterLink>
            <AppButton variant="primary" size="sm" rounded="full" @click="$router.push('/auth/register')">Sign up</AppButton>
          </template>
          <template v-else>
            <RouterLink to="/dashboard" class="text-farm-600 font-medium">Dashboard</RouterLink>
            <AppButton variant="ghost" size="sm" @click="authStore.logout()">Logout</AppButton>
          </template>
        </div>
      </div>
    </div>
  </nav>
</template>
