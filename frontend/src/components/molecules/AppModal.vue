<script setup>
defineProps({
  isOpen: {
    type: Boolean,
    required: true,
  },
})

defineEmits(['close'])
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="opacity-0 scale-95 translate-y-2"
      enter-to-class="opacity-100 scale-100 translate-y-0"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="opacity-100 scale-100 translate-y-0"
      leave-to-class="opacity-0 scale-95 translate-y-2"
    >
      <div v-if="isOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6">
        <!-- Backdrop -->
        <div
          class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity motion-reduce:transition-none"
          @click="$emit('close')"
          aria-hidden="true"
        ></div>

        <!-- Modal Panel -->
        <div
          class="relative w-full max-w-2xl bg-white rounded-3xl shadow-organic overflow-hidden transform transition-all motion-reduce:transition-none motion-reduce:transform-none border border-stone-100 max-h-[90vh] flex flex-col"
        >
          <!-- Organic top accent strip -->
          <div class="h-1.5 w-full bg-gradient-to-r from-moss-500 to-moss-600 shrink-0"></div>

          <button
            @click="$emit('close')"
            class="absolute top-5 right-5 text-stone-400 hover:text-stone-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500 bg-stone-100 hover:bg-stone-200 rounded-full p-1.5 z-10 transition-colors motion-reduce:transition-none"
          >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>

          <div class="p-6 sm:p-8 overflow-y-auto">
            <slot></slot>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
