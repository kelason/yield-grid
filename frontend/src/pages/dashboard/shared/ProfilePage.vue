<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { formatDistanceToNow } from 'date-fns'
import { useApi } from '../../../composables/useApi'
import { HTTP_STATUS } from '../../../constants/http'
import AppCard from '../../../components/atoms/AppCard.vue'
import PriceTag from '../../../components/atoms/PriceTag.vue'
import SkeletonCard from '../../../components/atoms/SkeletonCard.vue'
import EmptyState from '../../../components/molecules/EmptyState.vue'

const ANONYMOUS_ROUTE_PARAM = 'anonymous'
const FARMER_ROLE = 'farmer'

const route = useRoute()
const api = useApi()

const profile = ref(null)
const isLoading = ref(false)
const notFound = ref(false)

const userId = computed(() => route.params.userId)
const isAnonymous = computed(() => userId.value === ANONYMOUS_ROUTE_PARAM)
const isFarmer = computed(() => profile.value?.role === FARMER_ROLE)

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
            class="w-16 h-16 rounded-full bg-gradient-to-br from-moss-400 to-soil-600 flex items-center justify-center text-white text-2xl font-bold shadow-sm flex-shrink-0"
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

      <div>
        <h3 class="font-serif text-2xl font-bold text-stone-900 mb-4">Posts</h3>
        <div v-if="profile.posts.length > 0" class="space-y-4">
          <AppCard v-for="post in profile.posts" :key="post.id" padding="p-5" :hover="true">
            <RouterLink
              :to="{ name: 'forum-thread', params: { id: post.id } }"
              class="font-serif text-xl font-bold text-stone-900 hover:text-moss-700 transition-colors duration-200 motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500 rounded"
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
  </div>
</template>
