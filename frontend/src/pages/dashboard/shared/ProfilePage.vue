<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { formatDistanceToNow } from 'date-fns'
import { useApi } from '../../../composables/useApi'
import { useAuthStore } from '../../../stores/auth'
import { useAddressStore } from '../../../stores/addressStore'
import { useNotificationStore } from '../../../stores/notificationStore'
import { HTTP_STATUS } from '../../../constants/http'
import AppCard from '../../../components/atoms/AppCard.vue'
import AppButton from '../../../components/atoms/AppButton.vue'
import PriceTag from '../../../components/atoms/PriceTag.vue'
import SkeletonCard from '../../../components/atoms/SkeletonCard.vue'
import EmptyState from '../../../components/molecules/EmptyState.vue'
import AppModal from '../../../components/molecules/AppModal.vue'
import ConfirmModal from '../../../components/molecules/ConfirmModal.vue'
import AddressFields from '../../../components/molecules/AddressFields.vue'
import { MapPinIcon } from '@heroicons/vue/24/outline'

const ANONYMOUS_ROUTE_PARAM = 'anonymous'
const FARMER_ROLE = 'farmer'
const ADDRESS_LABEL_MAX_LENGTH = 50
const ADDRESS_STREET_MAX_LENGTH = 255

const route = useRoute()
const api = useApi()
const authStore = useAuthStore()
const addressStore = useAddressStore()
const notificationStore = useNotificationStore()

const profile = ref(null)
const isLoading = ref(false)
const notFound = ref(false)

const showAddressModal = ref(false)
const editingAddress = ref(null)
const addressDraft = ref(blankAddress())
const addressErrors = ref({})
const pinValid = ref(true)
const savingAddress = ref(false)
const pendingConfirm = ref(null)

const confirmConfig = computed(() => {
  if (!pendingConfirm.value) return null
  if (pendingConfirm.value.action === 'delete') {
    return {
      title: 'Delete address?',
      message: 'This address will be removed from your profile. This cannot be undone.',
      confirmText: 'Delete',
      type: 'danger',
    }
  }
  if (pendingConfirm.value.action === 'default') {
    return {
      title: 'Set default address?',
      message: `Use "${pendingConfirm.value.label || 'this address'}" as your default address?`,
      confirmText: 'Set default',
      type: 'primary',
    }
  }
  return {
    title: editingAddress.value ? 'Save address changes?' : 'Add this address?',
    message: editingAddress.value
      ? 'Your address will be updated with these details.'
      : 'This address will be added to your profile.',
    confirmText: editingAddress.value ? 'Save changes' : 'Add address',
    type: 'primary',
  }
})

const userId = computed(() => route.params.userId)
const isAnonymous = computed(() => userId.value === ANONYMOUS_ROUTE_PARAM)
const isFarmer = computed(() => profile.value?.role === FARMER_ROLE)
const isOwnProfile = computed(
  () => !isAnonymous.value && String(authStore.user?.id) === String(userId.value),
)

function blankAddress() {
  return {
    label: '',
    street: '',
    region_code: '',
    province_code: null,
    city_municipality_code: '',
    barangay_code: '',
    latitude: null,
    longitude: null,
    is_default: false,
  }
}

function openAddAddress() {
  editingAddress.value = null
  addressDraft.value = blankAddress()
  addressErrors.value = {}
  pinValid.value = true
  showAddressModal.value = true
}

function openEditAddress(address) {
  editingAddress.value = address
  addressDraft.value = {
    label: address.label || '',
    street: address.street || '',
    region_code: address.region_code,
    province_code: address.province_code,
    city_municipality_code: address.city_municipality_code,
    barangay_code: address.barangay_code,
    latitude: address.latitude,
    longitude: address.longitude,
    is_default: address.is_default,
  }
  addressErrors.value = {}
  pinValid.value = true
  showAddressModal.value = true
}

function cleanDraft() {
  const payload = { ...addressDraft.value }
  if (!payload.label) delete payload.label
  if (!payload.street) delete payload.street
  if (!payload.province_code) payload.province_code = null
  return payload
}

function validateAddressDraft() {
  const draft = addressDraft.value
  if ((draft.label || '').length > ADDRESS_LABEL_MAX_LENGTH)
    return `Label must be at most ${ADDRESS_LABEL_MAX_LENGTH} characters.`
  if ((draft.street || '').length > ADDRESS_STREET_MAX_LENGTH)
    return `Street must be at most ${ADDRESS_STREET_MAX_LENGTH} characters.`
  if (!draft.region_code || !draft.city_municipality_code || !draft.barangay_code)
    return 'Please select your region, city/municipality, and barangay.'
  return ''
}

function requestSaveAddress() {
  addressErrors.value = {}
  if (!pinValid.value) {
    notificationStore.error('Please place your pin within the selected area.')
    return
  }
  const validationMessage = validateAddressDraft()
  if (validationMessage) {
    notificationStore.error(validationMessage)
    return
  }
  pendingConfirm.value = { action: 'save' }
}

