<script setup>
import AppCard from '../atoms/AppCard.vue'
import AppButton from '../atoms/AppButton.vue'
import EmptyState from '../molecules/EmptyState.vue'
import AppModal from '../molecules/AppModal.vue'
import AddressFields from './AddressFields.vue'
import { MapPinIcon } from '@heroicons/vue/24/outline'
defineProps({
  addresses: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) },
  showAddressModal: Boolean,
  editingAddress: { type: Object, default: null },
  addressDraft: { type: Object, default: null },
  savingAddress: Boolean,
})
defineEmits([
  'add',
  'edit',
  'save',
  'delete',
  'default',
  'close',
  'update:addressDraft',
  'pin-validation',
])
</script>
<template>
  <section aria-label="My addresses || []">
    <div>
      <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <h3 class="font-serif text-2xl font-bold text-stone-900">My Addresses</h3>
        <AppButton size="sm" variant="primary" @click="$emit('add')"> Add address </AppButton>
      </div>
      <div v-if="(addresses?.length || 0) > 0" class="space-y-3">
        <AppCard v-for="address in addresses || []" :key="address.id" padding="p-4">
          <div class="flex flex-col sm:flex-row sm:justify-between gap-3">
            <div class="flex items-start gap-3 min-w-0">
              <MapPinIcon class="h-5 w-5 text-moss-600 flex-shrink-0 mt-0.5" aria-hidden="true" />
              <div class="min-w-0">
                <p class="text-sm font-semibold text-stone-900">
                  {{ address.label || 'Address' }}
                  <span
                    v-if="address.is_default"
                    class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-moss-100 text-moss-800"
                  >
                    Default
                  </span>
                </p>
                <p class="text-sm text-stone-600 mt-0.5 leading-relaxed">
                  {{ address.formatted_address }}
                </p>
              </div>
            </div>
            <div class="flex flex-shrink-0 gap-2">
              <AppButton
                variant="ghost"
                size="sm"
                v-if="!address.is_default"
                type="button"
                @click="$emit('default', address)"
                class="min-h-11 px-2 text-sm font-medium text-moss-700 hover:text-moss-800 underline transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500 rounded-xl"
              >
                Set default
              </AppButton>
              <AppButton
                variant="ghost"
                size="sm"
                type="button"
                @click="$emit('edit', address)"
                class="min-h-11 px-2 text-sm font-medium text-stone-600 hover:text-stone-900 underline transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500 rounded-xl"
              >
                Edit
              </AppButton>
              <AppButton
                variant="ghost"
                size="sm"
                type="button"
                @click="$emit('delete', address.id)"
                class="min-h-11 px-2 text-sm font-medium text-red-600 hover:text-red-700 underline transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 rounded-xl"
              >
                Delete
              </AppButton>
            </div>
          </div>
        </AppCard>
      </div>
      <EmptyState
        v-else
        title="No addresses yet"
        description="Add an address to trade in the marketplace and appear in nearest-first sorting."
      />
    </div>

    <AppModal
      :title="editingAddress ? 'Edit address' : 'Add address'"
      :busy="savingAddress"
      :is-open="showAddressModal"
      @close="$emit('close')"
    >
      <div class="space-y-5">
        <p v-if="errors?.form" role="alert" class="text-sm text-red-600">{{ errors.form }}</p>
        <AddressFields
          :model-value="addressDraft"
          @update:model-value="$emit('update:addressDraft', $event)"
          id-prefix="profile-addr"
          :errors="errors"
          @pin-validation="$emit('pin-validation', $event)"
        />
        <label
          v-if="(addresses?.length || 0) > 0"
          class="flex items-center gap-2 cursor-pointer select-none"
        >
          <input
            id="profile-addr-default"
            type="checkbox"
            :checked="addressDraft?.is_default"
            @change="
              $emit('update:addressDraft', { ...addressDraft, is_default: $event.target.checked })
            "
            class="h-4 w-4 rounded-xl text-moss-600 border-stone-300 focus:ring-moss-500"
          />
          <span class="text-sm font-medium text-stone-900">Set as default address</span>
        </label>
        <div class="flex justify-end gap-3">
          <AppButton variant="ghost" @click="$emit('close')" :disabled="savingAddress"
            >Cancel</AppButton
          >
          <AppButton variant="primary" :loading="savingAddress" @click="$emit('save')">
            {{ editingAddress ? 'Save changes' : 'Add address' }}
          </AppButton>
        </div>
      </div>
    </AppModal>
  </section>
</template>
