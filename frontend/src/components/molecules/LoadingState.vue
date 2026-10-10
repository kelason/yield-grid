<script setup>
import AppSpinner from '../atoms/AppSpinner.vue'
import { useI18n } from 'vue-i18n'
const { t } = useI18n()
defineProps({ label: { type: String, default: '' } })
</script>
<template>
  <div role="status" aria-live="polite" class="text-stone-600">
    <span :class="$slots.default ? 'sr-only' : 'text-sm'">{{ label || t('shell.loading') }}</span>
    <slot><AppSpinner class="mt-2" size="md" /></slot>
  </div>
</template>
