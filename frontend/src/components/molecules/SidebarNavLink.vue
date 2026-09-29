<script setup>
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useChatStore } from '../../stores/chatStore'
import UnreadBadge from '../atoms/UnreadBadge.vue'

const props = defineProps({
  item: { type: Object, required: true },
  collapsed: { type: Boolean, default: false },
})

const route = useRoute()
const chatStore = useChatStore()

const isActive = computed(() => {
  const to = props.item.to
  if (to.name === 'recommendations') {
    return route.name === 'recommendations' || route.name === 'crop-recommendations'
  }
  if (to.name === 'farm-manager') {
    return route.name === 'farm-manager' || route.name === 'plot-planner'
  }
  return route.name === to.name || route.path.startsWith(to.path || '/not-a-path')
})
</script>

<template>
  <RouterLink
    :to="item.to"
    :class="[
      isActive
        ? 'bg-moss-50 text-moss-700 border-l-[3px] border-moss-500 pl-[calc(0.5rem-3px)]'
        : 'text-stone-600 hover:bg-stone-50 hover:text-stone-900 border-l-[3px] border-transparent pl-2',
      'group flex items-center py-2.5 text-sm font-medium rounded-lg transition-all duration-150',
      collapsed ? 'justify-center pr-2' : 'pr-2',
    ]"
    :title="collapsed ? item.name : ''"
  >
    <svg
      :class="[
        isActive ? 'text-moss-600' : 'text-stone-400 group-hover:text-stone-500',
        'flex-shrink-0 h-5 w-5',
        collapsed ? '' : 'mr-3',
      ]"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
      stroke="currentColor"
      aria-hidden="true"
    >
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
    </svg>
    <span v-if="!collapsed" class="truncate flex-1">{{ item.name }}</span>
    <UnreadBadge
      v-if="item.name === 'Chat' && chatStore.totalUnread > 0 && !collapsed"
      :count="chatStore.totalUnread"
    />
  </RouterLink>
</template>
