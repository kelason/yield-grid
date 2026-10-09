<script setup>
import { useId } from 'vue'
import { ExclamationTriangleIcon, CheckCircleIcon } from '@heroicons/vue/24/outline'
import AppModal from './AppModal.vue'
import AppButton from '../atoms/AppButton.vue'
import { useI18n } from 'vue-i18n'
const { t } = useI18n()
const props = defineProps({
  isOpen: { type: Boolean, required: true },
  title: { type: String, default: '' },
  message: { type: String, required: true },
  confirmText: { type: String, default: '' },
  cancelText: { type: String, default: '' },
  type: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'danger'].includes(value),
  },
  loading: Boolean,
})
const emit = defineEmits(['confirm', 'cancel'])
const messageId = `confirmation-${useId()}`
function handleConfirm() {
  if (!props.loading) emit('confirm')
}
function handleCancel() {
  if (!props.loading) emit('cancel')
}
</script>
<template>
  <AppModal
    :is-open="isOpen"
    :title="title || t('shell.confirm_action')"
    :describedby="messageId"
    size="sm"
    :busy="loading"
    @close="handleCancel"
  >
    <div class="flex items-start gap-4">
      <component
        :is="type === 'danger' ? ExclamationTriangleIcon : CheckCircleIcon"
        :class="['h-6 w-6 shrink-0', type === 'danger' ? 'text-red-600' : 'text-moss-600']"
        aria-hidden="true"
      />
      <p :id="messageId" class="text-base leading-relaxed text-stone-600">{{ message }}</p>
    </div>
    <template #footer>
      <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <AppButton variant="secondary" :disabled="loading" autofocus @click="handleCancel">{{
          cancelText || t('shell.cancel')
        }}</AppButton>
        <AppButton
          :variant="type === 'danger' ? 'danger' : 'primary'"
          :loading="loading"
          @click="handleConfirm"
          >{{ confirmText || t('shell.confirm') }}</AppButton
        >
      </div>
    </template>
  </AppModal>
</template>
