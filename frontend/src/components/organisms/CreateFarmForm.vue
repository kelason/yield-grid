<script setup>
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import FormField from '../molecules/FormField.vue'
import AppButton from '../atoms/AppButton.vue'
import AppAlert from '../atoms/AppAlert.vue'
import ConfirmModal from '../molecules/ConfirmModal.vue'

const FARM_NAME_MAX_LENGTH = 255
const FARM_ADDRESS_MAX_LENGTH = 255
const FARM_CITY_MAX_LENGTH = 255
const FARM_STATE_MAX_LENGTH = 255
const FARM_COUNTRY_MAX_LENGTH = 255
const FARM_ZIP_MAX_LENGTH = 50
const FARM_TOTAL_AREA_MIN = 0
const FARM_TOTAL_AREA_MAX = 1000000

const emit = defineEmits(['submit', 'cancel'])
const { t } = useI18n()
const props = defineProps({
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
})

const form = ref({
  name: '',
  address: '',
  city: '',
  state: '',
  country: '',
  zip: '',
  total_area: '',
})

const validationError = ref('')
const pendingConfirm = ref(false)
const confirmed = ref(false)
watch(
  () => props.loading,
  (loading, wasLoading) => {
    if (wasLoading && !loading) {
      pendingConfirm.value = false
      confirmed.value = false
    }
  },
)

const confirmConfig = computed(() => ({
  title: t('farmer.farm_form.confirm_title'),
  message: t('farmer.farm_form.confirm_message', { name: form.value.name }),
  confirmText: t('farmer.farm_form.confirm_button'),
  type: 'primary',
}))

function validate() {
  if (!form.value.name.trim()) return t('farmer.farm_form.name_required')
  if (form.value.name.length > FARM_NAME_MAX_LENGTH)
    return t('farmer.farm_form.name_too_long', { max: FARM_NAME_MAX_LENGTH })
  if (form.value.address.length > FARM_ADDRESS_MAX_LENGTH)
    return t('farmer.farm_form.address_too_long', { max: FARM_ADDRESS_MAX_LENGTH })
  if (form.value.city.length > FARM_CITY_MAX_LENGTH)
    return t('farmer.farm_form.city_too_long', { max: FARM_CITY_MAX_LENGTH })
  if (form.value.state.length > FARM_STATE_MAX_LENGTH)
    return t('farmer.farm_form.state_too_long', { max: FARM_STATE_MAX_LENGTH })
  if (form.value.country.length > FARM_COUNTRY_MAX_LENGTH)
    return t('farmer.farm_form.country_too_long', { max: FARM_COUNTRY_MAX_LENGTH })
  if (form.value.zip.length > FARM_ZIP_MAX_LENGTH)
    return t('farmer.farm_form.zip_too_long', { max: FARM_ZIP_MAX_LENGTH })
  if (form.value.total_area !== '' && form.value.total_area !== null) {
    const area = Number(form.value.total_area)
    if (Number.isNaN(area)) return t('farmer.farm_form.area_number')
    if (area < FARM_TOTAL_AREA_MIN || area > FARM_TOTAL_AREA_MAX)
      return t('farmer.farm_form.area_range', {
        min: FARM_TOTAL_AREA_MIN,
        max: FARM_TOTAL_AREA_MAX,
      })
  }
  return ''
}

function handleSubmit() {
  if (props.loading || confirmed.value) return
  validationError.value = validate()
  if (validationError.value) return
  pendingConfirm.value = true
}

function confirmSubmit() {
  if (!pendingConfirm.value || confirmed.value || props.loading) return
  confirmed.value = true
  emit('submit', { ...form.value })
}
</script>

<template>
  <div>
    <form @submit.prevent="handleSubmit" class="space-y-4">
      <AppAlert v-if="error" type="error">{{ error }}</AppAlert>
      <AppAlert v-if="validationError" type="error">{{ validationError }}</AppAlert>

      <FormField
        id="farm-name"
        :label="t('farmer.farm_form.name')"
        v-model="form.name"
        required
        :maxlength="FARM_NAME_MAX_LENGTH"
      />

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <FormField
          id="farm-address"
          :label="t('farmer.farm_form.address')"
          v-model="form.address"
          :maxlength="FARM_ADDRESS_MAX_LENGTH"
        />
        <FormField
          id="farm-city"
          :label="t('farmer.farm_form.city')"
          v-model="form.city"
          :maxlength="FARM_CITY_MAX_LENGTH"
        />
        <FormField
          id="farm-state"
          :label="t('farmer.farm_form.state')"
          v-model="form.state"
          :maxlength="FARM_STATE_MAX_LENGTH"
        />
        <FormField
          id="farm-country"
          :label="t('farmer.farm_form.country')"
          v-model="form.country"
          :placeholder="t('farmer.farm_form.country_placeholder')"
          :maxlength="FARM_COUNTRY_MAX_LENGTH"
        />
        <FormField
          id="farm-zip"
          :label="t('farmer.farm_form.zip')"
          v-model="form.zip"
          :maxlength="FARM_ZIP_MAX_LENGTH"
        />
      </div>

      <FormField
        id="farm-area"
        :label="t('farmer.farm_form.area')"
        type="number"
        v-model="form.total_area"
        :min="FARM_TOTAL_AREA_MIN"
        :max="FARM_TOTAL_AREA_MAX"
      />

      <div class="flex justify-end space-x-3 pt-4">
        <AppButton type="button" variant="ghost" @click="$emit('cancel')">
          {{ t('shell.cancel') }}
        </AppButton>
        <AppButton type="submit" variant="primary" :loading="loading">
          {{ t('farmer.farm_form.save') }}
        </AppButton>
      </div>
    </form>

    <ConfirmModal
      :is-open="pendingConfirm"
      :loading="loading || confirmed"
      :title="confirmConfig.title"
      :message="confirmConfig.message"
      :confirm-text="confirmConfig.confirmText"
      :type="confirmConfig.type"
      @confirm="confirmSubmit"
      @cancel="!confirmed && (pendingConfirm = false)"
    />
  </div>
</template>
