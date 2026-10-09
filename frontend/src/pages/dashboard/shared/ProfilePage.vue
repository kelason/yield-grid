<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { formatDistanceToNow } from 'date-fns'
import { useApi } from '../../../composables/useApi'
import { useAuthStore } from '../../../stores/auth'
import { useAddressStore } from '../../../stores/addressStore'
import { useNotificationStore } from '../../../stores/notificationStore'
import { useFarmingStore } from '../../../stores/farming'
import { isFarmVerified } from '@/utils/verification'
import { HTTP_STATUS } from '../../../constants/http'
import AppCard from '../../../components/atoms/AppCard.vue'
import ProfileAddressPanel from '@/components/organisms/ProfileAddressPanel.vue'
import { useProfileAddresses } from '@/composables/useProfileAddresses'
import PageHeader from '@/components/molecules/PageHeader.vue'
import LoadingState from '@/components/molecules/LoadingState.vue'
import AppButton from '../../../components/atoms/AppButton.vue'
import PriceTag from '../../../components/atoms/PriceTag.vue'
import SkeletonCard from '../../../components/atoms/SkeletonCard.vue'
import EmptyState from '../../../components/molecules/EmptyState.vue'
import ConfirmModal from '../../../components/molecules/ConfirmModal.vue'
import VerifiedBadge from '../../../components/atoms/VerifiedBadge.vue'
import { MapPinIcon } from '@heroicons/vue/24/outline'

const ANONYMOUS_ROUTE_PARAM = 'anonymous'
const FARMER_ROLE = 'farmer'
const BUYER_ROLE = 'buyer'

const route = useRoute()
const api = useApi()
const authStore = useAuthStore()
const addressStore = useAddressStore()
const notificationStore = useNotificationStore()
const farmingStore = useFarmingStore()

const profile = ref(null)
const isLoading = ref(false)
const notFound = ref(false)
const fetchError = ref('')
let profileRequest = 0

const userId = computed(() => route.params.userId)
const isAnonymous = computed(() => userId.value === ANONYMOUS_ROUTE_PARAM)
const isFarmer = computed(() => profile.value?.role === FARMER_ROLE)
const isBuyer = computed(() => profile.value?.role === BUYER_ROLE)
const showStats = computed(() => isFarmer.value || isBuyer.value)
const isOwnProfile = computed(
  () => !isAnonymous.value && String(authStore.user?.id) === String(userId.value),
)
const hasVerifiedFarm = computed(
  () =>
    isOwnProfile.value &&
    isFarmer.value &&
    Array.isArray(farmingStore.farms) &&
    farmingStore.farms.some((farm) => isFarmVerified(farm)),
)

watch(
  [isOwnProfile, isFarmer],
  ([own, farmer]) => {
    if (own && farmer && farmingStore.farms.length === 0) {
      farmingStore.fetchFarms().catch(() => {})
    }
  },
  { immediate: true },
)

const {
  showAddressModal,
  editingAddress,
  addressDraft,
  addressErrors,
  pinValid,
  savingAddress,
  pendingConfirm,
  confirmConfig,
  openAddAddress,
  openEditAddress,
  closeEditor,
  requestDeleteAddress,
  requestDefaultAddress,
  requestSaveAddress,
  confirmPending,
  cancelConfirm,
} = useProfileAddresses({ addressStore, isOwnProfile, notificationStore, onSaved: fetchProfile })

function formatPostDate(value) {
  return formatDistanceToNow(new Date(value), { addSuffix: true })
}

async function fetchProfile() {
  const request = ++profileRequest
  profile.value = null
  if (isAnonymous.value) {
    isLoading.value = false
    return
  }
  isLoading.value = true
  fetchError.value = ''
  notFound.value = false
  profile.value = null

  try {
    const response = await api.get(`/users/${userId.value}`)
    if (request !== profileRequest) return
    applyProfile(response.data.data)
  } catch (error) {
    if (request !== profileRequest) return
    showProfileError(error)
  } finally {
    if (request === profileRequest) isLoading.value = false
  }
}

