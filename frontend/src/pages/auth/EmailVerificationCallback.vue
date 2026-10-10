<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import AppButton from '@/components/atoms/AppButton.vue'
import LoadingState from '@/components/molecules/LoadingState.vue'
const REDIRECT_DELAY_MS = 2000
const MILLISECONDS_PER_SECOND = 1000
const ERR_NO_URL = 'NO_URL'
const ERR_EXPIRED = 'EXPIRED'
const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const loading = ref(true)
const success = ref(false)
const errorMessage = ref('')
let redirectTimer
let disposed = false
function verificationFailure(code) {
  const failure = new Error(code)
  failure.code = code
  return failure
}
function verificationUrl() {
  const original = route.query.verify_url
  if (!original) throw verificationFailure(ERR_NO_URL)
  try {
    const url = new URL(original)
    for (const key in route.query) {
      if (key !== 'verify_url' && !url.searchParams.has(key))
        url.searchParams.append(key, route.query[key])
    }
    const expires = url.searchParams.get('expires')
    if (expires && Date.now() / MILLISECONDS_PER_SECOND > parseInt(expires))
      throw verificationFailure(ERR_EXPIRED)
    return url.toString()
  } catch (error) {
    if (error.code === ERR_EXPIRED) throw error
    return original
  }
}
function redirect() {
  router.push(authStore.isAuthenticated ? { path: '/dashboard' } : { name: 'login' })
}
onMounted(async () => {
  try {
    await authStore.verifyEmail(verificationUrl())
    if (disposed) return
    success.value = true
    redirectTimer = setTimeout(redirect, REDIRECT_DELAY_MS)
  } catch (error) {
    if (!disposed)
      errorMessage.value =
        error.code === ERR_NO_URL ? t('auth.callback.err_no_url') : t('auth.callback.err_expired')
  } finally {
    if (!disposed) loading.value = false
  }
})
onBeforeUnmount(() => {
  disposed = true
  clearTimeout(redirectTimer)
})
</script>
<template>
  <div class="text-center space-y-5">
    <LoadingState v-if="loading" :label="t('auth.callback.loading')" />
    <template v-else-if="success"
      ><CheckCircleIcon class="mx-auto h-12 w-12 text-moss-700" aria-hidden="true" />
      <h2 class="font-serif text-2xl font-bold text-stone-900">
        {{ t('auth.callback.success_title') }}
      </h2>
      <p class="text-base text-stone-600 leading-relaxed">
        {{ t('auth.callback.success_body') }}
      </p></template
    >
    <template v-else
      ><XCircleIcon class="mx-auto h-12 w-12 text-red-600" aria-hidden="true" />
      <h2 class="font-serif text-2xl font-bold text-stone-900">
        {{ t('auth.callback.fail_title') }}
      </h2>
      <p role="alert" class="text-base text-stone-600 leading-relaxed">
        {{ errorMessage || t('auth.callback.fail_default') }}
      </p>
      <AppButton @click="router.push('/dashboard')">{{
        t('auth.callback.dash')
      }}</AppButton></template
    >
  </div>
</template>
