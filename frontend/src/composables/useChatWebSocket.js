import { useWebSocket } from './useWebSocket'

export function useChatWebSocket() {
  const { echo } = useWebSocket()

  const listenToConversation = (conversationId, callbacks) => {
    if (!echo) return null

    return echo
      .private(`chat.${conversationId}`)
      .listen('NewChatMessage', (e) => {
        if (callbacks.onMessage) callbacks.onMessage(e)
      })
      .listen('.NewChatMessage', (e) => {
        if (callbacks.onMessage) callbacks.onMessage(e)
      })
      .listen('ConversationRead', (e) => {
        if (callbacks.onRead) callbacks.onRead(e)
      })
      .listen('.ConversationRead', (e) => {
        if (callbacks.onRead) callbacks.onRead(e)
      })
  }

  const leaveConversation = (conversationId) => {
    if (echo) {
      echo.leave(`chat.${conversationId}`)
    }
  }

  return {
    listenToConversation,
    leaveConversation,
  }
}
