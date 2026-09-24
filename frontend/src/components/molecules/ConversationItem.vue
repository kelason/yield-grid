<script setup>
import UnreadBadge from '../atoms/UnreadBadge.vue'
import OnlineIndicator from '../atoms/OnlineIndicator.vue'
import { formatDistanceToNow } from 'date-fns'

defineProps({
  conversation: {
    type: Object,
    required: true,
  },
  isActive: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['select'])
</script>

<template>
  <button
    @click="$emit('select', conversation.id)"
    class="flex items-center gap-3 w-full text-left p-3 rounded-xl transition-all duration-200 border"
    :class="
      isActive
        ? 'bg-moss-50 border-moss-200'
        : 'bg-transparent border-transparent hover:bg-stone-50'
    "
  >
    <div class="relative flex-shrink-0">
      <img
        v-if="conversation.other_participant?.avatar_url"
        :src="conversation.other_participant.avatar_url"
        class="w-12 h-12 rounded-full object-cover bg-stone-200"
      />
      <div
        v-else
        class="w-12 h-12 rounded-full bg-soil-200 flex items-center justify-center text-soil-700 text-lg font-bold"
      >
        {{ conversation.other_participant?.name.charAt(0).toUpperCase() || '?' }}
      </div>
      <!-- Assuming some logic for online status, using static true/false for now based on some prop or random -->
      <div class="absolute bottom-0 right-0">
        <OnlineIndicator :isOnline="true" />
      </div>
    </div>

    <div class="flex-grow min-w-0">
      <div class="flex justify-between items-baseline mb-0.5">
        <div class="font-bold text-stone-900 truncate pr-2 font-sans">
          {{ conversation.other_participant?.name || 'Unknown' }}
        </div>
        <div class="text-[10px] text-stone-400 whitespace-nowrap">
          {{
            conversation.last_message_at
              ? formatDistanceToNow(new Date(conversation.last_message_at), { addSuffix: false })
              : ''
          }}
        </div>
      </div>
      <div class="flex justify-between items-center gap-2">
        <div
          class="text-sm text-stone-500 truncate"
          :class="{ 'font-semibold text-stone-800': conversation.unread_count > 0 }"
        >
          {{ conversation.latest_message?.body || 'No messages yet' }}
        </div>
        <UnreadBadge :count="conversation.unread_count || 0" />
      </div>
    </div>
  </button>
</template>