async function confirmPending() {
  const pending = pendingConfirm.value
  pendingConfirm.value = null
  if (!pending) return
  if (pending.action === 'delete') {
    await deleteAddress(pending.id)
  } else if (pending.action === 'default') {
    await setDefaultAddress(pending.address)
  } else {
    await saveAddress()
  }
}

async function saveAddress() {
  addressErrors.value = {}
  savingAddress.value = true
  try {
    if (editingAddress.value) {
      await addressStore.updateAddress(editingAddress.value.id, cleanDraft())
      notificationStore.success('Address updated.')
    } else {
      await addressStore.createAddress(cleanDraft())
      notificationStore.success('Address added.')
    }
    showAddressModal.value = false
    fetchProfile()
  } catch (err) {
    const apiErrors = err.response?.data?.errors || {}
    Object.keys(apiErrors).forEach((key) => {
      addressErrors.value[key] = apiErrors[key][0]
    })
    if (Object.keys(addressErrors.value).length === 0) {
      notificationStore.error(err.response?.data?.message || 'Failed to save address.')
    }
  } finally {
    savingAddress.value = false
  }
}

async function deleteAddress(id) {
  try {
    await addressStore.deleteAddress(id)
    notificationStore.success('Address deleted.')
    fetchProfile()
  } catch (err) {
    notificationStore.error(err.response?.data?.message || 'Failed to delete address.')
  }
}

async function setDefaultAddress(address) {
  try {
    await addressStore.updateAddress(address.id, {
      label: address.label,
      street: address.street,
      region_code: address.region_code,
      province_code: address.province_code,
      city_municipality_code: address.city_municipality_code,
      barangay_code: address.barangay_code,
      latitude: address.latitude,
      longitude: address.longitude,
      is_default: true,
    })
    notificationStore.success('Default address updated.')
    fetchProfile()
  } catch {
    notificationStore.error('Failed to update default address.')
  }
}

function formatPostDate(value) {
  return formatDistanceToNow(new Date(value), { addSuffix: true })
}

async function fetchProfile() {
  if (isAnonymous.value) return

  isLoading.value = true
  notFound.value = false
  profile.value = null

  try {
    const response = await api.get(`/users/${userId.value}`)
    profile.value = response.data.data
    if (isOwnProfile.value && Array.isArray(profile.value?.addresses)) {
      addressStore.addresses = profile.value.addresses
    }
  } catch (error) {
    if (error.response?.status === HTTP_STATUS.NOT_FOUND) {
      notFound.value = true
    }
  } finally {
    isLoading.value = false
  }
}

watch(userId, fetchProfile, { immediate: true })
</script>

