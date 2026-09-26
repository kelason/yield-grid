import { useRouter } from 'vue-router'
import { useChatStore } from '../stores/chatStore'
import { useNotificationStore } from '../stores/notificationStore'
import { HTTP_STATUS } from '../constants/http'

const TRANSACTION_CHAT_UNAVAILABLE = 'Chat is only available with your transaction partners.'

/**
 * Opens (or reuses) a 1-to-1 chat with a transaction counterparty and routes
 * to the inbox with the thread preselected. Surfaces the 403 gate as a toast.
 */
export function useChatEntry() {
  const router = useRouter()
  const chatStore = useChatStore()
  const notificationStore = useNotificationStore()

  async function openChat(recipientId) {
    if (!recipientId) {
      notificationStore.error('Could not identify the chat recipient.')
      return
    }

    try {
      const conversation = await chatStore.startConversation(recipientId)
      router.push({ name: 'chat', query: { conversation: conversation.id } })
    } catch (err) {
      if (err.response?.status === HTTP_STATUS.FORBIDDEN) {
        notificationStore.error(TRANSACTION_CHAT_UNAVAILABLE)
      } else {
        notificationStore.error('Could not open chat. Please try again.')
      }
    }
  }

  return { openChat }
}
