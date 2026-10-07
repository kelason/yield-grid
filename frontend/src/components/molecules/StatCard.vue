<script setup>
import AppCard from '../atoms/AppCard.vue'
import AppSkeleton from '../atoms/AppSkeleton.vue'
defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], default: null },
  loading: Boolean,
  tone: { type: String, default: 'default' },
})
</script>
<template>
  <AppCard class="h-full">
    <div class="flex items-start justify-between gap-4">
      <dl class="min-w-0">
        <dt class="text-sm font-medium text-stone-600">{{ label }}</dt>
        <dd
          class="mt-3 break-words text-3xl font-semibold tracking-tight tabular-nums"
          :class="tone === 'harvest' ? 'text-harvest-700' : 'text-moss-700'"
        >
          <span v-if="loading" role="status"
            ><span class="sr-only">Loading {{ label }}</span
            ><AppSkeleton class="mt-1 h-8 w-24"
          /></span>
          <span v-else>{{ value ?? '—' }}</span>
        </dd>
      </dl>
      <div v-if="$slots.icon" class="rounded-xl bg-stone-100 p-2 text-moss-600" aria-hidden="true">
        <slot name="icon" />
      </div>
    </div>
  </AppCard>
</template>
