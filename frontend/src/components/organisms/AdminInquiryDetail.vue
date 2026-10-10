<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/atoms/AppButton.vue'
import AppCard from '@/components/atoms/AppCard.vue'
import FormField from '@/components/molecules/FormField.vue'
import EmptyState from '@/components/molecules/EmptyState.vue'
import {
  CONTACT_INQUIRY_STATUS,
  CONTACT_REPLY_BODY_MAX_LENGTH,
  CONTACT_REPLY_STALE_SECONDS,
  REPLY_DELIVERY_LABEL_KEYS,
  REPLY_DELIVERY_STATUS,
} from '@/constants/admin'

const { t } = useI18n()

const MS_PER_SECOND = 1000

const DELIVERY_BADGE_CLASSES = {
  queued: 'bg-dew-100 text-dew-800',
  sending: 'bg-harvest-100 text-harvest-800',
  sent: 'bg-moss-100 text-moss-800',
  failed: 'bg-red-100 text-red-800',
}

const props = defineProps({
  message: { type: Object, default: null },
  replies: { type: Array, default: () => [] },
  draft: { type: String, default: '' },
  draftError: { type: String, default: '' },
  busy: { type: Boolean, default: false },
})

const emit = defineEmits(['update:draft', 'transition', 'reply', 'retry'])

const safeReplies = computed(() =>
  (Array.isArray(props.replies) ? props.replies : []).filter(
    (entry) => entry && typeof entry === 'object',
  ),
)

const hasOutstandingDelivery = computed(() =>
  safeReplies.value.some(
    (reply) =>
      reply?.delivery_status === REPLY_DELIVERY_STATUS.QUEUED ||
      reply?.delivery_status === REPLY_DELIVERY_STATUS.SENDING,
  ),
)

const isClosed = computed(() => props.message?.status === CONTACT_INQUIRY_STATUS.CLOSED)

function deliveryLabel(reply) {
  return t(REPLY_DELIVERY_LABEL_KEYS[reply?.delivery_status] ?? 'admin.inquiry.delivery_unknown')
}

function deliveryBadgeClass(reply) {
  return DELIVERY_BADGE_CLASSES[reply?.delivery_status] ?? 'bg-stone-200 text-stone-700'
}

function replyAgeSeconds(reply) {
  const created = Date.parse(reply?.created_at ?? '')
  if (Number.isNaN(created)) return 0
  return Math.floor((Date.now() - created) / MS_PER_SECOND)
}

function isStale(reply) {
  return replyAgeSeconds(reply) > CONTACT_REPLY_STALE_SECONDS
}

function isRetryable(reply) {
  return (
    reply?.delivery_status === REPLY_DELIVERY_STATUS.FAILED ||
    ((reply?.delivery_status === REPLY_DELIVERY_STATUS.QUEUED ||
      reply?.delivery_status === REPLY_DELIVERY_STATUS.SENDING) &&
      isStale(reply))
  )
}

function showRetry(reply) {
  return (
    reply?.delivery_status === REPLY_DELIVERY_STATUS.FAILED ||
    reply?.delivery_status === REPLY_DELIVERY_STATUS.QUEUED ||
    reply?.delivery_status === REPLY_DELIVERY_STATUS.SENDING
  )
}

function isStaleSending(reply) {
  return reply?.delivery_status === REPLY_DELIVERY_STATUS.SENDING && isStale(reply)
}
</script>

