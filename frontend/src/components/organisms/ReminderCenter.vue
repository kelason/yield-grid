<script setup>
import { useI18n } from 'vue-i18n'
import AppCard from '@/components/atoms/AppCard.vue'
import EmptyState from '@/components/molecules/EmptyState.vue'

defineProps({
  reminders: { type: Array, required: true },
})

const { t } = useI18n()

const REMINDER_ACCENTS = {
  enrollment_window: 'bg-dew-50 text-dew-700',
  notice_of_loss_deadline: 'bg-harvest-100 text-harvest-800',
  claim_followup: 'bg-dew-50 text-dew-700',
  renewal: 'bg-harvest-100 text-harvest-800',
}

const accentFor = (type) => REMINDER_ACCENTS[type] || 'bg-stone-100 text-stone-800'
</script>

<template>
  <AppCard>
    <h2 class="font-serif text-2xl font-bold text-stone-900">
      {{ t('insurance.reminders.title') }}
    </h2>
    <div v-if="reminders.length === 0" data-testid="reminders-empty" class="mt-4">
      <EmptyState
        :title="t('insurance.reminders.title')"
        :description="t('insurance.reminders.empty')"
      />
    </div>
    <ul v-else class="mt-4 space-y-3" aria-live="polite">
      <li
        v-for="(reminder, index) in reminders"
        :key="`${reminder.type}-${index}`"
        data-testid="reminder-item"
        class="rounded-2xl border border-stone-200 bg-stone-50 p-4 transition-all duration-300 hover:shadow-organic"
      >
        <div class="flex items-center gap-2">
          <span
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
            :class="accentFor(reminder.type)"
          >
            {{ t(reminder.title_key) }}
          </span>
        </div>
        <p class="mt-2 text-base text-stone-600 font-light leading-relaxed">
          {{ t(reminder.message_key, reminder.params || {}) }}
        </p>
      </li>
    </ul>
  </AppCard>
</template>
