<script setup>
import { RouterView } from 'vue-router'
import Navbar from '../organisms/Navbar.vue'
import Footer from '../organisms/Footer.vue'
</script>

<template>
  <div class="min-h-screen flex flex-col font-sans">
    <Navbar />
    <main class="flex-grow">
      <RouterView />
    </main>
    <Footer />
  </div>
</template>