<template>
  <EmptyState
    v-if="!message"
    :title="t('admin.inquiry.select_title')"
    :description="t('admin.inquiry.select_desc')"
  />
  <AppCard v-else padding="p-5 sm:p-6">
    <div class="space-y-6">
      <div>
        <h2 class="font-serif text-2xl font-bold break-words text-stone-900">
          {{ message?.subject || t('admin.inquiry.untitled') }}
        </h2>
        <p class="mt-1 break-words text-sm text-stone-600">
          {{
            t('admin.inquiry.from', { name: message?.name || t('admin.inquiry.unknown_sender') })
          }}
          <span v-if="message?.email">({{ message.email }})</span>
        </p>
        <p class="mt-3 text-base leading-relaxed break-words whitespace-pre-line text-stone-900">
          {{ message?.message || t('admin.inquiry.no_message') }}
        </p>
      </div>

      <div class="border-t border-stone-200 pt-5">
        <h3 class="font-serif text-xl font-bold text-stone-900">
          {{ t('admin.inquiry.history') }}
        </h3>
        <p v-if="safeReplies.length === 0" class="mt-2 text-sm text-stone-600">
          {{ t('admin.inquiry.no_replies') }}
        </p>
        <ul v-else class="mt-3 space-y-4">
          <li
            v-for="(reply, index) in safeReplies"
            :key="reply?.id ?? `reply-${index}`"
            class="rounded-2xl border border-stone-200 p-4"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <span
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="deliveryBadgeClass(reply)"
              >
                {{ deliveryLabel(reply) }}
              </span>
              <AppButton
                v-if="showRetry(reply)"
                variant="outline"
                size="sm"
                :disabled="busy || !isRetryable(reply)"
                :aria-label="t('admin.inquiry.retry_aria', { id: reply?.id })"
                @click="emit('retry', reply)"
              >
                {{ t('shell.retry') }}
              </AppButton>
            </div>
            <p
              class="mt-2 text-base leading-relaxed break-words whitespace-pre-line text-stone-900"
            >
              {{ reply?.body || t('admin.inquiry.no_reply_body') }}
            </p>
            <p v-if="isStaleSending(reply)" class="mt-2 text-sm text-harvest-800">
              {{ t('admin.inquiry.dup_caution') }}
            </p>
            <p v-else-if="reply?.error_code" class="mt-2 text-sm text-stone-600">
              {{ t('admin.inquiry.delivery_failed_hint', { code: reply.error_code }) }}
            </p>
          </li>
        </ul>
      </div>

      <div class="border-t border-stone-200 pt-5">
        <h3 class="font-serif text-xl font-bold text-stone-900">
          {{ t('admin.inquiry.composer_title') }}
        </h3>
        <p v-if="isClosed" class="mt-2 text-sm text-stone-600">
          {{ t('admin.inquiry.closed_note') }}
        </p>
        <div v-else class="mt-3 space-y-3">
          <FormField
            id="admin-inquiry-reply"
            :model-value="draft"
            :label="t('admin.inquiry.reply_label')"
            multiline
            required
            :maxlength="CONTACT_REPLY_BODY_MAX_LENGTH"
            :error="draftError"
            :hint="t('admin.inquiry.reply_hint')"
            @update:model-value="emit('update:draft', $event)"
          />
          <div class="flex flex-wrap items-center gap-3">
            <AppButton
              variant="primary"
              :disabled="busy || hasOutstandingDelivery"
              @click="emit('reply')"
            >
              {{ t('admin.inquiry.send') }}
            </AppButton>
            <p v-if="hasOutstandingDelivery" class="text-sm text-stone-600">
              {{ t('admin.inquiry.outstanding') }}
            </p>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap gap-3 border-t border-stone-200 pt-5">
        <AppButton
          v-if="message?.status === CONTACT_INQUIRY_STATUS.UNREAD"
          variant="outline"
          :disabled="busy"
          @click="emit('transition', 'read')"
        >
          {{ t('admin.inquiry.mark_read') }}
        </AppButton>
        <AppButton
          v-if="!isClosed"
          variant="outline"
          :disabled="busy || hasOutstandingDelivery"
          @click="emit('transition', 'close')"
        >
          {{ t('admin.inquiry.close_btn') }}
        </AppButton>
        <AppButton
          v-if="isClosed"
          variant="outline"
          :disabled="busy"
          @click="emit('transition', 'reopen')"
        >
          {{ t('admin.inquiry.reopen_btn') }}
        </AppButton>
        <p v-if="!isClosed && hasOutstandingDelivery" class="w-full text-sm text-stone-600">
          {{ t('admin.inquiry.resolve_first') }}
        </p>
      </div>
    </div>
  </AppCard>
</template>
