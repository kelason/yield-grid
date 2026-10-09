<script setup>
import { useI18n } from 'vue-i18n'
import SidebarNavLink from './SidebarNavLink.vue'
import SidebarNavGroup from './SidebarNavGroup.vue'

const { t } = useI18n()
defineProps({
  items: { type: Array, required: true },
  collapsed: Boolean,
  openGroups: { type: Object, default: () => ({}) },
})
defineEmits(['toggle', 'navigate'])
</script>
<template>
  <nav :aria-label="t('shell.dashboard_navigation')" class="space-y-2">
    <template v-for="item in items" :key="item.name">
      <SidebarNavLink
        v-if="!item.children"
        :item="item"
        :collapsed="collapsed"
        @click="$emit('navigate')"
      />
      <template v-else-if="collapsed"
        ><SidebarNavLink
          v-for="child in item.children"
          :key="child.name"
          :item="child"
          :collapsed="true"
          @click="$emit('navigate')"
      /></template>
      <SidebarNavGroup
        v-else
        :item="item"
        :open="openGroups[item.name]"
        @toggle="$emit('toggle', $event)"
        @navigate="$emit('navigate')"
      />
    </template>
  </nav>
</template>
