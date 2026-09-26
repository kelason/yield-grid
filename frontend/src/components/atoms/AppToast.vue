<template>
  <transition
    enter-active-class="transform ease-out duration-300 transition"
    enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
    enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
    leave-active-class="transition ease-in duration-100"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="show"
      class="max-w-sm w-full bg-white shadow-organic rounded-2xl pointer-events-auto ring-1 ring-stone-200 overflow-hidden border border-stone-100 motion-reduce:transition-none motion-reduce:transform-none"
    >
      <div class="p-4">
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <!-- Icons based on type -->
            <svg
              v-if="notification.type === 'success'"
              class="h-6 w-6 text-moss-500"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            <svg
              v-else-if="notification.type === 'error'"
              class="h-6 w-6 text-red-500"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            <svg
              v-else-if="notification.type === 'warning'"
              class="h-6 w-6 text-harvest-500"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
              />
            </svg>
            <svg
              v-else
              class="h-6 w-6 text-dew-500"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
          </div>
          <div class="ml-3 w-0 flex-1 pt-0.5">
            <p class="text-sm font-semibold text-stone-900">
              {{ notification.title }}
            </p>
            <p class="mt-1 text-sm text-stone-500">
              {{ notification.message }}
            </p>
          </div>
          <div class="ml-4 flex-shrink-0 flex">
            <button
              @click="close"
              class="bg-white rounded-xl inline-flex text-stone-400 hover:text-stone-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-moss-500 transition-colors duration-150 motion-reduce:transition-none"
            >
              <span class="sr-only">Close</span>
              <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path
                  fill-rule="evenodd"
                  d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                  clip-rule="evenodd"
                />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useNotificationStore } from '@/stores/notificationStore'

const props = defineProps({
  notification: {
    type: Object,
    required: true,
  },
})

const show = ref(false)
const notificationStore = useNotificationStore()

onMounted(() => {
  show.value = true
})

function close() {
  show.value = false
  setTimeout(() => {
    notificationStore.removeNotification(props.notification.id)
  }, 300) // Match transition duration
}
</script>
