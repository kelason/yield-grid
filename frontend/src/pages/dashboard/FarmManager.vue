<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useFarmingStore } from '../../stores/farming'
import { useAuthStore } from '../../stores/auth'
import AppButton from '../../components/atoms/AppButton.vue'
import FarmCard from '../../components/molecules/FarmCard.vue'
import CreateFarmForm from '../../components/organisms/CreateFarmForm.vue'
import PageHeader from '@/components/molecules/PageHeader.vue'
import LoadingState from '@/components/molecules/LoadingState.vue'
import EmptyState from '@/components/molecules/EmptyState.vue'
import SkeletonCard from '@/components/atoms/SkeletonCard.vue'
import AppAlert from '@/components/atoms/AppAlert.vue'
import { HomeModernIcon } from '@heroicons/vue/24/outline'
import AppCard from '../../components/atoms/AppCard.vue'

const router = useRouter()
const farmingStore = useFarmingStore()
const authStore = useAuthStore()

const showCreateForm = ref(false)
const createError = ref('')
const isCreating = ref(false)
const SKELETON_COUNT = 3
const farmsError = ref('')

async function loadFarms() {
  farmsError.value = ''
  try {
    await farmingStore.fetchFarms()
  } catch (error) {
    farmsError.value = error.response?.data?.message || 'Could not load your farms. Please retry.'
  }
}
onMounted(loadFarms)

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
    <PageHeader
      title="My Farms"
      :description="
        farmingStore.loading
          ? 'Loading your registered farms…'
          : `${farmingStore.farms.length} farm(s) registered`
      "
    >
      <template #actions
        ><AppButton
          v-if="farmingStore.farms.length"
          :disabled="!authStore.isEmailVerified"
          @click="showCreateForm = !showCreateForm"
          >{{ showCreateForm ? 'Cancel' : 'Add Farm' }}</AppButton
        ></template
      >
    </PageHeader>
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
            <HomeModernIcon class="h-6 w-6 text-moss-700" aria-hidden="true" />
            <h3 class="font-serif text-base font-bold text-stone-900">Create New Farm</h3>
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

    <LoadingState
      v-if="farmingStore.loading && !farmingStore.farms.length"
      label="Loading farms"
      class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3"
      ><SkeletonCard v-for="n in SKELETON_COUNT" :key="n" withAction
    /></LoadingState>
    <div v-else-if="farmsError" class="space-y-3">
      <AppAlert type="error">{{ farmsError }}</AppAlert
      ><AppButton @click="loadFarms">Retry</AppButton>
    </div>
    <EmptyState
      v-else-if="!farmingStore.farms.length && !showCreateForm"
      title="No farms yet"
      description="Create your first farm to start planning plots and getting crop recommendations."
    >
      <template #action
        ><AppButton :disabled="!authStore.isEmailVerified" @click="showCreateForm = true"
          >Create First Farm</AppButton
        ></template
      >
    </EmptyState>

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