function applyProfile(value) {
  profile.value = value
  if (isOwnProfile.value && Array.isArray(value?.addresses))
    addressStore.addresses = value.addresses
}
function showProfileError(error) {
  if (error.response?.status === HTTP_STATUS.NOT_FOUND) notFound.value = true
  else fetchError.value = error.response?.data?.message || 'Unable to load this profile.'
}
watch(userId, fetchProfile, { immediate: true })
</script>

<template>
  <div class="space-y-6">
    <PageHeader title="Profile" />
    <LoadingState v-if="isLoading" label="Loading profile"
      ><div class="space-y-6">
        <SkeletonCard withAvatar />
        <SkeletonCard /></div
    ></LoadingState>
    <AppCard v-else-if="fetchError" role="alert"
      ><p>{{ fetchError }}</p>
      <AppButton variant="outline" @click="fetchProfile">Retry profile</AppButton></AppCard
    >

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
            class="w-16 h-16 rounded-full bg-moss-700 flex items-center justify-center text-white text-2xl font-bold shadow-soft flex-shrink-0"
            aria-hidden="true"
          >
            {{ profile.name?.charAt(0).toUpperCase() }}
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
            <VerifiedBadge v-if="hasVerifiedFarm" size="sm" class="ml-2" />
          </div>
        </div>
      </AppCard>

      <div
        v-if="showStats"
        class="grid grid-cols-1 gap-5"
        :class="isFarmer ? 'sm:grid-cols-3' : 'sm:grid-cols-2'"
      >
        <template v-if="isFarmer">
          <AppCard padding="p-6" class="text-center">
            <p class="text-3xl font-semibold text-moss-700">{{ profile.stats?.total_sold }}</p>
            <p class="text-sm font-medium text-soil-700 mt-1">Items sold</p>
          </AppCard>
          <AppCard padding="p-6" class="text-center">
            <PriceTag :amount="profile.stats?.total_revenue" size="lg" />
            <p class="text-sm font-medium text-soil-700 mt-1">Revenue earned</p>
          </AppCard>
          <AppCard padding="p-6" class="text-center">
            <p class="text-3xl font-semibold text-moss-700">{{ profile.stats?.total_listed }}</p>
            <p class="text-sm font-medium text-soil-700 mt-1">Items listed</p>
          </AppCard>
        </template>
        <template v-else>
          <AppCard padding="p-6" class="text-center">
            <p class="text-3xl font-semibold text-moss-700">
              {{ profile.stats?.total_purchases }}
            </p>
            <p class="text-sm font-medium text-soil-700 mt-1">Purchases</p>
          </AppCard>
          <AppCard padding="p-6" class="text-center">
            <PriceTag :amount="profile.stats?.total_spent" size="lg" />
            <p class="text-sm font-medium text-soil-700 mt-1">Total spent</p>
          </AppCard>
        </template>
      </div>

      <ProfileAddressPanel
        v-if="isOwnProfile"
        :addresses="addressStore.addresses"
        :errors="addressErrors"
        :show-address-modal="showAddressModal"
        :editing-address="editingAddress"
        v-model:address-draft="addressDraft"
        :saving-address="savingAddress"
        @add="openAddAddress"
        @edit="openEditAddress"
        @save="requestSaveAddress"
        @delete="requestDeleteAddress"
        @default="requestDefaultAddress"
        @close="closeEditor"
        @pin-validation="pinValid = $event.valid"
      />

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
        <div v-if="(profile.posts?.length || 0) > 0" class="space-y-4">
          <AppCard v-for="post in profile.posts" :key="post.id" padding="p-5">
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

    <ConfirmModal
      v-if="confirmConfig"
      :is-open="pendingConfirm !== null"
      :title="confirmConfig.title"
      :message="confirmConfig.message"
      :confirm-text="confirmConfig.confirmText"
      :type="confirmConfig.type"
      :loading="savingAddress"
      @confirm="confirmPending"
      @cancel="cancelConfirm"
    />
  </div>
</template>
