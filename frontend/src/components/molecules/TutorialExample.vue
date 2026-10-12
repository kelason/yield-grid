<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
defineProps({ example: { type: Object, required: true } })
const failed = ref(false)
const { t } = useI18n()
</script>
<template>
  <figure class="mt-6 max-w-sm rounded-2xl border border-stone-200 bg-stone-50 p-4 shadow-soft">
    <p class="mb-3 text-xs font-medium text-soil-700">{{ t('public.how_it_works.example') }}</p>
    <img
      v-if="!failed"
      :src="example.src"
      :alt="t(example.altKey)"
      :width="example.width"
      :height="example.height"
      loading="lazy"
      class="h-auto w-full rounded-xl"
      @error="failed = true"
    />
    <p v-else class="rounded-xl bg-dew-50 p-4 text-sm leading-relaxed text-dew-800">
      {{ t('public.how_it_works.image_unavailable') }}
    </p>
    <figcaption class="mt-3 text-sm leading-relaxed text-stone-600">
      {{ t(example.captionKey) }}
    </figcaption>
  </figure>
</template>
