<template>
  <div class="space-y-6">
    <header>
      <h1 class="font-serif text-5xl font-bold tracking-tight text-stone-900">
        Crop compatibility
      </h1>
      <p class="text-base text-stone-600 font-light leading-relaxed mt-2 max-w-2xl">
        Pick any two crops to see whether they follow each other well in rotation and whether they
        grow well side by side.
      </p>
    </header>

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
