<script setup>
import AppCard from './AppCard.vue'
import AppSkeleton from './AppSkeleton.vue'
defineProps({ withAvatar: Boolean, withAction: Boolean })
</script>
<template>
  <AppCard aria-hidden="true">
    <div class="flex gap-4">
      <AppSkeleton v-if="withAvatar" shape="circle" class="h-10 w-10 shrink-0" />
      <div class="flex-1 space-y-4">
        <AppSkeleton class="w-3/4" />
        <AppSkeleton />
        <AppSkeleton class="w-5/6" />
        <AppSkeleton v-if="withAction" shape="block" class="h-11 w-28" />
      </div>
    </div>
  </AppCard>
</template>
