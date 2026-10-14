<script setup>
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import BrandLogo from './BrandLogo.vue'
defineProps({ dark: Boolean, collapsed: Boolean })
const { t } = useI18n()
</script>
<template>
  <RouterLink
    to="/"
    :aria-label="t('shell.logo_home')"
    class="inline-flex min-w-0 min-h-11 items-center rounded-xl transition-colors duration-200"
  >
    <BrandLogo :dark="dark" :collapsed="collapsed" aria-hidden="true" />
  </RouterLink>
</template>
