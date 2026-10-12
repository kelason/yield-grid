<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { RouterLink } from 'vue-router'

const props = defineProps({
  active: { type: Boolean, default: null },
  to: { type: [String, Object], required: true },
  label: { type: String, required: true },
  dark: { type: Boolean, default: false },
  size: { type: String, default: 'sm', validator: (v) => ['sm', 'md'].includes(v) },
})
const route = useRoute()
const isActive = computed(
  () =>
    props.active ??
    (route.path === props.to || (props.to?.name != null && route.name === props.to.name)),
)
</script>

<template>
  <RouterLink
    :to="to"
    :aria-current="isActive ? 'page' : undefined"
    class="inline-flex min-h-11 items-center rounded-xl px-1 pt-1 font-medium transition-all duration-200 border-b-2"
    :class="[
      size === 'md' ? 'text-base' : 'text-sm',
      isActive
        ? dark
          ? 'border-moss-400 text-white'
          : 'border-moss-500 text-moss-700'
        : dark
          ? 'border-transparent text-stone-300 hover:border-moss-400 hover:text-white'
          : 'border-transparent text-stone-600 hover:border-moss-300 hover:text-moss-600',
    ]"
  >
    {{ label }}
  </RouterLink>
</template>
