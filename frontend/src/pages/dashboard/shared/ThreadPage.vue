<script setup>
import { onMounted, onUnmounted, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useForumStore } from '../../../stores/forumStore'
import { useAuthStore } from '../../../stores/auth'
import { useForumWebSocket } from '../../../composables/useForumWebSocket'
import ThreadCard from '../../../components/molecules/ThreadCard.vue'
import ReplyCard from '../../../components/molecules/ReplyCard.vue'
import AppButton from '../../../components/atoms/AppButton.vue'

const forumStore = useForumStore()
const authStore = useAuthStore()
const route = useRoute()
const router = useRouter()
const { listenToThread, leaveThread } = useForumWebSocket()

const threadId = parseInt(route.params.id)
const replyBody = ref('')
const isAnonymous = ref(false)
const isSubmitting = ref(false)
const replyingToId = ref(null)

const thread = computed(() => forumStore.currentThread)

const replyingToReply = computed(() => {
  if (!replyingToId.value || !thread.value) return null
  const findReply = (replies, id) => {
    for (const r of replies) {
      if (r.id === id) return r
      if (r.children) {
        const found = findReply(r.children, id)
        if (found) return found
      }
    }
    return null
  }
  return findReply(thread.value.replies, replyingToId.value)
})

const handleReplyTo = (id) => {
  replyingToId.value = id
  document.getElementById('reply-composer').scrollIntoView({ behavior: 'smooth' })
}

onMounted(async () => {
  await forumStore.fetchThread(threadId)

  listenToThread(threadId, {
    onReply: (e) => forumStore.handleNewReply(e.reply),
    onVote: (e) => forumStore.handleThreadVote(e.threadId, e.voteScore),
  })
})

onUnmounted(() => {
  leaveThread(threadId)
})

const handleReply = async () => {
  if (!replyBody.value.trim() || isSubmitting.value) return
  isSubmitting.value = true
  try {
    const payload = { body: replyBody.value, is_anonymous: isAnonymous.value }
    if (replyingToId.value) {
      payload.parent_id = replyingToId.value
    }
    await forumStore.createReply(threadId, payload)
    replyBody.value = ''
    replyingToId.value = null
  } finally {
    isSubmitting.value = false
  }
}

const handleVote = (id, val) => forumStore.voteThread(id, val)
const handleReplyVote = (id, val) => forumStore.voteReply(id, val)
const handleAccept = (id) => forumStore.acceptReply(id)

const goBack = () => router.push({ name: 'community-forum' })
</script>

<template>
  <div class="h-full flex flex-col">
    <button
      @click="goBack"
      class="flex items-center gap-2 text-stone-500 hover:text-moss-600 transition-colors mb-6 font-medium"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M10 19l-7-7m0 0l7-7m-7 7h18"
        ></path>
      </svg>
      Back to Discussions
    </button>

    <div v-if="forumStore.isLoading && !thread" class="text-center py-12 text-stone-500">
      Loading discussion...
    </div>

    <template v-else-if="thread">
      <!-- Original Thread Post -->
      <div class="mb-8">
        <ThreadCard :thread="thread" @vote="handleVote" />
      </div>

      <!-- Replies Section -->
      <div class="space-y-6">
        <h2 class="font-serif text-2xl font-bold text-stone-900 border-b border-stone-300 pb-2">
          {{ thread.reply_count }} Replies
        </h2>

        <!-- Reply Composer -->
        <div id="reply-composer" class="bg-stone-50 rounded-2xl p-4 sm:p-6 border border-stone-300">
          <form @submit.prevent="handleReply">
            <div
              v-if="replyingToReply"
              class="mb-3 flex items-center justify-between bg-stone-100 p-3 rounded-lg border border-stone-300"
            >
              <div class="text-sm text-stone-600 truncate flex-grow mr-4">
                <span class="font-medium text-stone-900"
                  >Replying to {{ replyingToReply.author.name }}:</span
                >
                "{{ replyingToReply.body }}"
              </div>
              <button
                type="button"
                @click="replyingToId = null"
                class="text-stone-400 hover:text-stone-600 flex-shrink-0"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  ></path>
                </svg>
              </button>
            </div>
            <textarea
              v-model="replyBody"
              rows="4"
              class="w-full p-4 rounded-xl border-stone-300 shadow-inner focus:ring-moss-500 focus:border-moss-500 transition-shadow mb-3 resize-y"
              placeholder="Add your knowledge or ask for clarification..."
              required
            ></textarea>
            <div class="flex flex-wrap items-center justify-between gap-4">
              <div class="flex items-center gap-2">
                <input
                  v-model="isAnonymous"
                  type="checkbox"
                  id="anon-reply"
                  class="rounded text-moss-600 focus:ring-moss-500 w-4 h-4 border-stone-300"
                />
                <label for="anon-reply" class="text-sm text-stone-600 cursor-pointer"
                  >Post anonymously</label
                >
              </div>
              <AppButton
                type="submit"
                :disabled="isSubmitting || !replyBody.trim()"
                class="bg-gradient-to-br from-moss-500 to-moss-600 text-white shadow-soft"
              >
                Post Reply
              </AppButton>
            </div>
          </form>
        </div>

        <!-- Reply List -->
        <div class="space-y-4">
          <ReplyCard
            v-for="reply in thread.replies"
            :key="reply.id"
            :reply="reply"
            :isThreadAuthor="authStore.user?.id === thread.user_id"
            @vote="handleReplyVote"
            @accept="handleAccept"
            @replyTo="handleReplyTo"
          />
        </div>
      </div>
    </template>
  </div>
</template>
