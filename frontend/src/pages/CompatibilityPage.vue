<template>
  <div class="space-y-6">
    <PageHeader
      title="Crop compatibility"
      description="Pick two crops to check whether they follow each other well in rotation or grow well side by side."
    />

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
import { useRecommendationStore } from '../stores/recommendationStore'
import PageHeader from '@/components/molecules/PageHeader.vue'
import CompatibilityChecker from '../components/organisms/CompatibilityChecker.vue'

const store = useRecommendationStore()

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
    error.value = 'Could not check compatibility. Please try again.'
  } finally {
    checking.value = false
  }
}
</script>
