<script setup>
import { RouterView, RouterLink } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()
</script>

<template>
  <div class="min-h-screen flex flex-col font-sans">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <RouterLink to="/" class="flex-shrink-0 flex items-center">
              <span class="text-2xl font-bold text-farm-600">YieldGrid</span>
            </RouterLink>
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <RouterLink to="/" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">Home</RouterLink>
              <RouterLink to="/about" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">About</RouterLink>
              <RouterLink to="/contact" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">Contact</RouterLink>
            </div>
          </div>
          <div class="hidden sm:ml-6 sm:flex sm:items-center space-x-4">
            <template v-if="!authStore.isAuthenticated">
              <RouterLink to="/auth/login" class="text-farm-600 hover:text-farm-700 font-medium">Log in</RouterLink>
              <RouterLink to="/auth/register" class="bg-farm-500 hover:bg-farm-600 text-white px-4 py-2 rounded-md font-medium transition-colors">Sign up</RouterLink>
            </template>
            <template v-else>
              <RouterLink to="/dashboard" class="text-farm-600 font-medium">Dashboard</RouterLink>
              <button @click="authStore.logout" class="text-gray-500 hover:text-gray-700">Logout</button>
            </template>
          </div>
        </div>
      </div>
    </nav>

    <!-- Page Content -->
    <main class="flex-grow">
      <RouterView />
    </main>

    <!-- Footer -->
    <footer class="bg-earth-900 text-earth-100 py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center">
        <div class="mb-4 md:mb-0">
          <span class="text-2xl font-bold text-white">YieldGrid</span>
          <p class="mt-2 text-sm text-earth-300">Empowering farmers and buyers.</p>
        </div>
        <div class="flex space-x-6 text-sm">
          <RouterLink to="/about" class="hover:text-white transition-colors">About Us</RouterLink>
          <RouterLink to="/contact" class="hover:text-white transition-colors">Contact</RouterLink>
          <a href="#" class="hover:text-white transition-colors">Privacy Policy</a>
        </div>
      </div>
    </footer>
  </div>
</template>
