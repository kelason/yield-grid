<template>
  <div class="bg-white overflow-hidden shadow-soft rounded-2xl border border-stone-200 p-6">
    <div class="animate-pulse flex space-x-4">
      <!-- Icon/Avatar skeleton -->
      <div v-if="withAvatar" class="rounded-full bg-stone-200 h-10 w-10 flex-shrink-0"></div>

      <div class="flex-1 space-y-4 py-1">
        <!-- Title skeleton -->
        <div class="h-4 bg-stone-200 rounded-lg w-3/4"></div>

        <!-- Content skeleton lines -->
        <div class="space-y-2">
          <div class="h-4 bg-stone-200 rounded-lg"></div>
          <div class="h-4 bg-stone-200 rounded-lg w-5/6"></div>
        </div>

        <!-- Optional button/action skeleton -->
        <div v-if="withAction" class="pt-4">
          <div class="h-8 bg-stone-200 rounded-xl w-24"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  withAvatar: {
    type: Boolean,
    default: false,
  },
  withAction: {
    type: Boolean,
    default: false,
  },
})
</script>
