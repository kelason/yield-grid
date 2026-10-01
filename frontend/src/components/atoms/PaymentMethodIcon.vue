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
    gcash: { label: 'GCash', color: 'bg-dew-500 text-white' },
    paymaya: { label: 'Maya', color: 'bg-moss-500 text-white' },
    card: { label: 'Credit Card', color: 'bg-stone-800 text-white' },
    qrph: { label: 'QR Ph', color: 'bg-red-500 text-white' },
    grab_pay: { label: 'GrabPay', color: 'bg-moss-600 text-white' },
  }

  return map[props.method] || { label: props.method, color: 'bg-stone-200 text-stone-800' }
})
</script>

<template>
  <div
    class="inline-flex items-center justify-center rounded-full px-2 py-1 text-xs font-bold uppercase shadow-soft border border-black/5"
    :class="methodConfig.color"
    :title="methodConfig.label"
  >
    {{ methodConfig.label }}
  </div>
</template>