<template>
  <div class="space-y-6" aria-live="polite">
    <div v-if="isLoading" class="space-y-6">
      <SkeletonCard withAvatar />
      <SkeletonCard />
    </div>

    <EmptyState
      v-else-if="isAnonymous"
      title="This profile is anonymous"
      description="The author chose to stay anonymous, so there is nothing to show here."
    />

    <EmptyState
      v-else-if="notFound || !profile"
      title="Profile not found"
      description="This user does not exist or is no longer available."
    />

    <template v-else>
      <AppCard padding="p-6">
        <div class="flex items-center gap-5">
          <img
            v-if="profile.avatar_url"
            :src="profile.avatar_url"
            :alt="`${profile.name} avatar`"
            class="w-16 h-16 rounded-full object-cover bg-stone-200 flex-shrink-0"
          />
          <div
            v-else
            class="w-16 h-16 rounded-full bg-gradient-to-br from-moss-400 to-soil-600 flex items-center justify-center text-white text-2xl font-bold shadow-soft flex-shrink-0"
            aria-hidden="true"
          >
            {{ profile.name.charAt(0).toUpperCase() }}
          </div>
          <div class="min-w-0">
            <h2 class="font-serif text-2xl font-bold text-stone-900 truncate">
              {{ profile.name }}
            </h2>
            <span
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize mt-1"
              :class="isFarmer ? 'bg-moss-100 text-moss-800' : 'bg-soil-100 text-soil-800'"
            >
              {{ profile.role }}
            </span>
          </div>
        </div>
      </AppCard>

      <div class="grid grid-cols-1 gap-5" :class="isFarmer ? 'sm:grid-cols-3' : 'sm:grid-cols-2'">
        <template v-if="isFarmer">
          <AppCard padding="p-6" class="text-center">
            <p class="text-3xl font-semibold text-moss-700">{{ profile.stats.total_sold }}</p>
            <p class="text-sm font-medium text-soil-700 mt-1">Items sold</p>
          </AppCard>
          <AppCard padding="p-6" class="text-center">
            <PriceTag :amount="profile.stats.total_revenue" size="lg" />
            <p class="text-sm font-medium text-soil-700 mt-1">Revenue earned</p>
          </AppCard>
          <AppCard padding="p-6" class="text-center">
            <p class="text-3xl font-semibold text-moss-700">{{ profile.stats.total_listed }}</p>
            <p class="text-sm font-medium text-soil-700 mt-1">Items listed</p>
          </AppCard>
        </template>
        <template v-else>
          <AppCard padding="p-6" class="text-center">
            <p class="text-3xl font-semibold text-moss-700">
              {{ profile.stats.total_purchases }}
            </p>
            <p class="text-sm font-medium text-soil-700 mt-1">Purchases</p>
          </AppCard>
          <AppCard padding="p-6" class="text-center">
            <PriceTag :amount="profile.stats.total_spent" size="lg" />
            <p class="text-sm font-medium text-soil-700 mt-1">Total spent</p>
          </AppCard>
        </template>
      </div>

      <div v-if="isOwnProfile">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-serif text-2xl font-bold text-stone-900">My Addresses</h3>
          <AppButton size="sm" variant="primary" @click="openAddAddress"> Add address </AppButton>
        </div>
        <div v-if="addressStore.addresses.length > 0" class="space-y-3">
          <AppCard v-for="address in addressStore.addresses" :key="address.id" padding="p-4">
            <div class="flex items-start justify-between gap-3">
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
                <button
                  v-if="!address.is_default"
                  type="button"
                  @click="pendingConfirm = { action: 'default', address, label: address.label }"
                  class="text-xs font-medium text-moss-700 hover:text-moss-800 underline transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500 rounded-xl"
                >
                  Set default
                </button>
                <button
                  type="button"
                  @click="openEditAddress(address)"
                  class="text-xs font-medium text-stone-600 hover:text-stone-900 underline transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500 rounded-xl"
                >
                  Edit
                </button>
                <button
                  type="button"
                  @click="pendingConfirm = { action: 'delete', id: address.id }"
                  class="text-xs font-medium text-red-600 hover:text-red-700 underline transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 rounded-xl"
                >
                  Delete
                </button>
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

      <div v-else-if="profile.location_summary">
        <AppCard padding="p-4">
          <p class="text-sm text-stone-600 flex items-center gap-2">
            <MapPinIcon class="h-5 w-5 text-moss-600" aria-hidden="true" />
            {{ profile.location_summary }}
          </p>
        </AppCard>
      </div>

      <div>
        <h3 class="font-serif text-2xl font-bold text-stone-900 mb-4">Posts</h3>
        <div v-if="profile.posts.length > 0" class="space-y-4">
          <AppCard v-for="post in profile.posts" :key="post.id" padding="p-5" :hover="true">
            <RouterLink
              :to="{ name: 'forum-thread', params: { id: post.id } }"
              class="font-serif text-xl font-bold text-stone-900 hover:text-moss-700 transition-colors duration-200 motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500 rounded-xl"
            >
              {{ post.title }}
            </RouterLink>
            <p class="text-stone-600 text-sm mt-2 mb-3 line-clamp-2 leading-relaxed">
              {{ post.body }}
            </p>
            <p class="text-xs text-stone-500">
              {{ post.vote_score }} votes · {{ post.reply_count }} replies ·
              {{ formatPostDate(post.created_at) }}
            </p>
          </AppCard>
        </div>
        <EmptyState
          v-else
          title="No posts yet"
          description="This user has not published any community posts."
        />
      </div>
    </template>

    <AppModal :is-open="showAddressModal" @close="showAddressModal = false">
      <div class="space-y-5">
        <h2 class="font-serif text-2xl font-bold text-stone-900">
          {{ editingAddress ? 'Edit address' : 'Add address' }}
        </h2>
        <AddressFields
          v-model="addressDraft"
          id-prefix="profile-addr"
          :errors="addressErrors"
          @pin-validation="pinValid = $event.valid"
        />
        <label
          v-if="addressStore.addresses.length > 0"
          class="flex items-center gap-2 cursor-pointer select-none"
        >
          <input
            id="profile-addr-default"
            type="checkbox"
            v-model="addressDraft.is_default"
            class="h-4 w-4 rounded-xl text-moss-600 border-stone-300 focus:ring-moss-500"
          />
          <span class="text-sm font-medium text-stone-900">Set as default address</span>
        </label>
        <div class="flex justify-end gap-3">
          <AppButton variant="ghost" @click="showAddressModal = false">Cancel</AppButton>
          <AppButton variant="primary" :loading="savingAddress" @click="requestSaveAddress">
            {{ editingAddress ? 'Save changes' : 'Add address' }}
          </AppButton>
        </div>
      </div>
    </AppModal>

    <ConfirmModal
      v-if="confirmConfig"
      :is-open="pendingConfirm !== null"
      :title="confirmConfig.title"
      :message="confirmConfig.message"
      :confirm-text="confirmConfig.confirmText"
      :type="confirmConfig.type"
      @confirm="confirmPending"
      @cancel="pendingConfirm = null"
    />
  </div>
</template>
