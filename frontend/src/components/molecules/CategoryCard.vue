<script setup>
defineProps({
  category: {
    type: Object,
    required: true,
  },
  isSelected: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['select'])
</script>

<template>
  <button
    @click="$emit('select', category.slug)"
    class="flex items-center gap-3 w-full text-left p-3 rounded-xl transition-all duration-200"
    :class="
      isSelected
        ? 'bg-moss-50 text-moss-900 shadow-inner border border-moss-200'
        : 'hover:bg-stone-50 text-stone-700 border border-transparent'
    "
  >
    <span class="text-2xl">{{ category.icon_emoji }}</span>
    <div class="flex-grow min-w-0">
      <div class="font-bold text-sm font-sans truncate">{{ category.name }}</div>
      <div class="text-xs text-stone-500 truncate" :class="{ 'text-moss-600': isSelected }">
        {{ category.description }}
      </div>
    </div>
    <div
      class="flex-shrink-0 text-xs font-semibold bg-stone-100 px-2 py-0.5 rounded-full"
      :class="{ 'bg-moss-200 text-moss-800': isSelected }"
    >
      {{ category.threads_count }}
    </div>
  </button>
</template>
