<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { XCircleIcon } from '@heroicons/vue/24/outline'
import { useApi } from '@/composables/useApi'
import AppCard from '@/components/atoms/AppCard.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import LoadingState from '@/components/molecules/LoadingState.vue'
const route = useRoute()
const router = useRouter()
const api = useApi()
const { t } = useI18n()
const isCanceling = ref(true)
const error = ref('')
onMounted(async () => {
  const sessionId = route.query.session_id
  try {
    if (sessionId) await api.post(`/checkout/${sessionId}/cancel`)
  } catch {
    error.value = t('market.checkout.cancel_error')
  } finally {
    isCanceling.value = false
  }
})
</script>
<template>
  <div class="mx-auto max-w-xl px-4 py-16 sm:py-24">
    <AppCard padding="p-6 sm:p-10" class="text-center space-y-6"
      ><LoadingState v-if="isCanceling" :label="t('market.checkout.cancel_updating')" /><template
        v-else
        ><XCircleIcon class="mx-auto h-12 w-12 text-stone-600" aria-hidden="true" />
        <h1 class="font-serif text-3xl font-bold text-stone-900">
          {{ t('market.checkout.cancel_title') }}
        </h1>
        <p class="text-base text-stone-600 leading-relaxed">
          {{ t('market.checkout.cancel_desc') }}
        </p>
        <p v-if="error" role="alert" class="text-sm text-red-600">{{ error }}</p>
        <AppButton variant="outline" class="w-full" @click="router.push('/marketplace')">{{
          t('market.checkout.return_market')
        }}</AppButton></template
      ></AppCard
    >
  </div>
</template>
