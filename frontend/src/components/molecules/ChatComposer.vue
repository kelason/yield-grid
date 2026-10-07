<script setup>
import { ref, computed, watch } from 'vue'
import { PaperAirplaneIcon } from '@heroicons/vue/24/outline'
import AppTextarea from '../atoms/AppTextarea.vue'
import AppButton from '../atoms/AppButton.vue'
import { CHAT_CONSTANTS } from '../../constants/chat'
const props = defineProps({ isSending: Boolean, sentMessage: { type: String, default: '' } })
const emit = defineEmits(['send'])
const message = ref('')
const messageLength = computed(() => (message.value || '').length)
const isOverLimit = computed(() => messageLength.value > CHAT_CONSTANTS.MESSAGE_MAX_LENGTH)
watch(
  () => props.sentMessage,
  (sent) => {
    if (sent && sent === message.value) message.value = ''
  },
)
function send() {
  if (!message.value.trim() || isOverLimit.value || props.isSending) return
  emit('send', message.value)
}
function handleEnter(event) {
  if (event.shiftKey || event.isComposing) return
  event.preventDefault()
  send()
}
</script>
<template>
  <form @submit.prevent="send" class="bg-white border-t border-stone-200 p-4 space-y-2">
    <label for="chat-message" class="text-sm font-medium text-soil-700">Message</label>
    <div class="flex gap-3 items-end">
      <AppTextarea
        id="chat-message"
        aria-describedby="chat-message-counter"
        v-model="message"
        rows="3"
        minlength="0"
        :maxlength="CHAT_CONSTANTS.MESSAGE_MAX_LENGTH"
        :disabled="isSending"
        class="min-w-0 flex-1 resize-y"
        placeholder="Type a message..."
        @keydown.enter="handleEnter"
      />
      <AppButton
        type="submit"
        aria-label="Send message"
        :loading="isSending"
        :disabled="!message.trim() || isOverLimit"
        ><PaperAirplaneIcon class="h-5 w-5" aria-hidden="true"
      /></AppButton>
    </div>
    <p
      id="chat-message-counter"
      class="text-right text-xs"
      :class="isOverLimit ? 'text-red-600' : 'text-stone-500'"
    >
      {{ messageLength }}/{{ CHAT_CONSTANTS.MESSAGE_MAX_LENGTH }}
    </p>
  </form>
</template>
