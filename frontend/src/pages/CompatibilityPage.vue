<template>
  <div class="space-y-6">
    <PageHeader :title="t('farmer.compat.title')" :description="t('farmer.compat.description')" />

    <CompatibilityChecker
      :taxonomy="store.taxonomy"
      :result="result"
      :checking="checking"
      :error="error"
      @check-compatibility="handleCheck"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRecommendationStore } from '../stores/recommendationStore'
import PageHeader from '@/components/molecules/PageHeader.vue'
import CompatibilityChecker from '../components/organisms/CompatibilityChecker.vue'

const store = useRecommendationStore()
const { t } = useI18n()

const result = ref(null)
const checking = ref(false)
const error = ref('')

onMounted(() => {
  store.fetchTaxonomy()
})

async function handleCheck({ cropA, cropB }) {
  checking.value = true
  error.value = ''
  result.value = null

  try {
    result.value = await store.checkCompatibility(cropA, cropB)
  } catch {
    error.value = t('farmer.compat.check_error')
  } finally {
    checking.value = false
  }
}
</script>
