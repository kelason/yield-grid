<script setup>
import { RouterView } from 'vue-router'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useHead } from '@unhead/vue'
import ToastContainer from '@/components/organisms/ToastContainer.vue'

const { locale } = useI18n()
useHead(computed(() => ({ htmlAttrs: { lang: locale.value } })))

const PUBLIC_READING_READY_EVENT = 'public-reading-ready'
function notifyLayoutReady(element) {
  element.dispatchEvent(new Event(PUBLIC_READING_READY_EVENT))
}
</script>

<template>
  <RouterView v-slot="{ Component }">
    <transition name="page" mode="out-in" @after-enter="notifyLayoutReady">
      <component :is="Component" />
    </transition>
  </RouterView>
  <ToastContainer />
</template>

<style>
.page-enter-active,
.page-leave-active {
  transition:
    opacity 0.3s ease,
    transform 0.3s ease;
}

.page-enter-from,
.page-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>
