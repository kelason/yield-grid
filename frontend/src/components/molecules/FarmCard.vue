<script setup>
import { computed } from 'vue'
import { MapPinIcon, MapIcon, SparklesIcon } from '@heroicons/vue/24/outline'
import AppCard from '../atoms/AppCard.vue'
import AppButton from '../atoms/AppButton.vue'
const props = defineProps({ farm: { type: Object, required: true } })
defineEmits(['view-plots', 'view-recommendations'])
const location = computed(() =>
  [props.farm?.city, props.farm?.state, props.farm?.country].filter(Boolean).join(', '),
)
</script>
<template>
  <AppCard class="h-full" padding="p-5">
    <template v-if="farm">
      <div class="flex items-start justify-between gap-4">
        <h3 class="min-w-0 break-words font-serif text-xl font-bold text-stone-900">
          {{ farm.name }}
        </h3>
        <span class="shrink-0 rounded-full bg-moss-50 px-3 py-1 text-xs font-medium text-moss-800"
          >{{ farm.plots_count || 0 }} {{ farm.plots_count === 1 ? 'plot' : 'plots' }}</span
        >
      </div>
      <p v-if="location" class="mt-3 flex items-start gap-2 text-sm leading-relaxed text-stone-600">
        <MapPinIcon class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />{{ location }}
      </p>
      <p class="mt-4 border-t border-stone-200 pt-4 text-sm text-stone-600">
        <template v-if="farm.total_area">{{ farm.total_area }} ha mapped</template
        ><template v-else>No mapped area yet</template>
      </p>
      <div class="mt-5 flex flex-wrap gap-3">
        <AppButton
          variant="outline"
          size="sm"
          :aria-label="`${farm.plots_count ? 'Manage plots' : 'Draw plots'} for ${farm.name}`"
          @click="$emit('view-plots', farm.id)"
          ><MapIcon class="h-4 w-4" aria-hidden="true" />{{
            farm.plots_count ? 'Manage plots' : 'Draw plots'
          }}</AppButton
        ><AppButton
          v-if="farm.plots_count"
          variant="ghost"
          size="sm"
          :aria-label="`Recommendations for ${farm.name}`"
          @click="$emit('view-recommendations', farm.id)"
          ><SparklesIcon class="h-4 w-4" aria-hidden="true" />Recommendations</AppButton
        >
      </div>
    </template>
    <p v-else class="text-sm text-stone-600">Farm information is unavailable.</p>
  </AppCard>
</template>
