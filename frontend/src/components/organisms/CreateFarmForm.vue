<script setup>
import { ref } from 'vue'
import FormField from '../molecules/FormField.vue'
import AppButton from '../atoms/AppButton.vue'
import AppAlert from '../atoms/AppAlert.vue'

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

function handleSubmit() {
  emit('submit', { ...form.value })
}
</script>

<template>
  <form @submit.prevent="handleSubmit" class="space-y-4">
    <AppAlert v-if="error" type="error">{{ error }}</AppAlert>

    <FormField id="farm-name" label="Farm Name" v-model="form.name" required />

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <FormField id="farm-address" label="Address" v-model="form.address" />
      <FormField id="farm-city" label="City" v-model="form.city" />
      <FormField id="farm-state" label="State/Province" v-model="form.state" />
      <FormField
        id="farm-country"
        label="Country"
        v-model="form.country"
        placeholder="e.g. Philippines, United States"
      />
      <FormField id="farm-zip" label="Zip/Postal Code" v-model="form.zip" />
    </div>

    <FormField
      id="farm-area"
      label="Total Area (Hectares)"
      type="number"
      v-model="form.total_area"
    />

    <div class="flex justify-end space-x-3 pt-4">
      <AppButton type="button" variant="ghost" @click="$emit('cancel')">Cancel</AppButton>
      <AppButton type="submit" variant="primary" :loading="loading">Save Farm</AppButton>
    </div>
  </form>
</template>
