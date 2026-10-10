<script setup>
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'
import AppButton from '../atoms/AppButton.vue'
const { t } = useI18n()
defineProps({ cooldown: { type: Number, default: 0 }, loading: Boolean })
defineEmits(['resend'])
</script>
<template>
  <div class="bg-harvest-50 px-4 py-3 border-b border-harvest-200 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto flex items-center justify-between flex-wrap gap-4">
      <div class="flex flex-1 min-w-0 items-start gap-3">
        <ExclamationTriangleIcon class="h-6 w-6 shrink-0 text-harvest-700" aria-hidden="true" />
        <p class="text-sm leading-relaxed text-harvest-800">
          <strong class="font-semibold">{{ t('shell.verify.title') }}</strong>
          {{ t('shell.verify.message') }}
        </p>
      </div>
      <AppButton
        variant="outline"
        size="sm"
        :loading="loading"
        :disabled="loading || cooldown > 0"
        @click="$emit('resend')"
        ><span v-if="loading">{{ t('shell.verify.sending') }}</span
        ><span v-else-if="cooldown > 0">{{ t('shell.verify.resend_in', { cooldown }) }}</span
        ><span v-else>{{ t('shell.verify.resend') }}</span></AppButton
      >
    </div>
  </div>
</template>
