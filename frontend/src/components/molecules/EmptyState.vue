<template>
  <div class="text-center bg-white overflow-hidden shadow-sm rounded-lg border border-gray-100 p-12">
    <!-- Icon Slot -->
    <div v-if="$slots.icon" class="mx-auto h-12 w-12 text-gray-400 mb-4 flex items-center justify-center">
      <slot name="icon"></slot>
    </div>
    <div v-else class="mx-auto h-12 w-12 text-gray-400 mb-4 flex items-center justify-center">
      <!-- Default Icon (e.g. Inbox) -->
      <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
      </svg>
    </div>
    
    <h3 class="mt-2 text-lg font-medium text-gray-900">{{ title }}</h3>
    <p class="mt-1 text-sm text-gray-500 max-w-sm mx-auto">
      {{ description }}
    </p>
    
    <div v-if="actionLabel" class="mt-6">
      <AppButton
        @click="$emit('action')"
        variant="primary"
      >
        <template v-if="$slots.actionIcon" #icon>
          <slot name="actionIcon"></slot>
        </template>
        {{ actionLabel }}
      </AppButton>
    </div>
  </div>
</template>

<script setup>
import AppButton from '@/components/atoms/AppButton.vue'

defineProps({
  title: {
    type: String,
    required: true
  },
  description: {
    type: String,
    required: true
  },
  actionLabel: {
    type: String,
    default: ''
  }
})

defineEmits(['action'])
</script>
