<script setup>
defineProps({
  isOwn: {
    type: Boolean,
    default: false,
  },
  body: {
    type: String,
    required: true,
  },
  time: {
    type: String,
    required: true,
  },
})
</script>

<template>
  <div
    class="flex flex-col max-w-[80%] min-w-[3rem]"
    :class="isOwn ? 'items-end self-end' : 'items-start self-start'"
  >
    <div
      class="px-4 py-2 text-sm shadow-soft"
      :class="
        isOwn
          ? 'bg-moss-100 text-stone-800 rounded-2xl rounded-br-sm'
          : 'bg-white border border-stone-200 text-stone-800 rounded-2xl rounded-bl-sm'
      "
    >
      {{ body }}
    </div>
    <span class="text-[10px] text-stone-400 mt-1 px-1">{{ time }}</span>
  </div>
</template>
