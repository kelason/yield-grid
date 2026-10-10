<template>
  <div
    class="plot-info-panel bg-white p-6 rounded-2xl shadow-soft border border-stone-200 w-full max-w-md"
  >
    <h2 class="font-serif text-xl font-bold mb-4 text-stone-800">
      {{ t('farmer.plot_info.title') }}
    </h2>
    <div v-if="plot" class="space-y-4">
      <div>
        <span class="text-sm text-stone-500 block">{{ t('farmer.plot_info.name') }}</span>
        <strong class="text-lg">{{ plot.name }}</strong>
      </div>
      <div>
        <span class="text-sm text-stone-500 block">{{ t('farmer.plot_info.area') }}</span>
        <strong>{{ t('farmer.plot_info.hectares', { area: plot.calculated_area }) }}</strong>
      </div>

      <div class="mt-6">
        <button
          @click="$emit('analyze', plot)"
          class="w-full py-3 bg-moss-600 hover:bg-moss-700 text-white font-bold rounded-xl transition-all duration-300 hover:scale-[1.02] shadow-soft flex items-center justify-center gap-2"
        >
          {{ t('farmer.plot_info.analyze') }}
        </button>
      </div>
    </div>
    <div v-else class="text-stone-500 italic">{{ t('farmer.plot_info.empty') }}</div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()
defineProps({
  plot: {
    type: Object,
    default: null,
  },
})

defineEmits(['analyze'])
</script>
