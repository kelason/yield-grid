<script setup>
import { useI18n } from 'vue-i18n'
import AppSpinner from './AppSpinner.vue'
const { t } = useI18n()
defineProps({ location: { type: String, default: '' } })
</script>
<template>
  <div
    role="status"
    aria-live="polite"
    class="flex flex-col items-center justify-center gap-4 p-6 bg-dew-50 rounded-2xl border border-dew-200 text-center"
  >
    <AppSpinner size="md" class="text-dew-700" />
    <h2 class="font-serif text-2xl font-bold text-dew-800">
      {{
        t('farmer.analysis.progress_title', {
          location: location || t('farmer.analysis.region_fallback'),
        })
      }}
    </h2>
    <p class="text-dew-700 text-sm max-w-lg leading-relaxed">
      {{
        t('farmer.analysis.progress_desc', {
          location: location || t('farmer.analysis.location_fallback'),
        })
      }}
    </p>
  </div>
</template>
