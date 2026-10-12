<script setup>
import { useI18n } from 'vue-i18n'
import { RouterView } from 'vue-router'
import { ref } from 'vue'
import { usePublicReadingNavigation } from '@/composables/usePublicReadingNavigation'
import AppNavbar from '../organisms/AppNavbar.vue'
import AppFooter from '../organisms/AppFooter.vue'

const { t } = useI18n()
const mainRef = ref(null)
const { onPageReady } = usePublicReadingNavigation({ mainRef })
</script>

<template>
  <div class="min-h-screen flex flex-col font-sans" @public-reading-ready="onPageReady">
    <a
      href="#main-content"
      class="sr-only z-50 rounded-xl bg-white p-3 focus:not-sr-only focus:absolute"
      >{{ t('shell.skip_link') }}</a
    >
    <AppNavbar />
    <main ref="mainRef" id="main-content" tabindex="-1" class="flex-grow focus:outline-none">
      <RouterView v-slot="{ Component }">
        <Transition name="fade" mode="out-in" @after-enter="onPageReady">
          <component :is="Component" />
        </Transition>
      </RouterView>
    </main>
    <AppFooter />
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}
.fade-enter-from {
  opacity: 0;
  transform: translateY(6px);
}
.fade-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
