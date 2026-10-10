import { useRouter } from 'vue-router'
import i18n from '@/i18n'
import { useChatStore } from '../stores/chatStore'
import { useNotificationStore } from '../stores/notificationStore'
import { HTTP_STATUS } from '../constants/http'

const t = (...args) => i18n.global.t(...args)

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
      notificationStore.error(t('chat.entry.no_recipient'))
      return
    }

    try {
      const conversation = await chatStore.startConversation(recipientId)
      router.push({ name: 'chat', query: { conversation: conversation.id } })
    } catch (err) {
      if (err.response?.status === HTTP_STATUS.FORBIDDEN) {
        notificationStore.error(t('chat.entry.unavailable'))
      } else {
        notificationStore.error(t('chat.entry.open_failed'))
      }
    }
  }

  return { openChat }
}
