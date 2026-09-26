<script setup>
import { RouterLink } from 'vue-router'
import AppCard from '../atoms/AppCard.vue'
import ForumTag from '../atoms/ForumTag.vue'
import VoteBadge from '../atoms/VoteBadge.vue'
import AcceptedBadge from '../atoms/AcceptedBadge.vue'
import { formatDistanceToNow } from 'date-fns'
import { useAuthStore } from '../../stores/auth'

const authStore = useAuthStore()

defineProps({
  thread: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['vote'])
</script>

<template>
  <AppCard variant="default" :hover="true" padding="p-5">
    <div class="flex gap-4">
      <div class="flex-shrink-0 flex flex-col items-center gap-3">
        <VoteBadge
          :score="thread.vote_score"
          :userVote="thread.user_vote"
          :disabled="authStore.user?.id === thread.author.id"
          @vote="(val) => emit('vote', thread.id, val)"
        />
        <div class="text-center" v-if="thread.has_accepted_reply">
          <AcceptedBadge />
        </div>
      </div>
      <div class="flex-grow min-w-0">
        <div class="flex justify-between items-start mb-2">
          <RouterLink
            :to="{ name: 'forum-thread', params: { id: thread.id } }"
            class="font-serif text-xl font-bold text-stone-900 hover:text-moss-700 transition-colors motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500 rounded"
          >
            {{ thread.title }}
          </RouterLink>
          <div class="flex-shrink-0 text-stone-400 text-sm whitespace-nowrap">
            {{ formatDistanceToNow(new Date(thread.last_activity_at), { addSuffix: true }) }}
          </div>
        </div>

        <p class="text-stone-600 text-sm mb-4 line-clamp-2 leading-relaxed">
          {{ thread.body }}
        </p>

        <div class="flex flex-wrap items-center justify-between gap-4">
          <div class="flex flex-wrap gap-2">
            <ForumTag v-for="tag in thread.tags" :key="tag.id" :tag="tag" />
          </div>

          <div class="flex items-center gap-4 text-sm text-stone-500">
            <div class="flex items-center gap-1.5" title="Category">
              <span>{{ thread.category?.icon_emoji }}</span>
              <span class="font-medium">{{ thread.category?.name }}</span>
            </div>

            <div class="flex items-center gap-2 border-l border-stone-200 pl-4">
              <img
                v-if="thread.author.avatar_url"
                :src="thread.author.avatar_url"
                class="w-5 h-5 rounded-full bg-stone-200"
              />
              <div
                v-else
                class="w-5 h-5 rounded-full bg-soil-200 flex items-center justify-center text-soil-700 text-xs"
              >
                {{ thread.author.name.charAt(0).toUpperCase() }}
              </div>
              <span class="truncate max-w-[120px]">{{ thread.author.name }}</span>
            </div>

            <div class="flex items-center gap-1.5 border-l border-stone-200 pl-4" title="Replies">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                ></path>
              </svg>
              <span>{{ thread.reply_count }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppCard>
</template>
