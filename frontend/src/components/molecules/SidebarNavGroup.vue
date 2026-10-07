<script setup>
import { ChevronDownIcon } from '@heroicons/vue/24/outline'
import AppButton from '../atoms/AppButton.vue'
import SidebarNavLink from './SidebarNavLink.vue'
defineProps({ item: { type: Object, required: true }, open: Boolean })
defineEmits(['toggle', 'navigate'])
</script>
<template>
  <div>
    <AppButton
      :variant="item.active ? 'secondary' : 'ghost-dark'"
      class="w-full justify-start !px-3"
      :aria-expanded="open"
      @click="$emit('toggle', item)"
    >
      <svg
        class="h-5 w-5 shrink-0"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        aria-hidden="true"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
      </svg>
      <span class="flex-1 text-left">{{ item.name }}</span
      ><ChevronDownIcon
        class="h-4 w-4 transition-transform"
        :class="{ 'rotate-180': open }"
        aria-hidden="true"
      />
    </AppButton>
    <div v-show="open" class="ml-4 mt-1 space-y-1 border-l border-soil-600 pl-2">
      <SidebarNavLink
        v-for="child in item.children"
        :key="child.name"
        :item="child"
        @click="$emit('navigate')"
      />
    </div>
  </div>
</template>
