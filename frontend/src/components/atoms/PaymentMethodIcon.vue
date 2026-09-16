<script setup>
import { computed } from 'vue'

const props = defineProps({
  method: {
    type: String,
    required: true,
  },
})

const methodConfig = computed(() => {
  const map = {
    gcash: { label: 'GCash', color: 'bg-blue-500 text-white' },
    paymaya: { label: 'Maya', color: 'bg-green-500 text-white' },
    card: { label: 'Credit Card', color: 'bg-gray-800 text-white' },
    qrph: { label: 'QR Ph', color: 'bg-red-500 text-white' },
    grab_pay: { label: 'GrabPay', color: 'bg-green-600 text-white' },
  }

  return map[props.method] || { label: props.method, color: 'bg-gray-200 text-gray-800' }
})
</script>

<template>
  <div
    class="inline-flex items-center justify-center rounded px-2 py-1 text-xs font-bold uppercase shadow-sm border border-black/5"
    :class="methodConfig.color"
    :title="methodConfig.label"
  >
    {{ methodConfig.label }}
  </div>
</template>
