<script setup>
import { RouterView } from 'vue-router'
import AppNavbar from '../organisms/AppNavbar.vue'
import AppFooter from '../organisms/AppFooter.vue'
</script>

<template>
  <div class="min-h-screen flex flex-col font-sans">
    <AppNavbar />
    <main class="flex-grow">
      <RouterView />
    </main>
    <AppFooter />
  </div>
</template>
