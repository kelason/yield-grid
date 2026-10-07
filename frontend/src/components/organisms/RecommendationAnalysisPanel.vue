<script setup>
import AnalysisProgress from '../atoms/AnalysisProgress.vue'
import AppAlert from '../atoms/AppAlert.vue'
import AnalysisPreferencesForm from './AnalysisPreferencesForm.vue'
defineProps({
  analyzing: Boolean,
  location: { type: String, default: '' },
  taxonomy: { type: Object, default: null },
  error: { type: String, default: '' },
})
defineEmits(['update:preferences', 'request-analysis', 'dismiss-error'])
</script>
<template>
  <section aria-label="Crop analysis" class="space-y-6">
    <AppAlert v-if="error" type="warning" dismissible @dismiss="$emit('dismiss-error')">{{
      error
    }}</AppAlert>
    <AnalysisProgress v-if="analyzing" :location="location" />
    <AnalysisPreferencesForm
      :hidden="analyzing"
      :taxonomy="taxonomy"
      @update:preferences="$emit('update:preferences', $event)"
      @request-analysis="$emit('request-analysis', $event)"
    />
  </section>
</template>
