<script setup>
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

defineProps({
  currentPage: {
    type: Number,
    required: true,
  },
  lastPage: {
    type: Number,
    required: true,
  },
  total: {
    type: Number,
    required: true,
  },
})

defineEmits(['page-change'])
</script>

<template>
  <div
    v-if="lastPage > 1"
    class="flex items-center justify-between bg-white px-4 py-3 sm:px-6 rounded-2xl shadow-soft border border-stone-200"
  >
    <div class="flex flex-1 justify-between sm:hidden">
      <button
        @click="$emit('page-change', currentPage - 1)"
        :disabled="currentPage === 1"
        class="relative inline-flex items-center rounded-xl border border-stone-300 bg-white px-4 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      >
        Previous
      </button>
      <button
        @click="$emit('page-change', currentPage + 1)"
        :disabled="currentPage === lastPage"
        class="relative ml-3 inline-flex items-center rounded-xl border border-stone-300 bg-white px-4 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      >
        Next
      </button>
    </div>
    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
      <div>
        <p class="text-sm text-stone-600">
          Showing page <span class="font-semibold text-stone-900">{{ currentPage }}</span> of
          <span class="font-semibold text-stone-900">{{ lastPage }}</span>
          (<span class="font-semibold text-stone-900">{{ total }}</span> results)
        </p>
      </div>
      <div>
        <nav class="isolate inline-flex gap-1" aria-label="Pagination">
          <button
            @click="$emit('page-change', currentPage - 1)"
            :disabled="currentPage === 1"
            class="relative inline-flex items-center rounded-full p-2 text-stone-500 hover:bg-moss-50 hover:text-moss-600 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-150 border border-stone-200"
          >
            <span class="sr-only">Previous</span>
            <ChevronLeftIcon class="h-5 w-5" aria-hidden="true" />
          </button>

          <button
            @click="$emit('page-change', currentPage + 1)"
            :disabled="currentPage === lastPage"
            class="relative inline-flex items-center rounded-full p-2 text-stone-500 hover:bg-moss-50 hover:text-moss-600 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-150 border border-stone-200"
          >
            <span class="sr-only">Next</span>
            <ChevronRightIcon class="h-5 w-5" aria-hidden="true" />
          </button>
        </nav>
      </div>
    </div>
  </div>
</template>
