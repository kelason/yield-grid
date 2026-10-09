<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import VoteBadge from '../atoms/VoteBadge.vue'
import AcceptedBadge from '../atoms/AcceptedBadge.vue'
import { FlagIcon } from '@heroicons/vue/24/outline'
import { formatDistanceToNow } from 'date-fns'
import { useAuthStore } from '../../stores/auth'

const authStore = useAuthStore()
const { t } = useI18n()

const REPLY_TITLE_MAX_LENGTH = 80

const props = defineProps({
  reply: {
    type: Object,
    required: true,
  },
  isThreadAuthor: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['vote', 'accept', 'replyTo', 'report'])

const isOwner = computed(() => {
  const userId = authStore.user?.id
  if (userId == null) return false
  const ownerId = props.reply.author?.id ?? props.reply.user_id
  return ownerId != null && String(userId) === String(ownerId)
})

function emitReport() {
  emit('report', {
    reportable_type: 'reply',
    reportable_id: props.reply.id,
    title: String(props.reply.body ?? t('community.reply.reply_fallback')).slice(
      0,
      REPLY_TITLE_MAX_LENGTH,
    ),
  })
}

const ANONYMOUS_ROUTE_PARAM = 'anonymous'

function profileLink(author) {
  return { name: 'user-profile', params: { userId: author?.id ?? ANONYMOUS_ROUTE_PARAM } }
}
</script>

<template>
  <div
    class="bg-white rounded-2xl p-5 shadow-soft border border-stone-200 transition-all duration-300 motion-reduce:transition-none"
    :class="{ 'border-moss-200 ring-1 ring-moss-200': reply.is_accepted }"
  >
    <div class="flex gap-4">
      <div class="flex-shrink-0 flex flex-col items-center gap-3">
        <VoteBadge
          :score="reply.vote_score"
          :userVote="reply.user_vote"
          :disabled="authStore.user?.id === reply.author?.id"
          @vote="(val) => emit('vote', reply.id, val)"
        />
        <button
          v-if="isThreadAuthor || reply.is_accepted"
          @click="emit('accept', reply.id)"
          class="min-h-11 min-w-11 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500 rounded-full transition-transform motion-reduce:transition-none motion-reduce:transform-none motion-reduce:hover:scale-100 hover:scale-110"
          :class="{ 'cursor-default': !isThreadAuthor && reply.is_accepted }"
          :disabled="!isThreadAuthor"
          :title="t('community.reply.accept_title')"
          :aria-label="t('community.reply.accept_aria')"
          type="button"
        >
          <AcceptedBadge v-if="reply.is_accepted" />
          <svg
            v-else
            class="w-6 h-6 text-stone-300 hover:text-moss-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
            ></path>
          </svg>
        </button>
      </div>

      <div class="flex-grow min-w-0">
        <div class="flex flex-wrap justify-between items-start gap-2 mb-3">
          <RouterLink
            :to="profileLink(reply.author)"
            class="flex items-center gap-3 rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500"
          >
            <img
              v-if="reply.author?.avatar_url"
              :src="reply.author?.avatar_url"
              class="w-8 h-8 rounded-full bg-stone-200"
            />
            <div
              v-else
              class="w-8 h-8 rounded-full bg-soil-200 flex items-center justify-center text-soil-700 font-bold"
            >
              {{ reply.author?.name?.charAt(0).toUpperCase() }}
            </div>
            <div>
              <div
                class="font-medium text-stone-900 hover:text-moss-700 transition-colors duration-200 motion-reduce:transition-none"
              >
                {{ reply.author?.name || t('community.card.anonymous') }}
              </div>
              <div class="text-xs text-stone-500 capitalize">{{ reply.author?.role }}</div>
            </div>
          </RouterLink>
          <div class="text-xs text-stone-500">
            {{ formatDistanceToNow(new Date(reply.created_at), { addSuffix: true }) }}
          </div>
        </div>

        <div
          class="text-stone-700 text-base leading-relaxed whitespace-pre-wrap break-words [overflow-wrap:anywhere] mb-4"
        >
          {{ reply.body }}
        </div>

        <div
          v-if="reply.attachments && reply.attachments.length > 0"
          class="flex flex-wrap gap-2 mb-4"
        >
          <img
            v-for="att in reply.attachments"
            :key="att.id"
            :src="att.file_path"
            class="max-h-48 rounded-xl object-cover border border-stone-200 shadow-soft"
          />
        </div>

        <div class="flex gap-4 border-t border-stone-100 pt-3">
          <button
            @click="emit('replyTo', reply.id)"
            class="min-h-11 px-2 text-sm font-medium text-soil-600 hover:text-soil-800 transition-colors motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500 rounded-xl"
          >
            {{ t('community.reply.reply') }}
          </button>
          <button
            v-if="!isOwner"
            type="button"
            :aria-label="t('community.reply.report_aria')"
            class="min-h-11 px-2 inline-flex items-center gap-1 text-sm font-medium text-soil-600 hover:text-soil-800 transition-colors motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500 rounded-xl"
            @click.stop="emitReport"
          >
            <FlagIcon class="w-4 h-4" aria-hidden="true" />
            {{ t('community.card.report') }}
          </button>
        </div>

        <!-- Nested replies could go here if threaded -->
        <div
          v-if="reply.children && reply.children.length > 0"
          class="mt-4 pl-4 border-l-2 border-stone-100 space-y-4"
        >
          <ReplyCard
            v-for="child in reply.children"
            :key="child.id"
            :reply="child"
            :isThreadAuthor="isThreadAuthor"
            @vote="(id, val) => emit('vote', id, val)"
            @accept="(id) => emit('accept', id)"
            @replyTo="(id) => emit('replyTo', id)"
            @report="(payload) => emit('report', payload)"
          />
        </div>
      </div>
    </div>
  </div>
</template>
