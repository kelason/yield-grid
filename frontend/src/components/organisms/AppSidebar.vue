<script setup>
import { computed, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import AppLogo from '../atoms/AppLogo.vue'
import SidebarNavLink from '../molecules/SidebarNavLink.vue'

const authStore = useAuthStore()
const route = useRoute()
const isCollapsed = ref(false)
const openGroups = ref({})

const farmerNavigation = [
  {
    name: 'Overview',
    to: { name: 'farmer-dashboard' },
    icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    emoji: '📊',
  },
  {
    name: 'Farm',
    icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
    emoji: '🌾',
    children: [
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
        name: 'Compatibility',
        to: { name: 'crop-compatibility' },
        icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
        emoji: '🔄',
      },
    ],
  },
  {
    name: 'Sell Harvest',
    icon: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
    emoji: '🏷️',
    children: [
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
    ],
  },
  {
    name: 'Buyer Demands',
    icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
    emoji: '📋',
    children: [
      {
        name: 'Crop Demands',
        to: { name: 'farmer-browse-demands' },
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        emoji: '📋',
      },
      {
        name: 'My Offers',
        to: { name: 'farmer-offers' },
        icon: 'M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7',
        emoji: '🤝',
      },
    ],
  },
  {
    name: 'Trust Score',
    to: { name: 'credit-score' },
    icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
    emoji: '🏦',
  },
  {
    name: 'Crop Insurance',
    to: { name: 'insurance' },
    icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    emoji: '🛡️',
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
    name: 'Market',
    icon: 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
    emoji: '🛒',
    children: [
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
    ],
  },
  {
    name: 'Demands',
    icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
    emoji: '📋',
    children: [
      {
        name: 'My Demands',
        to: { name: 'buyer-demands' },
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        emoji: '📋',
      },
      {
        name: 'Post Demand',
        to: { name: 'buyer-post-demand' },
        icon: 'M12 6v6m0 0v6m0-6h6m-6 0H6',
        emoji: '➕',
      },
    ],
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

const RESTRICTED_FEATURES = [
  'Chat',
  'Community',
  'Cash Approvals',
  'Post Harvest',
  'My Offers',
  'My Demands',
  'Post Demand',
]

const navigation = computed(() => {
  const baseNav = authStore.userRole === 'buyer' ? buyerNavigation : farmerNavigation

  // Disable specific features for unverified users
  if (!authStore.isEmailVerified) {
    return baseNav
      .map((entry) => {
        if (!entry.children) {
          return RESTRICTED_FEATURES.includes(entry.name) ? null : entry
        }
        const children = entry.children.filter((child) => !RESTRICTED_FEATURES.includes(child.name))
        return children.length > 0 ? { ...entry, children } : null
      })
      .filter(Boolean)
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

function isGroupActive(group) {
  return group.children.some((child) => isActive(child.to))
}

function isGroupOpen(group) {
  if (openGroups.value[group.name] !== undefined) {
    return openGroups.value[group.name]
  }
  return isGroupActive(group)
}

function toggleGroup(group) {
  openGroups.value[group.name] = !isGroupOpen(group)
}

// When navigating to a page inside a collapsed group, expand it automatically.
watch(
  () => route.name,
  () => {
    navigation.value.forEach((entry) => {
      if (entry.children && isGroupActive(entry)) {
        openGroups.value[entry.name] = true
      }
    })
  },
)

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
          class="absolute -right-3 top-5 bg-white border border-stone-200 rounded-full p-1 text-stone-400 hover:text-stone-600 shadow-soft z-10 transition-transform duration-300"
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
            'flex items-center flex-shrink-0 border-b border-stone-300 h-16',
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
          <template v-for="entry in navigation" :key="entry.name">
            <SidebarNavLink v-if="!entry.children" :item="entry" :collapsed="isCollapsed" />
            <template v-if="entry.children && isCollapsed">
              <SidebarNavLink
                v-for="child in entry.children"
                :key="child.name"
                :item="child"
                :collapsed="true"
              />
            </template>
            <div v-if="entry.children && !isCollapsed">
              <button
                type="button"
                @click="toggleGroup(entry)"
                :aria-expanded="isGroupOpen(entry)"
                :class="[
                  isGroupActive(entry)
                    ? 'bg-moss-50 text-moss-700'
                    : 'text-stone-600 hover:bg-stone-50 hover:text-stone-900',
                  'group flex items-center w-full px-2 pr-2 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500',
                ]"
              >
                <svg
                  :class="[
                    isGroupActive(entry)
                      ? 'text-moss-600'
                      : 'text-stone-400 group-hover:text-stone-500',
                    'flex-shrink-0 h-5 w-5 mr-3',
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
                    :d="entry.icon"
                  />
                </svg>
                <span class="truncate flex-1 text-left">{{ entry.name }}</span>
                <svg
                  class="h-4 w-4 flex-shrink-0 text-stone-400 transition-transform duration-200"
                  :class="{ 'rotate-180': isGroupOpen(entry) }"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  aria-hidden="true"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                  />
                </svg>
              </button>
              <div v-show="isGroupOpen(entry)" class="ml-5 mt-1 space-y-1">
                <SidebarNavLink
                  v-for="child in entry.children"
                  :key="child.name"
                  :item="child"
                  :collapsed="false"
                />
              </div>
            </div>
          </template>
        </nav>

        <!-- User footer -->
        <div
          class="flex-shrink-0 border-t border-stone-200 p-4 bg-white transition-all duration-300"
        >
          <RouterLink
            v-if="authStore.user?.id"
            :to="{ name: 'user-profile', params: { userId: authStore.user.id } }"
            :class="['flex items-center rounded-xl', isCollapsed ? 'justify-center' : 'gap-3']"
            class="focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500"
          >
            <!-- Organic avatar -->
            <div
              class="w-9 h-9 rounded-full bg-gradient-to-br from-moss-400 to-soil-600 flex items-center justify-center text-white text-sm font-bold shadow-soft flex-shrink-0 cursor-pointer"
              :title="isCollapsed ? authStore.user?.name : ''"
            >
              {{ getInitials(authStore.user?.name) }}
            </div>
            <div v-if="!isCollapsed" class="min-w-0 overflow-hidden">
              <p
                class="text-sm font-semibold text-stone-800 truncate hover:text-moss-700 transition-colors duration-200 motion-reduce:transition-none"
              >
                {{ authStore.user?.name }}
              </p>
              <p class="text-xs font-medium text-stone-400 capitalize truncate">
                {{ authStore.userRole }}
              </p>
            </div>
          </RouterLink>
        </div>
      </div>
    </div>
  </div>
</template>
