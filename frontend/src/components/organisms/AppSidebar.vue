<script setup>
import { RouterLink, useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import AppLogo from '../atoms/AppLogo.vue'

const authStore = useAuthStore()
const route = useRoute()

const navigation = [
  { name: 'Overview', to: { name: 'dashboard' }, icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
  { name: 'My Farms', to: { name: 'farm-manager' }, icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z' },
]

function isActive(to) {
  return route.name === to.name || route.path.startsWith(to.path || '/not-a-path')
}
</script>

<template>
  <div class="hidden md:flex md:flex-shrink-0">
    <div class="flex flex-col w-64">
      <div class="flex flex-col h-0 flex-1 border-r border-gray-200 bg-white">
        <div class="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
          <div class="flex items-center flex-shrink-0 px-4">
            <AppLogo />
          </div>
          <nav class="mt-8 flex-1 px-2 bg-white space-y-1">
            <RouterLink
              v-for="item in navigation"
              :key="item.name"
              :to="item.to"
              :class="[isActive(item.to) ? 'bg-farm-50 text-farm-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900', 'group flex items-center px-2 py-2 text-sm font-medium rounded-md']"
            >
              <svg :class="[isActive(item.to) ? 'text-farm-600' : 'text-gray-400 group-hover:text-gray-500', 'mr-3 flex-shrink-0 h-6 w-6']" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
              </svg>
              {{ item.name }}
            </RouterLink>
          </nav>
        </div>
        <div class="flex-shrink-0 flex border-t border-gray-200 p-4">
          <div class="flex-shrink-0 w-full group block">
            <div class="flex items-center">
              <div class="ml-3">
                <p class="text-sm font-medium text-gray-700 group-hover:text-gray-900">
                  {{ authStore.user?.name }}
                </p>
                <p class="text-xs font-medium text-gray-500 group-hover:text-gray-700 capitalize">
                  {{ authStore.userRole }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
