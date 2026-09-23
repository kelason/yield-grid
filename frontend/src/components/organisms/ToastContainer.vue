<template>
  <div
    aria-live="assertive"
    class="fixed inset-0 flex items-end px-4 py-6 pointer-events-none sm:p-6 sm:pt-24 sm:items-start z-[1000]"
  >
    <div class="w-full flex flex-col items-center space-y-4 sm:items-end">
      <AppToast
        v-for="notification in notificationStore.notifications"
        :key="notification.id"
        :notification="notification"
      />
    </div>
  </div>
</template>

<script setup>
import { useNotificationStore } from '@/stores/notificationStore'
import AppToast from '@/components/atoms/AppToast.vue'

const notificationStore = useNotificationStore()
</script>
