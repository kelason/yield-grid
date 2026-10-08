<script setup>
import { ref } from 'vue'
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  level: { type: String, default: 'unknown' },
  available: { type: Boolean, default: true },
})
const emit = defineEmits(['toggle'])

const STEPS = [
  { value: 'safe', label: 'Safe', swatch: 'bg-moss-600' },
  { value: 'low', label: 'Low', swatch: 'bg-harvest-400' },
  { value: 'medium', label: 'Medium', swatch: 'bg-harvest-600' },
  { value: 'high', label: 'High', swatch: 'bg-red-600' },
]

const pressed = ref(true)

function onToggle() {
  pressed.value = !pressed.value
  emit('toggle', pressed.value)
}
</script>

<template>
  <div class="bg-white/95 rounded-2xl shadow-soft border border-stone-200 p-3 w-40">
    <div class="flex items-center justify-between gap-2 mb-2">
      <h3 class="font-serif text-sm font-bold text-stone-900">Flood risk</h3>
      <button
        type="button"
        :aria-pressed="pressed"
        aria-label="Toggle flood overlay"
        class="min-h-[44px] min-w-[44px] -m-2 flex items-center justify-center rounded-full text-stone-500 transition-colors hover:bg-stone-100 hover:text-stone-900 focus-visible:ring-2 focus-visible:ring-moss-500 motion-reduce:transition-none"
        @click="onToggle"
      >
        <component :is="pressed ? EyeIcon : EyeSlashIcon" class="h-5 w-5" aria-hidden="true" />
      </button>
    </div>
    <ul class="space-y-1">
      <li
        v-for="step in STEPS"
        :key="step.value"
        data-testid="legend-step"
        class="flex items-center gap-2 rounded-lg px-1 py-0.5 text-xs font-medium text-stone-600"
        :class="step.value === props.level ? 'ring-2 ring-moss-500' : ''"
      >
        <span class="h-3 w-3 rounded-full" :class="step.swatch" aria-hidden="true"></span>
        {{ step.label }}
      </li>
    </ul>
    <p v-if="!available" class="mt-2 text-xs text-stone-500">Hazard data unavailable</p>
  </div>
</template>
