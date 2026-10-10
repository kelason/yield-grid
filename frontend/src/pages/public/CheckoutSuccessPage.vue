<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { CheckCircleIcon, ClockIcon } from '@heroicons/vue/24/outline'
import { useApi } from '@/composables/useApi'
import { PAYMENT_STATUS } from '@/constants/payment'
import AppCard from '@/components/atoms/AppCard.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import LoadingState from '@/components/molecules/LoadingState.vue'
const VERIFYING = 'verifying'
const route = useRoute()
const router = useRouter()
const api = useApi()
const { t } = useI18n()
const status = ref(VERIFYING)
onMounted(async () => {
  const sessionId = route.query.session_id
  if (!sessionId) {
    status.value = PAYMENT_STATUS.PENDING
    return
  }
  try {
    const response = await api.get(`/checkout/${sessionId}/verify`)
    status.value = response.data.status || PAYMENT_STATUS.PENDING
  } catch {
    status.value = PAYMENT_STATUS.PENDING
  }
})
</script>
<template>
  <div class="mx-auto max-w-xl px-4 py-16 sm:py-24">
    <AppCard padding="p-6 sm:p-10" class="text-center space-y-6">
      <template v-if="status === VERIFYING"
        ><LoadingState :label="t('market.checkout.success_verifying')" />
        <h1 class="font-serif text-3xl font-bold text-stone-900">
          {{ t('market.checkout.success_verifying') }}
        </h1>
        <p class="text-base text-stone-600 leading-relaxed">
          {{ t('market.checkout.success_verifying_desc') }}
        </p></template
      >
      <template v-else-if="status === PAYMENT_STATUS.COMPLETED"
        ><CheckCircleIcon class="mx-auto h-12 w-12 text-moss-700" aria-hidden="true" />
        <h1 class="font-serif text-3xl font-bold text-stone-900">
          {{ t('market.checkout.success_title') }}
        </h1>
        <p class="text-base text-stone-600 leading-relaxed">
          {{ t('market.checkout.success_desc') }}
        </p></template
      >
      <template v-else
        ><ClockIcon class="mx-auto h-12 w-12 text-harvest-700" aria-hidden="true" />
        <h1 class="font-serif text-3xl font-bold text-stone-900">
          {{ t('market.checkout.pending_title') }}
        </h1>
        <p class="text-base text-stone-600 leading-relaxed">
          {{ t('market.checkout.pending_desc') }}
        </p></template
      >
      <AppButton
        v-if="status !== VERIFYING"
        class="w-full"
        @click="router.push('/dashboard/buyer/purchases')"
        >{{ t('market.checkout.view_purchases') }}</AppButton
      >
    </AppCard>
  </div>
</template>
