<script setup>
defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (v) =>
      ['primary', 'secondary', 'outline', 'danger', 'ghost', 'ghost-dark', 'harvest'].includes(v),
  },
  size: { type: String, default: 'md', validator: (v) => ['sm', 'md', 'lg'].includes(v) },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  type: { type: String, default: 'button' },
  rounded: { type: String, default: 'xl', validator: (v) => ['md', 'xl', 'full'].includes(v) },
})

const variantClasses = {
  primary:
    'text-white bg-gradient-to-br from-moss-500 to-moss-600 hover:from-moss-600 hover:to-moss-700 shadow-soft hover:shadow-organic hover:scale-[1.02] active:scale-[0.99]',
  secondary:
    'text-moss-700 bg-moss-100 hover:bg-moss-200 hover:scale-[1.02] active:scale-[0.99] border border-moss-200',
  outline:
    'text-moss-700 border-2 border-moss-300 bg-transparent hover:bg-moss-50 hover:border-moss-500 hover:scale-[1.02] active:scale-[0.99]',
  danger:
    'text-white bg-gradient-to-br from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 shadow-sm hover:shadow-md hover:scale-[1.02] active:scale-[0.99]',
  ghost: 'text-stone-600 hover:text-stone-900 hover:bg-stone-100',
  'ghost-dark': 'text-stone-300 hover:text-white hover:bg-white/10',
  harvest:
    'text-white bg-gradient-to-br from-harvest-500 to-harvest-600 hover:from-harvest-600 hover:to-harvest-700 shadow-harvest-glow hover:scale-[1.02] active:scale-[0.99]',
}

const sizeClasses = {
  sm: 'px-3 py-1.5 text-sm gap-1.5',
  md: 'px-5 py-2.5 text-base gap-2',
  lg: 'px-8 py-4 text-lg gap-2.5',
}

const roundedClasses = {
  md: 'rounded-md',
  xl: 'rounded-xl',
  full: 'rounded-full',
}
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="[
      'inline-flex items-center justify-center font-medium transition-all duration-300 motion-reduce:transition-none motion-reduce:transform-none motion-reduce:hover:scale-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-moss-500',
      sizeClasses[size],
      roundedClasses[rounded],
      variantClasses[variant],
      disabled || loading ? 'opacity-50 cursor-not-allowed !transform-none !shadow-none' : '',
    ]"
  >
    <svg
      v-if="loading"
      class="animate-spin h-4 w-4 flex-shrink-0"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
      aria-hidden="true"
    >
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path
        class="opacity-75"
        fill="currentColor"
        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
      />
    </svg>
    <slot />
  </button>
</template>
