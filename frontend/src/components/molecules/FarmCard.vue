<script setup>
import AppCard from '../atoms/AppCard.vue'
import AppButton from '../atoms/AppButton.vue'

defineProps({
  farm: {
    type: Object,
    required: true,
  },
})

defineEmits(['view-plots', 'view-recommendations'])
</script>

<template>
  <div
    class="group transform hover:-translate-y-1 transition-all duration-300 motion-reduce:transition-none motion-reduce:transform-none motion-reduce:hover:translate-y-0"
  >
    <AppCard class="h-full flex flex-col" padding="p-5" :hover="false">
      <!-- Card Header -->
      <div class="flex items-start justify-between gap-3 mb-4">
        <div class="flex items-start gap-3 min-w-0 flex-1">
          <div
            class="w-11 h-11 rounded-2xl bg-gradient-to-br from-stone-100 to-stone-200 border border-stone-200 flex items-center justify-center text-xl shadow-sm flex-shrink-0 mt-0.5"
          >
            🌾
          </div>
          <div class="min-w-0 flex-1">
            <h3 class="text-base font-bold text-stone-900 leading-snug line-clamp-2 font-serif">
              {{ farm.name }}
            </h3>
            <p
              v-if="farm.city || farm.state || farm.country"
              class="text-xs text-stone-500 mt-0.5 truncate"
            >
              📍 {{ [farm.city, farm.state, farm.country].filter(Boolean).join(', ') }}
            </p>
          </div>
        </div>
        <span
          class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-moss-100 text-moss-800 border border-moss-200 flex-shrink-0 whitespace-nowrap mt-0.5"
        >
          {{ farm.plots_count || 0 }} {{ (farm.plots_count || 0) === 1 ? 'Plot' : 'Plots' }}
        </span>
      </div>

      <!-- Farm Stats -->
      <div class="flex-1 space-y-2 mb-5">
        <div
          v-if="farm.total_area"
          class="flex items-center justify-between py-2.5 px-3.5 bg-stone-50 rounded-xl border border-stone-200"
        >
          <span class="text-xs text-stone-500 font-medium flex items-center gap-1.5">
            <span>📐</span> Total Area
          </span>
          <span class="text-sm font-bold text-soil-700">{{ farm.total_area }} ha</span>
        </div>
        <div
          v-else
          class="py-2.5 px-3.5 bg-stone-50 rounded-xl border border-stone-200 text-xs text-stone-400 text-center"
        >
          No plots drawn yet
        </div>
      </div>

      <!-- Actions -->
      <div v-if="farm.plots_count > 0" class="flex flex-col gap-2 pt-1">
        <AppButton
          variant="secondary"
          size="sm"
          rounded="xl"
          class="w-full justify-center"
          @click="$emit('view-recommendations', farm.id)"
        >
          <span class="text-base leading-none">🤖</span>
          <span>View Recommendations</span>
        </AppButton>
        <AppButton
          variant="outline"
          size="sm"
          rounded="xl"
          class="w-full justify-center"
          @click="$emit('view-plots', farm.id)"
        >
          <svg
            class="h-4 w-4 flex-shrink-0"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"
            />
          </svg>
          <span>Manage Plots</span>
        </AppButton>
      </div>
      <AppButton
        v-else
        variant="outline"
        size="sm"
        rounded="xl"
        class="w-full justify-center"
        @click="$emit('view-plots', farm.id)"
      >
        <svg
          class="h-4 w-4 flex-shrink-0"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 4v16m8-8H4"
          />
        </svg>
        <span>Draw Plots</span>
      </AppButton>
    </AppCard>
  </div>
</template>
