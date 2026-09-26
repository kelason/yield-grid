<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useFarmingStore } from '../../stores/farming'
import { useAuthStore } from '../../stores/auth'
import AppButton from '../../components/atoms/AppButton.vue'
import FarmCard from '../../components/molecules/FarmCard.vue'
import CreateFarmForm from '../../components/organisms/CreateFarmForm.vue'
import AppCard from '../../components/atoms/AppCard.vue'

const router = useRouter()
const farmingStore = useFarmingStore()
const authStore = useAuthStore()

const showCreateForm = ref(false)
const createError = ref('')
const isCreating = ref(false)
const SKELETON_COUNT = 3

onMounted(() => {
  farmingStore.fetchFarms()
})

async function handleCreateFarm(payload) {
  isCreating.value = true
  createError.value = ''
  try {
    await farmingStore.createFarm(payload)
    showCreateForm.value = false
  } catch (e) {
    createError.value = e.response?.data?.message || 'Failed to create farm'
  } finally {
    isCreating.value = false
  }
}

function handleViewPlots(farmId) {
  const farm = farmingStore.farms.find((f) => f.id === farmId)
  if (farm) {
    farmingStore.setActiveFarm(farm)
    router.push({ name: 'plot-planner', params: { farmId } })
  }
}

function handleViewRecommendations(farmId) {
  router.push({ name: 'recommendations', query: { farmId } })
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-xl font-bold text-gray-900 tracking-tight">My Farms</h2>
        <p class="text-sm text-gray-500 mt-0.5">
          {{ farmingStore.farms.length }} farm(s) registered
        </p>
      </div>
      <AppButton
        v-if="farmingStore.farms.length"
        variant="primary"
        rounded="full"
        :disabled="!authStore.isEmailVerified"
        @click="showCreateForm = !showCreateForm"
      >
        <svg
          class="mr-2 h-4 w-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 4v16m8-8H4"
          />
        </svg>
        {{ showCreateForm ? 'Cancel' : 'Add Farm' }}
      </AppButton>
    </div>

    <!-- Create form panel -->
    <Transition
      enter-active-class="transition-all duration-200 ease-out"
      enter-from-class="opacity-0 -translate-y-2"
      leave-active-class="transition-all duration-150 ease-in"
      leave-to-class="opacity-0 -translate-y-2"
    >
      <div v-if="showCreateForm">
        <AppCard variant="muted">
          <div class="flex items-center gap-2 mb-5">
            <span class="text-xl">🏡</span>
            <h3 class="text-base font-bold text-gray-900">Create New Farm</h3>
          </div>
          <CreateFarmForm
            :loading="isCreating"
            :error="createError"
            @submit="handleCreateFarm"
            @cancel="showCreateForm = false"
          />
        </AppCard>
      </div>
    </Transition>

    <!-- Loading skeleton -->
    <div
      v-if="farmingStore.loading && !farmingStore.farms.length"
      class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3"
    >
      <div
        v-for="n in SKELETON_COUNT"
        :key="n"
        class="animate-pulse bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4"
      >
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-gray-200 rounded-xl"></div>
          <div class="flex-1 space-y-2">
            <div class="h-4 bg-gray-200 rounded w-3/4"></div>
            <div class="h-3 bg-gray-100 rounded w-1/2"></div>
          </div>
        </div>
        <div class="h-10 bg-gray-100 rounded-lg"></div>
        <div class="h-9 bg-gray-200 rounded-lg"></div>
      </div>
    </div>

    <!-- Empty state -->
    <div
      v-else-if="!farmingStore.farms.length && !showCreateForm"
      class="text-center py-16 bg-white rounded-2xl border-2 border-dashed border-gray-200 hover:border-green-300 transition-colors duration-200"
    >
      <div class="text-5xl mb-4">🌾</div>
      <h3 class="text-base font-semibold text-gray-900 mb-1">No farms yet</h3>
      <p class="text-sm text-gray-500 mb-6 max-w-xs mx-auto">
        Create your first farm to start planning plots and getting AI crop recommendations.
      </p>
      <AppButton
        variant="primary"
        rounded="full"
        :disabled="!authStore.isEmailVerified"
        @click="showCreateForm = true"
      >
        <svg
          class="mr-2 h-4 w-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 4v16m8-8H4"
          />
        </svg>
        Create First Farm
      </AppButton>
    </div>

    <!-- Farm grid -->
    <div v-else-if="!showCreateForm" class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
      <FarmCard
        v-for="farm in farmingStore.farms"
        :key="farm.id"
        :farm="farm"
        @view-plots="handleViewPlots"
        @view-recommendations="handleViewRecommendations"
      />
    </div>
  </div>
</template>
