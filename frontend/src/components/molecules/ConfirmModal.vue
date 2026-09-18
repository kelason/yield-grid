<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div v-if="isOpen" class="fixed inset-0 z-[110] flex items-center justify-center p-4 sm:p-6">
        <!-- Backdrop -->
        <div
          class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
          @click="handleCancel"
        ></div>

        <!-- Modal Panel -->
        <div
          class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all border border-white/20"
        >
          <div
            :class="`h-1.5 w-full ${type === 'danger' ? 'bg-gradient-to-r from-red-500 to-red-600' : 'bg-gradient-to-r from-farm-500 to-farm-600'}`"
          ></div>

          <div class="p-6 text-center">
            <h3 class="text-xl font-bold text-gray-900 mb-2">{{ title }}</h3>
            <p class="text-sm text-gray-500 mb-6">{{ message }}</p>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
              <button
                type="button"
                @click="handleCancel"
                class="w-full sm:w-auto rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 transition-all transform hover:-translate-y-0.5 active:scale-95"
              >
                {{ cancelText }}
              </button>
              <button
                type="button"
                @click="handleConfirm"
                :class="[
                  'w-full sm:w-auto rounded-md px-4 py-2 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 transition-all transform hover:-translate-y-0.5 active:scale-95',
                  type === 'danger'
                    ? 'bg-red-600 hover:bg-red-500 focus-visible:outline-red-600'
                    : 'bg-gradient-to-r from-farm-500 to-farm-600 hover:from-farm-600 hover:to-farm-700 focus-visible:outline-farm-600',
                ]"
              >
                {{ confirmText }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
defineProps({
  isOpen: {
    type: Boolean,
    required: true,
  },
  title: {
    type: String,
    default: 'Confirm Action',
  },
  message: {
    type: String,
    required: true,
  },
  confirmText: {
    type: String,
    default: 'Confirm',
  },
  cancelText: {
    type: String,
    default: 'Cancel',
  },
  type: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'danger'].includes(value),
  },
})

const emit = defineEmits(['confirm', 'cancel'])

const handleConfirm = () => {
  emit('confirm')
}

const handleCancel = () => {
  emit('cancel')
}
</script>
