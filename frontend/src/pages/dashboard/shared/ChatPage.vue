<script setup>
import { onMounted, ref, computed, nextTick, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, RouterLink } from 'vue-router'
import { useChatStore } from '../../../stores/chatStore'
import ConversationItem from '../../../components/molecules/ConversationItem.vue'
import ChatComposer from '../../../components/molecules/ChatComposer.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import PageHeader from '@/components/molecules/PageHeader.vue'
import EmptyState from '@/components/molecules/EmptyState.vue'
import LoadingState from '@/components/molecules/LoadingState.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import { useConfirmModal } from '@/composables/useConfirmModal'
import { CHAT_CONSTANTS } from '@/constants/chat'
import ChatBubble from '../../../components/atoms/ChatBubble.vue'

const CONVERSATION_SKELETON_COUNT = 5
const MESSAGE_SKELETON_STYLES = [
  'self-start w-3/4',
  'self-end w-2/3',
  'self-start w-1/2',
  'self-end w-3/4',
]

const chatStore = useChatStore()
const route = useRoute()
const { t } = useI18n()

const messagesContainer = ref(null)
const selectedConversationId = ref(null)
const sentMessage = ref('')
const sendError = ref('')
const { isOpen, isExecuting, config, confirm, execute, cancel } = useConfirmModal()

// Realtime sync is owned by the dashboard layout; scroll locally when a new
// message lands at the end of the open conversation.
watch(
  () => chatStore.messages[chatStore.messages.length - 1]?.id,
  (newId, oldId) => {
    if (newId !== undefined && newId !== oldId) {
      scrollToBottom()
    }
  },
)

