import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useApi } from '../composables/useApi'

export const useChatStore = defineStore('chat', () => {
  const api = useApi()

  // State
  const conversations = ref([])
  const activeConversation = ref(null)
  const messages = ref([])
  const isLoading = ref(false)

  // Getters
  const totalUnread = computed(() => {
    return conversations.value.reduce((total, conv) => total + (conv.unread_count || 0), 0)
  })

  // Actions
  async function fetchConversations() {
    isLoading.value = true
    try {
      const response = await api.get('/chat/conversations')
      conversations.value = response.data.data
    } catch (error) {
      console.error('Failed to fetch conversations:', error)
    } finally {
      isLoading.value = false
    }
  }

  async function fetchMessages(conversationId, page = 1) {
    isLoading.value = true
    try {
      const response = await api.get(`/chat/conversations/${conversationId}?page=${page}`)

      // If it's page 1, replace messages, else append (prepend since latest first)
      const fetchedMessages = response.data.data.reverse() // API sends latest first, we need chronological

      if (page === 1) {
        messages.value = fetchedMessages
        // Clear unread count for this conversation since we just loaded it (backend marks it read)
        const conv = conversations.value.find((c) => c.id === conversationId)
        if (conv) conv.unread_count = 0
      } else {
        messages.value = [...fetchedMessages, ...messages.value]
      }

      return response.data.meta // for pagination details
    } catch (error) {
      console.error('Failed to fetch messages:', error)
    } finally {
      isLoading.value = false
    }
  }

  async function startConversation(recipientId) {
    try {
      const response = await api.post('/chat/conversations', { recipient_id: recipientId })
      const newConv = response.data

      // Add to list if not exists
      const exists = conversations.value.find((c) => c.id === newConv.id)
      if (!exists) {
        conversations.value.unshift(newConv)
      }

      return newConv
    } catch (error) {
      console.error('Failed to start conversation:', error)
      throw error
    }
  }

  async function sendMessage(conversationId, body) {
    try {
      // Optimistic update could go here
      const response = await api.post(`/chat/conversations/${conversationId}/messages`, { body })

      // Add to current view if active
      if (activeConversation.value && activeConversation.value.id === conversationId) {
        messages.value.push(response.data)
      }

      // Update conversation list
      const conv = conversations.value.find((c) => c.id === conversationId)
      if (conv) {
        conv.latest_message = response.data
        conv.last_message_at = response.data.created_at

        // Move to top
        conversations.value = [conv, ...conversations.value.filter((c) => c.id !== conversationId)]
      }

      return response.data
    } catch (error) {
      console.error('Failed to send message:', error)
      throw error
    }
  }

  function handleNewMessage(message) {
    // If it's for the active conversation, append it
    if (activeConversation.value && activeConversation.value.id === message.conversation_id) {
      const exists = messages.value.some((m) => m.id === message.id)
      if (!exists) {
        messages.value.push(message)
      }
    }

    // Update the conversation list
    let conv = conversations.value.find((c) => c.id === message.conversation_id)
    if (conv) {
      conv.latest_message = message
      conv.last_message_at = message.created_at

      if (!activeConversation.value || activeConversation.value.id !== message.conversation_id) {
        conv.unread_count = (conv.unread_count || 0) + 1
      }

      // Move to top
      conversations.value = [
        conv,
        ...conversations.value.filter((c) => c.id !== message.conversation_id),
      ]
    } else {
      // If we received a message for an unknown conversation, fetch the list again
      fetchConversations()
    }
  }

  return {
    conversations,
    activeConversation,
    messages,
    isLoading,
    totalUnread,
    fetchConversations,
    fetchMessages,
    startConversation,
    sendMessage,
    handleNewMessage,
  }
})
