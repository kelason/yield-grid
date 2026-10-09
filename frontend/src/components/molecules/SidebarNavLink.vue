<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute } from 'vue-router'
import { useChatStore } from '../../stores/chatStore'
import UnreadBadge from '../atoms/UnreadBadge.vue'
import { isNavigationActive } from '@/composables/useDashboardNavigation'

const props = defineProps({
  item: { type: Object, required: true },
  collapsed: { type: Boolean, default: false },
})

const route = useRoute()
const chatStore = useChatStore()
const { t } = useI18n()

const isActive = computed(() => isNavigationActive(props.item.to, route))
</script>

<template>
  <RouterLink
    :to="item.to"
    :aria-label="t(item.labelKey)"
    :aria-current="isActive ? 'page' : undefined"
    :class="[
      isActive
        ? 'bg-moss-100 text-moss-800 border-l-[3px] border-moss-500 pl-[calc(0.5rem-3px)]'
        : 'text-stone-100 hover:bg-soil-700 hover:text-white border-l-[3px] border-transparent pl-2',
      'group flex min-h-11 items-center py-2.5 text-sm font-medium rounded-xl transition-all duration-150',
      collapsed ? 'justify-center pr-2' : 'pr-2',
    ]"
    :title="collapsed ? t(item.labelKey) : ''"
  >
    <svg
      :class="[
        isActive ? 'text-moss-600' : 'text-stone-300 group-hover:text-white',
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
    <span v-if="!collapsed" class="truncate flex-1">{{ t(item.labelKey) }}</span>
    <UnreadBadge
      v-if="item.name === 'Chat' && chatStore.totalUnread > 0 && !collapsed"
      :count="chatStore.totalUnread"
    />
  </RouterLink>
</template>
