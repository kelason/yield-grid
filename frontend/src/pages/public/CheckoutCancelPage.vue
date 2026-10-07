<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { XCircleIcon } from '@heroicons/vue/24/outline'
import { useApi } from '@/composables/useApi'
import AppCard from '@/components/atoms/AppCard.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import LoadingState from '@/components/molecules/LoadingState.vue'
const route = useRoute()
const router = useRouter()
const api = useApi()
const isCanceling = ref(true)
const error = ref('')
onMounted(async () => {
  const sessionId = route.query.session_id
  try {
    if (sessionId) await api.post(`/checkout/${sessionId}/cancel`)
  } catch {
    error.value =
      'We could not update this checkout. Check your purchases for the latest payment status.'
  } finally {
    isCanceling.value = false
  }
})
</script>
<template>
  <div class="mx-auto max-w-xl px-4 py-16 sm:py-24">
    <AppCard padding="p-6 sm:p-10" class="text-center space-y-6"
      ><LoadingState v-if="isCanceling" label="Updating checkout" /><template v-else
        ><XCircleIcon class="mx-auto h-12 w-12 text-stone-600" aria-hidden="true" />
        <h1 class="font-serif text-3xl font-bold text-stone-900">Payment Cancelled</h1>
        <p class="text-base text-stone-600 leading-relaxed">
          You left checkout. Check your purchases for the latest payment status.
        </p>
        <p v-if="error" role="alert" class="text-sm text-red-600">{{ error }}</p>
        <AppButton variant="outline" class="w-full" @click="router.push('/marketplace')"
          >Return to Marketplace</AppButton
        ></template
      ></AppCard
    >
  </div>
</template>
