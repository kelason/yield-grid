<script setup>
import AppTextarea from '@/components/atoms/AppTextarea.vue'
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import FormField from '../molecules/FormField.vue'
import AppButton from '../atoms/AppButton.vue'
import AppAlert from '../atoms/AppAlert.vue'
import ConfirmModal from '../molecules/ConfirmModal.vue'
import { usePendingConfirmation } from '@/composables/useConfirmModal'
import { useApi } from '../../composables/useApi'

const CONTACT_NAME_MAX_LENGTH = 255
const CONTACT_EMAIL_MAX_LENGTH = 255
const CONTACT_SUBJECT_MAX_LENGTH = 255
const CONTACT_MESSAGE_MAX_LENGTH = 2000

const { t } = useI18n()
const api = useApi()

const form = ref({ name: '', email: '', subject: '', message: '' })
const loading = ref(false)
const successMessage = ref('')
const error = ref('')
const pendingConfirm = ref(null)
const { isExecuting, execute, cancel } = usePendingConfirmation(pendingConfirm)

const messageLength = computed(() => (form.value.message || '').length)

const confirmConfig = computed(() => ({
  title: t('public.contact.confirm_title'),
  message: t('public.contact.confirm_msg'),
  confirmText: t('public.contact.confirm_cta'),
  type: 'primary',
}))

function validate() {
  if (!form.value.name.trim()) return t('public.contact.err_name')
  if (form.value.name.length > CONTACT_NAME_MAX_LENGTH)
    return t('public.contact.err_name_max', { max: CONTACT_NAME_MAX_LENGTH })
  if (!form.value.email.trim()) return t('public.contact.err_email')
  if (form.value.email.length > CONTACT_EMAIL_MAX_LENGTH)
    return t('public.contact.err_email_max', { max: CONTACT_EMAIL_MAX_LENGTH })
  if (form.value.subject.length > CONTACT_SUBJECT_MAX_LENGTH)
    return t('public.contact.err_subject_max', { max: CONTACT_SUBJECT_MAX_LENGTH })
  if (!form.value.message.trim()) return t('public.contact.err_message')
  if (messageLength.value > CONTACT_MESSAGE_MAX_LENGTH)
    return t('public.contact.err_message_max', { max: CONTACT_MESSAGE_MAX_LENGTH })
  return ''
}

function handleSubmit() {
  error.value = validate()
  if (error.value) return
  pendingConfirm.value = { ...form.value }
}

const submitContact = () => execute(performContact)

async function performContact(payload) {
  loading.value = true
  error.value = ''
  successMessage.value = ''

  try {
    const response = await api.post('/contact', payload)
    successMessage.value = response.data.message
    form.value = { name: '', email: '', subject: '', message: '' }
  } catch (e) {
    error.value = e.response?.data?.message || t('public.contact.failed')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <form @submit.prevent="handleSubmit" class="space-y-6">
      <AppAlert v-if="successMessage" type="success">{{ successMessage }}</AppAlert>
      <AppAlert v-if="error" type="error">{{ error }}</AppAlert>

      <FormField
        id="contact-name"
        :label="t('public.contact.name_label')"
        v-model="form.name"
        :required="true"
        :maxlength="CONTACT_NAME_MAX_LENGTH"
      />
      <FormField
        id="contact-email"
        :label="t('public.contact.email_label')"
        type="email"
        v-model="form.email"
        :required="true"
        :maxlength="CONTACT_EMAIL_MAX_LENGTH"
      />
      <FormField
        id="contact-subject"
        :label="t('public.contact.subject_label')"
        v-model="form.subject"
        :maxlength="CONTACT_SUBJECT_MAX_LENGTH"
      />

      <div>
        <div class="flex items-center justify-between">
          <label for="contact-message" class="block text-sm font-medium text-soil-700">{{
            t('public.contact.message_label')
          }}</label>
          <span class="text-sm text-stone-600" id="contact-message-counter">
            {{ messageLength }} / {{ CONTACT_MESSAGE_MAX_LENGTH }}
          </span>
        </div>
        <div class="mt-1">
          <AppTextarea
            minlength="0"
            id="contact-message"
            aria-describedby="contact-message-counter"
            v-model="form.message"
            rows="4"
            required
            :maxlength="CONTACT_MESSAGE_MAX_LENGTH"
          ></AppTextarea>
        </div>
      </div>

      <AppButton type="submit" variant="primary" size="md" :loading="loading" class="w-full">
        {{ t('public.contact.submit') }}
      </AppButton>
    </form>

    <ConfirmModal
      :is-open="pendingConfirm !== null"
      :loading="isExecuting"
      :title="confirmConfig.title"
      :message="confirmConfig.message"
      :confirm-text="confirmConfig.confirmText"
      :type="confirmConfig.type"
      @confirm="submitContact"
      @cancel="cancel"
    />
  </div>
</template>
