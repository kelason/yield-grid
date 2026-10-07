<script setup>
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  headingLevel: { type: Number, default: 1, validator: (value) => [1, 2].includes(value) },
})
</script>
<template>
  <header class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0 max-w-2xl">
      <component
        :is="`h${headingLevel}`"
        class="font-serif break-words [overflow-wrap:anywhere] text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl"
        >{{ title }}</component
      >
      <p v-if="description" class="mt-3 text-base leading-relaxed text-stone-600">
        {{ description }}
      </p>
    </div>
    <div v-if="$slots.actions" class="flex shrink-0 flex-wrap items-center gap-3">
      <slot name="actions" />
    </div>
  </header>
</template>
