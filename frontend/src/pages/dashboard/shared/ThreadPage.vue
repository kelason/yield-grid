<script setup>
import AppTextarea from '@/components/atoms/AppTextarea.vue'
import { onMounted, onUnmounted, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useForumStore } from '../../../stores/forumStore'
import { useAuthStore } from '../../../stores/auth'
import { useForumWebSocket } from '../../../composables/useForumWebSocket'
import { useContentReport } from '@/composables/useContentReport'
import ThreadCard from '../../../components/molecules/ThreadCard.vue'
import ReplyCard from '../../../components/molecules/ReplyCard.vue'
import ContentReportForm from '@/components/organisms/ContentReportForm.vue'
import AppButton from '../../../components/atoms/AppButton.vue'
import AppModal from '../../../components/molecules/AppModal.vue'
import ConfirmModal from '../../../components/molecules/ConfirmModal.vue'
import { useConfirmModal } from '@/composables/useConfirmModal'
import PageHeader from '@/components/molecules/PageHeader.vue'
import LoadingState from '@/components/molecules/LoadingState.vue'
import EmptyState from '@/components/molecules/EmptyState.vue'
import { FORUM_CONSTANTS } from '../../../constants/forum'

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
const replyError = ref('')
const { isOpen, isExecuting, config, confirm, execute, cancel } = useConfirmModal()
const showReportModal = ref(false)
const {
  target: reportTarget,
  reason: reportReason,
  description: reportDescription,
  busy: reportBusy,
  error: reportError,
  fieldError: reportFieldError,
  openReport,
  closeReport,
  validate: validateReport,
  submit: submitContentReport,
} = useContentReport()
const reportAccess = computed(() => {
  if (!authStore.isAuthenticated) return 'signin'
  if (!authStore.isEmailVerified) return 'verify'
  return 'ok'
})

const thread = computed(() => forumStore.currentThread)

