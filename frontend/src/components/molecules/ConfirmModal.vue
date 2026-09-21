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
      <div v-if="isOpen" class="fixed inset-0 z-[110] flex items-center justify-center p-4 sm:p-6">
        <!-- Backdrop -->
        <div
          class="fixed inset-0 bg-soil-900/50 backdrop-blur-sm transition-opacity"
          @click="handleCancel"
          aria-hidden="true"
        ></div>

        <!-- Modal Panel -->
        <div
          class="relative w-full max-w-md bg-white rounded-3xl shadow-organic overflow-hidden transform transition-all border border-stone-100"
        >
          <!-- Organic top accent strip -->
          <div
            :class="[
              'h-1.5 w-full',
              type === 'danger'
                ? 'bg-gradient-to-r from-red-500 to-red-600'
                : 'bg-gradient-to-r from-moss-500 to-moss-600',
            ]"
          ></div>

          <div class="p-7 text-center">
            <!-- Icon -->
            <div class="flex items-center justify-center mb-4">
              <div
                :class="[
                  'w-14 h-14 rounded-full flex items-center justify-center text-2xl',
                  type === 'danger' ? 'bg-red-100' : 'bg-moss-100',
                ]"
              >
                <span>{{ type === 'danger' ? '⚠️' : '🌱' }}</span>
              </div>
            </div>

            <h3 class="text-xl font-bold text-stone-900 mb-2 font-serif">{{ title }}</h3>
            <p class="text-sm text-stone-500 mb-7 leading-relaxed">{{ message }}</p>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
              <!-- Cancel -->
              <button
                type="button"
                @click="handleCancel"
                class="w-full sm:w-auto rounded-xl bg-stone-100 px-5 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 transition-all duration-200 hover:scale-[1.02] active:scale-[0.99]"
              >
                {{ cancelText }}
              </button>
              <!-- Confirm -->
              <button
                type="button"
                @click="handleConfirm"
                :class="[
                  'w-full sm:w-auto rounded-xl px-5 py-2.5 text-sm font-semibold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 transition-all duration-200 hover:scale-[1.02] active:scale-[0.99]',
                  type === 'danger'
                    ? 'bg-gradient-to-br from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 focus-visible:outline-red-600'
                    : 'bg-gradient-to-br from-moss-500 to-moss-600 hover:from-moss-600 hover:to-moss-700 shadow-soft hover:shadow-organic focus-visible:outline-moss-600',
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
