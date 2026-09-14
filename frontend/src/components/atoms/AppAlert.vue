<script setup>
defineProps({
  type: {
    type: String,
    default: 'info',
    validator: (v) => ['success', 'error', 'warning', 'info'].includes(v),
  },
  dismissible: { type: Boolean, default: false },
})

const emit = defineEmits(['dismiss'])
</script>

<template>
  <div
    :class="[
      'rounded-md p-4',
      { 'bg-green-50 text-green-800': type === 'success' },
      { 'bg-red-50 text-red-800': type === 'error' },
      { 'bg-yellow-50 text-yellow-800': type === 'warning' },
      { 'bg-blue-50 text-blue-800': type === 'info' },
    ]"
  >
    <div class="flex items-start">
      <div class="flex-1 text-sm font-medium">
        <slot />
      </div>
      <button
        v-if="dismissible"
        @click="emit('dismiss')"
        class="ml-3 -mr-1 -mt-1 opacity-50 hover:opacity-100 transition-opacity"
      >
        ✕
      </button>
    </div>
  </div>
</template>
