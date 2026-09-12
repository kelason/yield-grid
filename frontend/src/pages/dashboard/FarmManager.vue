<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useFarmingStore } from '../../stores/farming'
import AppButton from '../../components/atoms/AppButton.vue'
import FarmCard from '../../components/molecules/FarmCard.vue'
import CreateFarmForm from '../../components/organisms/CreateFarmForm.vue'
import AppCard from '../../components/atoms/AppCard.vue'

const router = useRouter()
const farmingStore = useFarmingStore()

const showCreateForm = ref(false)
const createError = ref('')
const isCreating = ref(false)

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
  const farm = farmingStore.farms.find(f => f.id === farmId)
  if (farm) {
    farmingStore.setActiveFarm(farm)
    router.push({ name: 'plot-planner', params: { farmId } })
  }
}
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl font-medium text-gray-900">My Farms</h2>
      <AppButton variant="primary" @click="showCreateForm = !showCreateForm">
        {{ showCreateForm ? 'Cancel' : 'Add New Farm' }}
      </AppButton>
    </div>

    <!-- Create Form -->
    <div v-if="showCreateForm" class="mb-8">
      <AppCard class="bg-gray-50 border-gray-200">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Create New Farm</h3>
        <CreateFarmForm 
          :loading="isCreating" 
          :error="createError"
          @submit="handleCreateFarm"
          @cancel="showCreateForm = false"
        />
      </AppCard>
    </div>

    <!-- Loading State -->
    <div v-if="farmingStore.loading && farmingStore.farms.length === 0" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-farm-600"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="farmingStore.farms.length === 0" class="text-center py-12 bg-white rounded-lg shadow-sm border border-gray-100 border-dashed">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">No farms</h3>
      <p class="mt-1 text-sm text-gray-500">Get started by creating a new farm.</p>
      <div class="mt-6">
        <AppButton variant="primary" @click="showCreateForm = true">
          <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
          </svg>
          New Farm
        </AppButton>
      </div>
    </div>

    <!-- Farm Grid -->
    <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <FarmCard 
        v-for="farm in farmingStore.farms" 
        :key="farm.id" 
        :farm="farm" 
        @view-plots="handleViewPlots"
      />
    </div>
  </div>
</template>
