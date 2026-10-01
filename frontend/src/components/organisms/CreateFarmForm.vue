<script setup>
import { ref, computed } from 'vue'
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
defineProps({
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

const confirmConfig = computed(() => ({
  title: 'Create this farm?',
  message: `Create the farm "${form.value.name}"? You can add plots to it afterwards.`,
  confirmText: 'Create farm',
  type: 'primary',
}))

function validate() {
  if (!form.value.name.trim()) return 'Please enter a farm name.'
  if (form.value.name.length > FARM_NAME_MAX_LENGTH)
    return `Farm name must be at most ${FARM_NAME_MAX_LENGTH} characters.`
  if (form.value.address.length > FARM_ADDRESS_MAX_LENGTH)
    return `Address must be at most ${FARM_ADDRESS_MAX_LENGTH} characters.`
  if (form.value.city.length > FARM_CITY_MAX_LENGTH)
    return `City must be at most ${FARM_CITY_MAX_LENGTH} characters.`
  if (form.value.state.length > FARM_STATE_MAX_LENGTH)
    return `State/Province must be at most ${FARM_STATE_MAX_LENGTH} characters.`
  if (form.value.country.length > FARM_COUNTRY_MAX_LENGTH)
    return `Country must be at most ${FARM_COUNTRY_MAX_LENGTH} characters.`
  if (form.value.zip.length > FARM_ZIP_MAX_LENGTH)
    return `Zip/Postal code must be at most ${FARM_ZIP_MAX_LENGTH} characters.`
  if (form.value.total_area !== '' && form.value.total_area !== null) {
    const area = Number(form.value.total_area)
    if (Number.isNaN(area)) return 'Total area must be a number.'
    if (area < FARM_TOTAL_AREA_MIN || area > FARM_TOTAL_AREA_MAX)
      return `Total area must be between ${FARM_TOTAL_AREA_MIN} and ${FARM_TOTAL_AREA_MAX} hectares.`
  }
  return ''
}

function handleSubmit() {
  validationError.value = validate()
  if (validationError.value) return
  pendingConfirm.value = true
}

function confirmSubmit() {
  pendingConfirm.value = false
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
        label="Farm Name"
        v-model="form.name"
        required
        :maxlength="FARM_NAME_MAX_LENGTH"
      />

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <FormField
          id="farm-address"
          label="Address"
          v-model="form.address"
          :maxlength="FARM_ADDRESS_MAX_LENGTH"
        />
        <FormField
          id="farm-city"
          label="City"
          v-model="form.city"
          :maxlength="FARM_CITY_MAX_LENGTH"
        />
        <FormField
          id="farm-state"
          label="State/Province"
          v-model="form.state"
          :maxlength="FARM_STATE_MAX_LENGTH"
        />
        <FormField
          id="farm-country"
          label="Country"
          v-model="form.country"
          placeholder="e.g. Philippines, United States"
          :maxlength="FARM_COUNTRY_MAX_LENGTH"
        />
        <FormField
          id="farm-zip"
          label="Zip/Postal Code"
          v-model="form.zip"
          :maxlength="FARM_ZIP_MAX_LENGTH"
        />
      </div>

      <FormField
        id="farm-area"
        label="Total Area (Hectares)"
        type="number"
        v-model="form.total_area"
        :min="FARM_TOTAL_AREA_MIN"
        :max="FARM_TOTAL_AREA_MAX"
      />

      <div class="flex justify-end space-x-3 pt-4">
        <AppButton type="button" variant="ghost" @click="$emit('cancel')">Cancel</AppButton>
        <AppButton type="submit" variant="primary" :loading="loading">Save Farm</AppButton>
      </div>
    </form>

    <ConfirmModal
      :is-open="pendingConfirm"
      :title="confirmConfig.title"
      :message="confirmConfig.message"
      :confirm-text="confirmConfig.confirmText"
      :type="confirmConfig.type"
      @confirm="confirmSubmit"
      @cancel="pendingConfirm = false"
    />
  </div>
</template>