const replyingToReply = computed(() => {
  if (!replyingToId.value || !thread.value) return null
  const findReply = (replies, id) => {
    for (const r of replies || []) {
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
  document.getElementById('reply-composer')?.scrollIntoView({
    behavior: window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
  })
  document.getElementById('reply-body')?.focus()
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

const replyBodyLength = computed(() => (replyBody.value || '').length)
const isReplyOverLimit = computed(() => replyBodyLength.value > FORUM_CONSTANTS.REPLY_MAX_LENGTH)

function handleReply() {
  if (
    isSubmitting.value ||
    isReplyOverLimit.value ||
    replyBody.value.trim().length < FORUM_CONSTANTS.REPLY_MIN_LENGTH
  )
    return
  const payload = { body: replyBody.value, is_anonymous: isAnonymous.value }
  if (replyingToId.value) payload.parent_id = replyingToId.value
  confirm(
    {
      title: 'Post this reply?',
      message: 'Your reply will be shared with this discussion.',
      confirmText: 'Post reply',
    },
    () => postReply(payload),
  )
}
async function postReply(payload) {
  isSubmitting.value = true
  replyError.value = ''
  try {
    await forumStore.createReply(threadId, payload)
    replyBody.value = ''
    replyingToId.value = null
  } catch (error) {
    replyError.value = error.response?.data?.message || 'Failed to post reply. Please retry.'
  } finally {
    isSubmitting.value = false
  }
}
function handleVote(id, value) {
  confirm(
    {
      title: 'Update your vote?',
      message: 'Your vote on this discussion will be updated.',
      confirmText: 'Vote',
    },
    () => forumStore.voteThread(id, value),
  )
}
function handleReplyVote(id, value) {
  confirm(
    {
      title: 'Update your vote?',
      message: 'Your vote on this reply will be updated.',
      confirmText: 'Vote',
    },
    () => forumStore.voteReply(id, value),
  )
}
function handleAccept(id) {
  confirm(
    {
      title: 'Accept this answer?',
      message:
        'This reply will be marked as the accepted answer for your thread. The thread will be flagged as resolved.',
      confirmText: 'Accept answer',
    },
    () => forumStore.acceptReply(id),
  )
}

const goBack = () => router.push({ name: 'community-forum' })

function openReportModal(payload) {
  openReport(payload)
  showReportModal.value = true
}
function closeReportModal() {
  if (reportBusy.value || isExecuting.value) return
  showReportModal.value = false
  closeReport()
}
function requestReportSubmit() {
  if (!reportTarget.value || reportBusy.value) return
  if (!validateReport()) return
  confirm(
    {
      title: 'Submit this report?',
      message: 'Your report will be sent to the moderation team.',
      confirmText: 'Send report',
    },
    submitReport,
  )
}
async function submitReport() {
  try {
    await submitContentReport()
    showReportModal.value = false
  } catch {
    // The inline form error and draft stay visible; the modal remains open.
  }
}
function goLogin() {
  closeReportModal()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="space-y-6">
    <PageHeader :title="thread?.title || 'Discussion'" />
    <AppButton
      variant="ghost"
      @click="goBack"
      class="flex items-center gap-2 text-stone-500 hover:text-moss-600 transition-colors duration-200 mb-6 font-medium"
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
    </AppButton>

    <LoadingState v-if="forumStore.isLoading && !thread" label="Loading discussion" />
    <div
      v-else-if="forumStore.fetchError"
      role="alert"
      class="rounded-2xl border border-red-200 bg-white p-6"
    >
      <p>{{ forumStore.fetchError }}</p>
      <AppButton variant="outline" @click="forumStore.fetchThread(threadId)"
        >Retry discussion</AppButton
      >
    </div>

    <template v-else-if="thread">
      <!-- Original Thread Post -->
      <div class="mb-8">
        <ThreadCard :thread="thread" @vote="handleVote" @report="openReportModal" />
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
              class="mb-3 flex items-center justify-between bg-stone-100 p-3 rounded-xl border border-stone-300"
            >
              <div class="text-sm text-stone-600 truncate flex-grow mr-4">
                <span class="font-medium text-stone-900"
                  >Replying to {{ replyingToReply.author?.name || 'Anonymous' }}:</span
                >
                "{{ replyingToReply.body }}"
              </div>
              <button
                aria-label="Cancel reply to this answer"
                type="button"
                @click="replyingToId = null"
                class="min-h-11 min-w-11 text-stone-500 hover:text-stone-600 transition-colors duration-200 flex-shrink-0"
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
            <div class="flex items-center justify-between mb-1">
              <label for="reply-body" class="block text-sm font-medium text-soil-700"
                >Your Reply</label
              >
              <span
                class="text-[11px]"
                :class="isReplyOverLimit ? 'text-red-600 font-semibold' : 'text-stone-500'"
                id="reply-body-counter"
              >
                {{ replyBodyLength }}/{{ FORUM_CONSTANTS.REPLY_MAX_LENGTH }}
              </span>
            </div>
            <AppTextarea
              :minlength="FORUM_CONSTANTS.REPLY_MIN_LENGTH"
              id="reply-body"
              aria-describedby="reply-body-counter"
              v-model="replyBody"
              rows="4"
              :maxlength="FORUM_CONSTANTS.REPLY_MAX_LENGTH"
              class="resize-y mb-3"
              :disabled="isExecuting"
              placeholder="Add your knowledge or ask for clarification..."
              required
            ></AppTextarea>
            <p v-if="replyError" role="alert" class="mb-3 text-sm text-red-600">{{ replyError }}</p>
            <div class="flex flex-wrap items-center justify-between gap-4">
              <div class="flex items-center gap-2">
                <input
                  v-model="isAnonymous"
                  type="checkbox"
                  id="anon-reply"
                  class="rounded-xl text-moss-600 focus:ring-moss-500 w-4 h-4 border-stone-300"
                />
                <label for="anon-reply" class="text-sm text-stone-600 cursor-pointer"
                  >Post anonymously</label
                >
              </div>
              <AppButton
                type="submit"
                :disabled="
                  isSubmitting ||
                  replyBody.trim().length < FORUM_CONSTANTS.REPLY_MIN_LENGTH ||
                  isReplyOverLimit
                "
              >
                Post Reply
              </AppButton>
            </div>
          </form>
        </div>

        <!-- Reply List -->
        <div class="space-y-4">
          <ReplyCard
            v-for="reply in thread.replies || []"
            :key="reply.id"
            :reply="reply"
            :isThreadAuthor="authStore.user?.id === thread.user_id"
            @vote="handleReplyVote"
            @accept="handleAccept"
            @replyTo="handleReplyTo"
            @report="openReportModal"
          />
        </div>
      </div>
    </template>

    <EmptyState
      v-else
      title="Discussion unavailable"
      description="Return to the community to choose another discussion."
    />
    <AppModal
      title="Report content"
      :is-open="showReportModal"
      :busy="reportBusy || isExecuting"
      @close="closeReportModal"
    >
      <ContentReportForm
        v-if="reportAccess === 'ok' && reportTarget"
        :target="reportTarget"
        :reason="reportReason"
        :description="reportDescription"
        :busy="reportBusy || isExecuting"
        :error="reportError"
        :field-error="reportFieldError"
        @update:reason="reportReason = $event"
        @update:description="reportDescription = $event"
        @submit="requestReportSubmit"
        @cancel="closeReportModal"
      />
      <div v-else-if="reportAccess === 'signin'" class="space-y-4">
        <p class="text-base leading-relaxed text-stone-600">
          Sign in to report this content to the moderation team.
        </p>
        <div class="flex justify-end gap-3">
          <AppButton variant="secondary" @click="closeReportModal">Cancel</AppButton>
          <AppButton variant="primary" @click="goLogin">Sign in</AppButton>
        </div>
      </div>
      <div v-else class="space-y-4">
        <p class="text-base leading-relaxed text-stone-600">
          Verify your email address to report content to the moderation team.
        </p>
        <div class="flex justify-end">
          <AppButton variant="secondary" @click="closeReportModal">Close</AppButton>
        </div>
      </div>
    </AppModal>
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
