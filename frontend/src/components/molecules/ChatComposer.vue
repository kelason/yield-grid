<script setup>
import { ref, computed } from 'vue'
import { CHAT_CONSTANTS } from '../../constants/chat'

const emit = defineEmits(['send'])

const message = ref('')
const isSending = ref(false)

const wordCount = computed(() => {
  const words = message.value.trim().split(/\s+/)
  return message.value.trim() === '' ? 0 : words.length
})

const isOverLimit = computed(() => wordCount.value > CHAT_CONSTANTS.MESSAGE_MAX_WORDS)

const send = () => {
  if (!message.value.trim() || isOverLimit.value || isSending.value) return
  emit('send', message.value)
  message.value = ''
}
</script>

<template>
  <div class="bg-white border-t border-stone-200 p-4">
    <form @submit.prevent="send" class="flex gap-2 items-end relative">
      <div
        class="flex-grow bg-stone-50 rounded-2xl border border-stone-200 overflow-hidden focus-within:ring-2 focus-within:ring-moss-500 focus-within:border-moss-500 transition-all"
      >
        <textarea
          v-model="message"
          rows="3"
          class="w-full bg-transparent border-none focus:ring-0 resize-none py-3 px-4 text-sm min-h-24 max-h-48 overflow-y-auto"
          placeholder="Type a message..."
          @keydown.enter.prevent="send"
        ></textarea>
      </div>
      <button
        type="submit"
        :disabled="!message.trim() || isOverLimit || isSending"
        class="flex-shrink-0 bg-moss-500 hover:bg-moss-600 text-white rounded-full p-3 shadow-soft transition-transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed mb-0.5"
      >
        <svg class="w-5 h-5 ml-0.5 transform rotate-90" fill="currentColor" viewBox="0 0 20 20">
          <path
            d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"
          ></path>
        </svg>
      </button>
    </form>
    <div
      class="text-right text-[10px] mt-1 mr-14"
      :class="isOverLimit ? 'text-red-600 font-semibold' : 'text-stone-400'"
    >
      {{ wordCount }}/{{ CHAT_CONSTANTS.MESSAGE_MAX_WORDS }} words
    </div>
  </div>
</template>
