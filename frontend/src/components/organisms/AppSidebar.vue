<script setup>
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import AppLogo from '../atoms/AppLogo.vue'

const authStore = useAuthStore()
const route = useRoute()

const farmerNavigation = [
  {
    name: 'Overview',
    to: { name: 'farmer-dashboard' },
    icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    emoji: '📊',
  },
  {
    name: 'My Farms',
    to: { name: 'farm-manager' },
    icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
    emoji: '🌾',
  },
  {
    name: 'Recommendations',
    to: { name: 'recommendations' },
    icon: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
    emoji: '🤖',
  },
  {
    name: 'My Contracts',
    to: { name: 'farmer-contracts' },
    icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    emoji: '📝',
  },
]

const buyerNavigation = [
  {
    name: 'Overview',
    to: { name: 'buyer-dashboard' },
    icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    emoji: '📊',
  },
  {
    name: 'Browse Market',
    to: { name: 'buyer-marketplace' },
    icon: 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
    emoji: '🛒',
  },
  {
    name: 'My Purchases',
    to: { name: 'buyer-purchases' },
    icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    emoji: '📦',
  },
]

const navigation = computed(() => {
  return authStore.userRole === 'buyer' ? buyerNavigation : farmerNavigation
})

function isActive(to) {
  if (to.name === 'recommendations') {
    return route.name === 'recommendations' || route.name === 'crop-recommendations'
  }
  if (to.name === 'farm-manager') {
    return route.name === 'farm-manager' || route.name === 'plot-planner'
  }
  return route.name === to.name || route.path.startsWith(to.path || '/not-a-path')
}

function getInitials(name) {
  if (!name) return '?'
  return name
    .split(' ')
    .map((n) => n[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
}
</script>

<template>
  <div class="hidden md:flex md:flex-shrink-0">
    <div class="flex flex-col w-64">
      <div
        class="flex flex-col h-0 flex-1 border-r border-gray-200 bg-gradient-to-b from-white via-white to-earth-50"
      >
        <!-- Logo area with gradient strip -->
        <div class="flex items-center flex-shrink-0 px-5 border-b border-gray-100 h-16">
          <AppLogo />
        </div>

        <!-- Nav section label -->
        <div class="px-4 pt-5 pb-2">
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Navigation</p>
        </div>

        <!-- Nav links -->
        <nav class="flex-1 px-3 space-y-1 overflow-y-auto">
          <RouterLink
            v-for="item in navigation"
            :key="item.name"
            :to="item.to"
            :class="[
              isActive(item.to)
                ? 'bg-farm-50 text-farm-700 border-l-2 border-farm-500 pl-[calc(0.5rem-2px)]'
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 border-l-2 border-transparent pl-2',
              'group flex items-center pr-2 py-2.5 text-sm font-medium rounded-lg transition-all duration-150',
            ]"
          >
            <svg
              :class="[
                isActive(item.to) ? 'text-farm-600' : 'text-gray-400 group-hover:text-gray-500',
                'mr-2 flex-shrink-0 h-5 w-5',
              ]"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                :d="item.icon"
              />
            </svg>
            {{ item.name }}
          </RouterLink>
        </nav>

        <!-- User footer -->
        <div class="flex-shrink-0 border-t border-gray-200 p-4 bg-white">
          <div class="flex items-center gap-3">
            <!-- Avatar -->
            <div
              class="w-9 h-9 rounded-full bg-gradient-to-br from-farm-400 to-farm-600 flex items-center justify-center text-white text-sm font-bold shadow-sm flex-shrink-0"
            >
              {{ getInitials(authStore.user?.name) }}
            </div>
            <div class="min-w-0">
              <p class="text-sm font-semibold text-gray-800 truncate">
                {{ authStore.user?.name }}
              </p>
              <p class="text-xs font-medium text-gray-400 capitalize">
                {{ authStore.userRole }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
