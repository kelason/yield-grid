<script setup>
import { computed, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useChatStore } from '../../stores/chatStore'
import AppLogo from '../atoms/AppLogo.vue'
import UnreadBadge from '../atoms/UnreadBadge.vue'

const authStore = useAuthStore()
const chatStore = useChatStore()
const route = useRoute()
const isCollapsed = ref(false)

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
  {
    name: 'Post Harvest',
    to: { name: 'farmer-new-listing' },
    icon: 'M12 6v6m0 0v6m0-6h6m-6 0H6',
    emoji: '➕',
  },
  {
    name: 'Cash Approvals',
    to: { name: 'farmer-cash-approvals' },
    icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
    emoji: '💵',
  },
  {
    name: 'Community',
    to: { name: 'community-forum' },
    icon: 'M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z',
    emoji: '🤝',
  },
  {
    name: 'Chat',
    to: { name: 'chat' },
    icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    emoji: '💬',
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
  {
    name: 'Community',
    to: { name: 'community-forum' },
    icon: 'M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z',
    emoji: '🤝',
  },
  {
    name: 'Chat',
    to: { name: 'chat' },
    icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    emoji: '💬',
  },
]

const navigation = computed(() => {
  const baseNav = authStore.userRole === 'buyer' ? buyerNavigation : farmerNavigation

  // Disable specific features for unverified users
  if (!authStore.isEmailVerified) {
    const restrictedFeatures = ['Chat', 'Community', 'Cash Approvals', 'Post Harvest']
    return baseNav.filter((item) => !restrictedFeatures.includes(item.name))
  }

  return baseNav
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
  <div class="hidden md:flex md:flex-shrink-0 relative">
    <div
      :class="[
        'flex flex-col transition-all duration-300 ease-in-out',
        isCollapsed ? 'w-20' : 'w-64',
      ]"
    >
      <div
        class="flex flex-col h-0 flex-1 border-r border-stone-200 bg-gradient-to-b from-white via-white to-stone-50 relative"
      >
        <!-- Toggle button -->
        <button
          @click="isCollapsed = !isCollapsed"
          class="absolute -right-3 top-5 bg-white border border-stone-200 rounded-full p-1 text-stone-400 hover:text-stone-600 shadow-sm z-10 transition-transform duration-300"
          :class="isCollapsed ? 'rotate-180' : ''"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 19l-7-7 7-7"
            ></path>
          </svg>
        </button>

        <!-- Logo area -->
        <div
          :class="[
            'flex items-center flex-shrink-0 border-b border-stone-100 h-16',
            isCollapsed ? 'justify-center px-0' : 'px-5',
          ]"
        >
          <AppLogo :collapsed="isCollapsed" />
        </div>

        <!-- Nav section label -->
        <div
          class="pt-5 pb-2 transition-all duration-300"
          :class="isCollapsed ? 'px-0 text-center' : 'px-4'"
        >
          <p
            v-if="!isCollapsed"
            class="text-[10px] font-bold text-stone-400 uppercase tracking-widest whitespace-nowrap"
          >
            Navigation
          </p>
          <div v-else class="h-[15px]"></div>
        </div>

        <!-- Nav links -->
        <nav class="flex-1 px-3 space-y-1 overflow-y-auto overflow-x-hidden">
          <RouterLink
            v-for="item in navigation"
            :key="item.name"
            :to="item.to"
            :class="[
              isActive(item.to)
                ? 'bg-moss-50 text-moss-700 border-l-[3px] border-moss-500 pl-[calc(0.5rem-3px)]'
                : 'text-stone-600 hover:bg-stone-50 hover:text-stone-900 border-l-[3px] border-transparent pl-2',
              'group flex items-center py-2.5 text-sm font-medium rounded-lg transition-all duration-150',
              isCollapsed ? 'justify-center pr-2' : 'pr-2',
            ]"
            :title="isCollapsed ? item.name : ''"
          >
            <svg
              :class="[
                isActive(item.to) ? 'text-moss-600' : 'text-stone-400 group-hover:text-stone-500',
                'flex-shrink-0 h-5 w-5',
                isCollapsed ? '' : 'mr-3',
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
            <span v-if="!isCollapsed" class="truncate flex-1">{{ item.name }}</span>
            <UnreadBadge
              v-if="item.name === 'Chat' && chatStore.totalUnread > 0 && !isCollapsed"
              :count="chatStore.totalUnread"
            />
          </RouterLink>
        </nav>

        <!-- User footer -->
        <div
          class="flex-shrink-0 border-t border-stone-200 p-4 bg-white transition-all duration-300"
        >
          <div :class="['flex items-center', isCollapsed ? 'justify-center' : 'gap-3']">
            <!-- Organic avatar -->
            <div
              class="w-9 h-9 rounded-full bg-gradient-to-br from-moss-400 to-soil-600 flex items-center justify-center text-white text-sm font-bold shadow-sm flex-shrink-0 cursor-pointer"
              :title="isCollapsed ? authStore.user?.name : ''"
            >
              {{ getInitials(authStore.user?.name) }}
            </div>
            <div v-if="!isCollapsed" class="min-w-0 overflow-hidden">
              <p class="text-sm font-semibold text-stone-800 truncate">
                {{ authStore.user?.name }}
              </p>
              <p class="text-xs font-medium text-stone-400 capitalize truncate">
                {{ authStore.userRole }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
