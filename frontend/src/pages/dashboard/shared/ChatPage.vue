<script setup>
import { onMounted, ref, computed, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { useChatStore } from '../../../stores/chatStore'
import { useChatWebSocket } from '../../../composables/useChatWebSocket'
import ConversationItem from '../../../components/molecules/ConversationItem.vue'
import ChatComposer from '../../../components/molecules/ChatComposer.vue'
import ChatBubble from '../../../components/atoms/ChatBubble.vue'

const chatStore = useChatStore()
const { listenToConversation, leaveConversation } = useChatWebSocket()
const route = useRoute()

const messagesContainer = ref(null)
const selectedConversationId = ref(null)

onMounted(async () => {
  await chatStore.fetchConversations()
  const requestedId = Number(route.query.conversation)
  const target = chatStore.conversations.some((c) => c.id === requestedId)
    ? requestedId
    : chatStore.conversations[0]?.id
  if (target) {
    selectConversation(target)
  }
})

const activeConversation = computed(() => {
  return chatStore.conversations.find((c) => c.id === selectedConversationId.value)
})

const selectConversation = async (id) => {
  if (selectedConversationId.value === id) return

  // Clean up old listener
  if (selectedConversationId.value) {
    leaveConversation(selectedConversationId.value)
  }

  selectedConversationId.value = id
  chatStore.activeConversation = chatStore.conversations.find((c) => c.id === id)

  await chatStore.fetchMessages(id)
  scrollToBottom()

  listenToConversation(id, {
    onMessage: (e) => {
      chatStore.handleNewMessage(e.message)
      scrollToBottom()
    },
  })
}

const handleSend = async (text) => {
  if (!selectedConversationId.value) return
  await chatStore.sendMessage(selectedConversationId.value, text)
  scrollToBottom()
}

const scrollToBottom = () => {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
  })
}
</script>

<template>
  <div
    class="h-[calc(100vh-12rem)] min-h-[600px] flex overflow-hidden shadow-soft rounded-2xl border border-stone-300"
  >
    <!-- Left Sidebar: Conversations -->
    <div
      class="w-full md:w-80 flex-shrink-0 flex flex-col bg-white border-r md:border border-stone-300 md:rounded-l-2xl shadow-soft"
      :class="{ 'hidden md:flex': selectedConversationId }"
    >
      <div
        class="p-4 border-b border-stone-300 flex justify-between items-center bg-stone-50 md:rounded-tl-2xl"
      >
        <h2 class="font-serif text-xl font-bold text-stone-900">Messages</h2>
        <!-- New Message Button could go here -->
      </div>

      <div class="flex-1 overflow-y-auto p-2 space-y-1">
        <div
          v-if="chatStore.isLoading && chatStore.conversations.length === 0"
          class="text-center py-8 text-stone-400 text-sm"
        >
          Loading...
        </div>
        <div
          v-else-if="chatStore.conversations.length === 0"
          class="text-center py-8 text-stone-400 text-sm"
        >
          No conversations yet.
        </div>
        <ConversationItem
          v-else
          v-for="conv in chatStore.conversations"
          :key="conv.id"
          :conversation="conv"
          :isActive="selectedConversationId === conv.id"
          @select="selectConversation"
        />
      </div>
    </div>

    <!-- Right Area: Chat Window -->
    <div
      class="flex-1 flex flex-col bg-stone-100 md:border-y md:border-r border-stone-300 md:rounded-r-2xl"
      :class="{ 'hidden md:flex': !selectedConversationId }"
    >
      <template v-if="activeConversation">
        <!-- Header -->
        <div
          class="p-4 bg-white border-b border-stone-300 flex items-center gap-3 md:rounded-tr-2xl shadow-sm z-10"
        >
          <button
            @click="selectedConversationId = null"
            class="md:hidden p-2 text-stone-500 hover:bg-stone-100 rounded-full"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 19l-7-7 7-7"
              ></path>
            </svg>
          </button>

          <img
            v-if="activeConversation.other_participant?.avatar_url"
            :src="activeConversation.other_participant.avatar_url"
            class="w-10 h-10 rounded-full object-cover bg-stone-200"
          />
          <div
            v-else
            class="w-10 h-10 rounded-full bg-soil-200 flex items-center justify-center text-soil-700 font-bold"
          >
            {{ activeConversation.other_participant?.name.charAt(0).toUpperCase() || '?' }}
          </div>
          <div>
            <div class="font-bold text-stone-900 font-sans">
              {{ activeConversation.other_participant?.name || 'Unknown' }}
            </div>
            <div class="text-xs text-stone-500 capitalize">
              {{ activeConversation.other_participant?.role || '' }}
            </div>
          </div>
        </div>

        <!-- Messages Area -->
        <div
          class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-3 flex flex-col"
          ref="messagesContainer"
        >
          <div
            v-if="chatStore.isLoading && chatStore.messages.length === 0"
            class="flex justify-center py-4"
          >
            <div class="animate-pulse flex gap-1">
              <div class="w-2 h-2 bg-moss-400 rounded-full"></div>
              <div class="w-2 h-2 bg-moss-400 rounded-full animation-delay-200"></div>
              <div class="w-2 h-2 bg-moss-400 rounded-full animation-delay-400"></div>
            </div>
          </div>

          <div
            v-else-if="chatStore.messages.length === 0"
            class="h-full flex flex-col items-center justify-center text-stone-400 space-y-2"
          >
            <div class="text-4xl">👋</div>
            <p>Say hello to start the conversation</p>
          </div>

          <ChatBubble
            v-else
            v-for="msg in chatStore.messages"
            :key="msg.id"
            :body="msg.body"
            :time="
              new Date(msg.created_at).toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit',
              })
            "
            :isOwn="msg.is_own"
          />
        </div>

        <!-- Composer -->
        <div class="md:rounded-br-2xl overflow-hidden">
          <ChatComposer @send="handleSend" />
        </div>
      </template>

      <div v-else class="h-full flex flex-col items-center justify-center text-stone-400">
        <svg
          class="w-16 h-16 text-stone-300 mb-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.5"
            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
          ></path>
        </svg>
        <p>Select a conversation to start messaging</p>
      </div>
    </div>
  </div>
</template>
