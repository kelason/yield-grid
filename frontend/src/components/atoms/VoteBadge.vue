<script setup>
defineProps({
  score: {
    type: Number,
    required: true,
  },
  userVote: {
    type: Number,
    default: 0, // 1 for upvote, -1 for downvote, 0 for none
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['vote'])
</script>

<template>
  <div
    class="flex flex-col items-center justify-center bg-stone-50 rounded-xl p-1 shadow-inner border border-stone-100 min-w-[2.5rem]"
  >
    <button
      @click="!disabled && $emit('vote', 1)"
      :disabled="disabled"
      class="p-1 rounded transition-colors"
      :class="[
        userVote === 1 ? 'text-moss-500' : 'text-stone-400',
        disabled ? 'opacity-50 cursor-not-allowed' : 'hover:bg-stone-200',
      ]"
      title="Upvote"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M5 15l7-7 7 7"
        ></path>
      </svg>
    </button>
    <span
      class="text-sm font-bold font-sans py-0.5"
      :class="{
        'text-moss-600': score > 0,
        'text-red-600': score < 0,
        'text-stone-700': score === 0,
      }"
    >
      {{ score }}
    </span>
    <button
      @click="!disabled && $emit('vote', -1)"
      :disabled="disabled"
      class="p-1 rounded transition-colors"
      :class="[
        userVote === -1 ? 'text-red-500' : 'text-stone-400',
        disabled ? 'opacity-50 cursor-not-allowed' : 'hover:bg-stone-200',
      ]"
      title="Downvote"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M19 9l-7 7-7-7"
        ></path>
      </svg>
    </button>
  </div>
</template>
