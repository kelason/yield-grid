<template>
  <main
    id="main-content"
    tabindex="-1"
    class="min-h-screen flex items-center justify-center bg-stone-50 px-4 py-16 focus:outline-none"
  >
    <AppCard padding="p-6 sm:p-10" class="w-full max-w-2xl"
      ><p class="mb-5 text-sm font-semibold text-moss-700">404</p>
      <PageHeader :title="t('errors.notfound_title')" :description="t('errors.notfound_desc')" />
      <div class="mt-8 flex flex-wrap gap-3">
        <AppButton @click="goHome">{{ t('errors.home') }}</AppButton
        ><AppButton variant="outline" @click="goContact">{{ t('errors.contact') }}</AppButton>
      </div></AppCard
    >
  </main>
</template>
<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import AppCard from '@/components/atoms/AppCard.vue'
import PageHeader from '@/components/molecules/PageHeader.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import { useAuthStore } from '@/stores/auth'
import { roleHomeTarget } from '@/constants/roles'

const { t } = useI18n()
const router = useRouter()
const authStore = useAuthStore()

const homeTarget = computed(() => {
  if (!authStore.isAuthenticated) return '/'
  return roleHomeTarget(authStore.userRole)
})

const goHome = () => router.push(homeTarget.value)
const goContact = () => router.push('/contact')
</script>
