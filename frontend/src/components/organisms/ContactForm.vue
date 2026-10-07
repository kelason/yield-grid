<script setup>
import AppTextarea from '@/components/atoms/AppTextarea.vue'
import { ref, computed } from 'vue'
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

const api = useApi()

const form = ref({ name: '', email: '', subject: '', message: '' })
const loading = ref(false)
const successMessage = ref('')
const error = ref('')
const pendingConfirm = ref(null)
const { isExecuting, execute, cancel } = usePendingConfirmation(pendingConfirm)

const messageLength = computed(() => (form.value.message || '').length)

const confirmConfig = computed(() => ({
  title: 'Send this message?',
  message: 'Your message will be sent to the YieldGrid team. We will reply to your email address.',
  confirmText: 'Send message',
  type: 'primary',
}))

function validate() {
  if (!form.value.name.trim()) return 'Please enter your name.'
  if (form.value.name.length > CONTACT_NAME_MAX_LENGTH)
    return `Name must be at most ${CONTACT_NAME_MAX_LENGTH} characters.`
  if (!form.value.email.trim()) return 'Please enter your email address.'
  if (form.value.email.length > CONTACT_EMAIL_MAX_LENGTH)
    return `Email must be at most ${CONTACT_EMAIL_MAX_LENGTH} characters.`
  if (form.value.subject.length > CONTACT_SUBJECT_MAX_LENGTH)
    return `Subject must be at most ${CONTACT_SUBJECT_MAX_LENGTH} characters.`
  if (!form.value.message.trim()) return 'Please enter a message.'
  if (messageLength.value > CONTACT_MESSAGE_MAX_LENGTH)
    return `Message must be at most ${CONTACT_MESSAGE_MAX_LENGTH} characters.`
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
    error.value = e.response?.data?.message || 'Failed to send message.'
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
        label="Name"
        v-model="form.name"
        :required="true"
        :maxlength="CONTACT_NAME_MAX_LENGTH"
      />
      <FormField
        id="contact-email"
        label="Email"
        type="email"
        v-model="form.email"
        :required="true"
        :maxlength="CONTACT_EMAIL_MAX_LENGTH"
      />
      <FormField
        id="contact-subject"
        label="Subject"
        v-model="form.subject"
        :maxlength="CONTACT_SUBJECT_MAX_LENGTH"
      />

      <div>
        <div class="flex items-center justify-between">
          <label for="contact-message" class="block text-sm font-medium text-soil-700"
            >Message</label
          >
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
        Send Message
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
