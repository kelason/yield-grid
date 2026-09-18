<script setup>
import { RouterView } from 'vue-router'
import AppNavbar from '../organisms/AppNavbar.vue'
import AppFooter from '../organisms/AppFooter.vue'
</script>

<template>
  <div class="min-h-screen flex flex-col font-sans">
    <AppNavbar />
    <main class="flex-grow">
      <RouterView v-slot="{ Component }">
        <Transition name="fade" mode="out-in">
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