onMounted(async () => {
  await chatStore.ensureConversationsLoaded()
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

  selectedConversationId.value = id
  chatStore.activeConversation = chatStore.conversations.find((c) => c.id === id)

  await chatStore.fetchMessages(id)
  scrollToBottom()
}

function backToConversations() {
  selectedConversationId.value = null
  chatStore.activeConversation = null
}
async function retryMessages() {
  const id = selectedConversationId.value
  if (!id) return
  await chatStore.fetchMessages(id)
  scrollToBottom()
}
function handleSend(text) {
  const id = selectedConversationId.value
  if (!id || !text.trim() || text.length > CHAT_CONSTANTS.MESSAGE_MAX_LENGTH) return
  sentMessage.value = ''
  confirm(
    {
      title: t('chat.inbox.send_title'),
      message: t('chat.inbox.send_msg'),
      confirmText: t('chat.inbox.send_ok'),
    },
    () => sendMessage(id, text),
  )
}
async function sendMessage(id, text) {
  sendError.value = ''
  try {
    await chatStore.sendMessage(id, text)
    if (selectedConversationId.value === id) sentMessage.value = text
    scrollToBottom()
  } catch (error) {
    sendError.value = error.response?.data?.message || t('chat.inbox.send_failed')
  }
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
  <div class="space-y-6">
    <PageHeader :title="t('chat.inbox.title')" :description="t('chat.inbox.description')" />
    <div
      class="h-[calc(100dvh-16rem)] min-h-[32rem] flex overflow-hidden shadow-soft rounded-2xl border border-stone-300"
    >
      <!-- Left Sidebar: Conversations -->
      <div
        class="w-full md:w-80 flex-shrink-0 flex flex-col bg-white border-r md:border border-stone-300 md:rounded-l-2xl shadow-soft"
        :class="{ 'hidden md:flex': selectedConversationId }"
      >
        <div
          class="p-4 border-b border-stone-300 flex justify-between items-center bg-stone-50 md:rounded-tl-2xl"
        >
          <h2 class="font-serif text-xl font-bold text-stone-900">
            {{ t('chat.inbox.sidebar_title') }}
          </h2>
        </div>

        <div class="flex-1 overflow-y-auto p-2 space-y-1">
          <div v-if="chatStore.isLoading && chatStore.conversations.length === 0" class="space-y-1">
            <LoadingState :label="t('chat.inbox.loading_conv')" />
            <div
              v-for="n in CONVERSATION_SKELETON_COUNT"
              :key="n"
              class="animate-pulse motion-reduce:animate-none flex items-center gap-3 p-3"
              aria-hidden="true"
            >
              <div class="rounded-full bg-stone-200 h-12 w-12 flex-shrink-0"></div>
              <div class="flex-1 space-y-2">
                <div class="h-4 bg-stone-200 rounded-xl w-2/3"></div>
                <div class="h-3 bg-stone-200 rounded-xl w-full"></div>
              </div>
            </div>
          </div>
          <div
            v-else-if="chatStore.conversationsError"
            role="alert"
            class="p-4 text-sm text-red-600"
          >
            <p>{{ chatStore.conversationsError }}</p>
            <AppButton variant="outline" @click="chatStore.ensureConversationsLoaded">{{
              t('chat.inbox.retry_conv')
            }}</AppButton>
          </div>
          <div
            v-else-if="chatStore.conversations.length === 0"
            class="text-center py-8 text-stone-500 text-sm"
          >
            <EmptyState
              :title="t('chat.inbox.empty_title')"
              :description="t('chat.inbox.empty_desc')"
            />
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
        class="min-w-0 flex-1 flex flex-col bg-stone-50 md:border-y md:border-r border-stone-300 md:rounded-r-2xl"
        :class="{ 'hidden md:flex': !selectedConversationId }"
      >
        <template v-if="activeConversation">
          <!-- Header -->
          <div
            class="p-4 bg-white border-b border-stone-300 flex items-center gap-3 md:rounded-tr-2xl shadow-soft z-10"
          >
            <button
              type="button"
              :aria-label="t('chat.inbox.back_aria')"
              @click="backToConversations"
              class="md:hidden min-h-11 min-w-11 p-2 text-stone-500 hover:bg-stone-100 rounded-full transition-colors duration-200"
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

            <RouterLink
              v-if="activeConversation.other_participant?.id"
              :to="{
                name: 'user-profile',
                params: { userId: activeConversation.other_participant.id },
              }"
              class="flex items-center gap-3 rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500"
            >
              <img
                v-if="activeConversation.other_participant?.avatar_url"
                :src="activeConversation.other_participant.avatar_url"
                class="w-10 h-10 rounded-full object-cover bg-stone-200"
              />
              <div
                v-else
                class="w-10 h-10 rounded-full bg-soil-200 flex items-center justify-center text-soil-700 font-bold"
              >
                {{ activeConversation.other_participant?.name?.charAt(0).toUpperCase() || '?' }}
              </div>
              <div>
                <div
                  class="font-bold text-stone-900 font-sans hover:text-moss-700 transition-colors duration-200 motion-reduce:transition-none"
                >
                  {{ activeConversation.other_participant?.name || t('chat.inbox.unknown') }}
                </div>
                <div class="text-xs text-stone-500 capitalize">
                  {{ activeConversation.other_participant?.role || '' }}
                </div>
              </div>
            </RouterLink>
          </div>

          <!-- Messages Area -->
          <div
            class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-3 flex flex-col"
            ref="messagesContainer"
            role="log"
            :aria-label="t('chat.inbox.log_aria')"
          >
            <div
              v-if="chatStore.isLoading && chatStore.messages.length === 0"
              class="flex flex-col gap-3"
            >
              <LoadingState :label="t('chat.inbox.loading_msg')" />
              <div
                v-for="(style, index) in MESSAGE_SKELETON_STYLES"
                :key="index"
                class="animate-pulse motion-reduce:animate-none h-12 bg-stone-200 rounded-2xl"
                :class="style"
                aria-hidden="true"
              ></div>
            </div>

            <div
              v-else-if="chatStore.messagesError"
              role="alert"
              class="space-y-3 text-sm text-red-600"
            >
              <p>{{ chatStore.messagesError }}</p>
              <AppButton variant="outline" @click="retryMessages">{{
                t('chat.inbox.retry_msg')
              }}</AppButton>
            </div>

            <div
              v-else-if="chatStore.messages.length === 0"
              class="h-full flex flex-col items-center justify-center text-stone-500 space-y-2"
            >
              <p>{{ t('chat.inbox.say_hello') }}</p>
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
            <p v-if="sendError" role="alert" class="px-4 py-2 text-sm text-red-600">
              {{ sendError }}
            </p>
            <ChatComposer
              :key="selectedConversationId"
              :is-sending="isExecuting"
              :sent-message="sentMessage"
              @send="handleSend"
            />
          </div>
        </template>

        <div v-else class="h-full flex flex-col items-center justify-center text-stone-500">
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
          <p>{{ t('chat.inbox.select_prompt') }}</p>
        </div>
      </div>
    </div>
    <ConfirmModal
      :is-open="isOpen"
      :title="config.title"
      :message="config.message"
      :confirm-text="config.confirmText"
      :loading="isExecuting"
      @confirm="execute"
      @cancel="cancel"
    />
  </div>
</template>
