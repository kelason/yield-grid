import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useApi } from '../composables/useApi'
import { useNotificationStore } from './notificationStore'

export const useForumStore = defineStore('forum', () => {
  const api = useApi()
  const notificationStore = useNotificationStore()

  // State
  const threads = ref([])
  const currentThread = ref(null)
  const categories = ref([])
  const tags = ref([])
  const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    total: 0,
  })
  const isLoading = ref(false)

  // Actions
  async function fetchCategories() {
    try {
      const response = await api.get('/forum/categories')
      categories.value = response.data.data
    } catch (error) {
      console.error('Failed to fetch categories:', error)
    }
  }

  async function fetchTags() {
    try {
      const response = await api.get('/forum/tags')
      tags.value = response.data.data
    } catch (error) {
      console.error('Failed to fetch tags:', error)
    }
  }

  async function fetchThreads(params = {}) {
    isLoading.value = true
    try {
      const response = await api.get('/forum/threads', { params })
      threads.value = response.data.data
      pagination.value = {
        currentPage: response.data.meta.current_page,
        lastPage: response.data.meta.last_page,
        total: response.data.meta.total,
      }
    } catch (error) {
      console.error(error)
      notificationStore.error('Failed to fetch threads')
    } finally {
      isLoading.value = false
    }
  }

  async function fetchThread(id) {
    isLoading.value = true
    try {
      const response = await api.get(`/forum/threads/${id}`)
      currentThread.value = response.data.data || response.data
    } catch (error) {
      console.error(error)
      notificationStore.error('Failed to fetch thread')
    } finally {
      isLoading.value = false
    }
  }

  async function createThread(data) {
    try {
      const response = await api.post('/forum/threads', data)
      notificationStore.success('Thread created successfully')
      return response.data
    } catch (error) {
      notificationStore.error('Failed to create thread')
      throw error
    }
  }

  async function createReply(threadId, data) {
    try {
      const response = await api.post(`/forum/threads/${threadId}/replies`, data)

      // If we are currently viewing this thread, add it locally before socket broadcast comes
      if (currentThread.value && currentThread.value.id === threadId) {
        const replyData = response.data.data || response.data
        if (!data.parent_id) {
          currentThread.value.replies.push(replyData)
        } else {
          const appendRecursive = (replies) => {
            for (const r of replies) {
              if (r.id === data.parent_id) {
                if (!r.children) r.children = []
                r.children.push(replyData)
                return true
              }
              if (r.children && appendRecursive(r.children)) return true
            }
            return false
          }
          appendRecursive(currentThread.value.replies)
        }
      }
      return response.data
    } catch (error) {
      notificationStore.error('Failed to post reply')
      throw error
    }
  }

  async function voteThread(threadId, value) {
    try {
      const response = await api.post(`/forum/threads/${threadId}/vote`, { value })
      const data = response.data.data || response.data

      if (currentThread.value && currentThread.value.id === threadId) {
        currentThread.value.vote_score = data.vote_score
        currentThread.value.user_vote = data.user_vote
      }
      // Update in list view as well
      const threadInList = threads.value.find((t) => t.id === threadId)
      if (threadInList) {
        threadInList.vote_score = data.vote_score
        threadInList.user_vote = data.user_vote
      }
      return data
    } catch (error) {
      console.error(error)
      notificationStore.error('Failed to vote')
    }
  }

  async function voteReply(replyId, value) {
    try {
      const response = await api.post(`/forum/replies/${replyId}/vote`, { value })
      const data = response.data.data || response.data

      // Update local state if needed, normally socket broadcast or reactivity handles this better
      if (currentThread.value) {
        const updateVoteRecursive = (replies) => {
          for (const reply of replies) {
            if (reply.id === replyId) {
              reply.vote_score = data.vote_score
              reply.user_vote = data.user_vote
              return true
            }
            if (reply.children && updateVoteRecursive(reply.children)) return true
          }
          return false
        }
        updateVoteRecursive(currentThread.value.replies)
      }
      return data
    } catch (error) {
      console.error(error)
      notificationStore.error('Failed to vote')
    }
  }

  async function acceptReply(replyId) {
    try {
      await api.post(`/forum/replies/${replyId}/accept`)
      // Refresh thread to get new state
      if (currentThread.value) {
        await fetchThread(currentThread.value.id)
      }
    } catch (error) {
      console.error(error)
      notificationStore.error('Failed to accept reply')
    }
  }

  async function reportContent(type, id, reason, description) {
    try {
      await api.post('/forum/reports', {
        reportable_type: type,
        reportable_id: id,
        reason,
        description,
      })
      notificationStore.success('Report submitted. Thank you.')
    } catch (error) {
      console.error(error)
      notificationStore.error('Failed to submit report')
    }
  }

  // Handle incoming socket events
  function handleNewReply(reply) {
    if (currentThread.value && currentThread.value.id === reply.thread_id) {
      // Prevent duplicate if we just added it manually
      const exists = currentThread.value.replies.some((r) => r.id === reply.id)
      if (!exists && !reply.parent_id) {
        currentThread.value.replies.push(reply)
        currentThread.value.reply_count++
      }
    }
  }

  function handleThreadVote(threadId, score) {
    if (currentThread.value && currentThread.value.id === threadId) {
      currentThread.value.vote_score = score
    }
    const threadInList = threads.value.find((t) => t.id === threadId)
    if (threadInList) {
      threadInList.vote_score = score
    }
  }

  return {
    threads,
    currentThread,
    categories,
    tags,
    pagination,
    isLoading,
    fetchCategories,
    fetchTags,
    fetchThreads,
    fetchThread,
    createThread,
    createReply,
    voteThread,
    voteReply,
    acceptReply,
    reportContent,
    handleNewReply,
    handleThreadVote,
  }
})
