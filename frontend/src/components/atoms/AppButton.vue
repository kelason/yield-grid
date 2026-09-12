<script setup>
defineProps({
  variant: { type: String, default: 'primary', validator: v => ['primary', 'secondary', 'outline', 'danger', 'ghost'].includes(v) },
  size: { type: String, default: 'md', validator: v => ['sm', 'md', 'lg'].includes(v) },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  type: { type: String, default: 'button' },
  rounded: { type: String, default: 'md', validator: v => ['md', 'full'].includes(v) }
})
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="[
      'inline-flex items-center justify-center font-medium transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-farm-500',
      // Size
      { 'px-3 py-1.5 text-sm': size === 'sm' },
      { 'px-4 py-2 text-base': size === 'md' },
      { 'px-8 py-4 text-lg': size === 'lg' },
      // Rounded
      { 'rounded-md': rounded === 'md' },
      { 'rounded-full': rounded === 'full' },
      // Variants
      { 'text-white bg-gradient-to-r from-farm-500 to-farm-600 hover:from-farm-600 hover:to-farm-700 shadow-md hover:shadow-lg': variant === 'primary' },
      { 'text-farm-700 bg-farm-100 hover:bg-farm-200': variant === 'secondary' },
      { 'text-farm-700 border-2 border-farm-200 bg-transparent hover:bg-farm-50 hover:border-farm-300': variant === 'outline' },
      { 'text-white bg-red-500 hover:bg-red-600': variant === 'danger' },
      { 'text-gray-600 hover:text-gray-900 hover:bg-gray-100': variant === 'ghost' },
      // Disabled
      { 'opacity-50 cursor-not-allowed': disabled || loading },
    ]"
  >
    <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
    </svg>
    <slot />
  </button>
</template>
