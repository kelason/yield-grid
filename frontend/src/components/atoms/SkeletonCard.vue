<template>
  <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-100 p-6">
    <div class="animate-pulse flex space-x-4">
      <!-- Icon/Avatar skeleton -->
      <div v-if="withAvatar" class="rounded-full bg-gray-200 h-10 w-10"></div>

      <div class="flex-1 space-y-4 py-1">
        <!-- Title skeleton -->
        <div class="h-4 bg-gray-200 rounded w-3/4"></div>

        <!-- Content skeleton lines -->
        <div class="space-y-2">
          <div class="h-4 bg-gray-200 rounded"></div>
          <div class="h-4 bg-gray-200 rounded w-5/6"></div>
        </div>

        <!-- Optional button/action skeleton -->
        <div v-if="withAction" class="pt-4">
          <div class="h-8 bg-gray-200 rounded w-24"></div>
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
