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
      'rounded-xl p-4 border-l-4 flex items-start gap-3',
      type === 'success' && 'bg-green-50 text-green-800 border-green-500',
      type === 'error' && 'bg-red-50 text-red-800 border-red-500',
      type === 'warning' && 'bg-amber-50 text-amber-800 border-amber-500',
      type === 'info' && 'bg-blue-50 text-blue-800 border-blue-500',
    ]"
    role="alert"
  >
    <!-- Icon -->
    <span class="text-lg leading-none flex-shrink-0 mt-0.5">
      <span v-if="type === 'success'">✅</span>
      <span v-else-if="type === 'error'">⚠️</span>
      <span v-else-if="type === 'warning'">⚠️</span>
      <span v-else>ℹ️</span>
    </span>

    <div class="flex-1 text-sm font-medium leading-relaxed">
      <slot />
    </div>

    <button
      v-if="dismissible"
      @click="emit('dismiss')"
      class="ml-2 -mr-1 -mt-1 opacity-50 hover:opacity-100 transition-opacity text-lg leading-none"
      aria-label="Dismiss alert"
    >
      ✕
    </button>
  </div>
</template>
