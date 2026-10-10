<script setup>
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRouter } from 'vue-router'
import { MapIcon, Squares2X2Icon, SparklesIcon, ArrowUpRightIcon } from '@heroicons/vue/24/outline'
import { useFarmingStore } from '@/stores/farming'
import AppCard from '@/components/atoms/AppCard.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import SkeletonCard from '@/components/atoms/SkeletonCard.vue'
import PageHeader from '@/components/molecules/PageHeader.vue'
import StatCard from '@/components/molecules/StatCard.vue'
import LoadingState from '@/components/molecules/LoadingState.vue'
import EmptyState from '@/components/molecules/EmptyState.vue'
import FarmCard from '@/components/molecules/FarmCard.vue'
const { t } = useI18n()
const farmingStore = useFarmingStore()
const router = useRouter()
const loading = ref(true)
const error = ref('')
const SKELETON_COUNT = 2
const farms = computed(() => farmingStore.farms ?? [])
const totalPlots = computed(() =>
  farms.value.reduce((total, farm) => total + (farm?.plots_count || 0), 0),
)
async function fetchFarms() {
  loading.value = true
  error.value = ''
  try {
    await farmingStore.fetchFarms()
  } catch {
    error.value = t('farmer.dashboard.load_error')
  } finally {
    loading.value = false
  }
}
function viewPlots(farmId) {
  router.push({ name: 'plot-planner', params: { farmId } })
}
function viewRecommendations(farmId) {
  router.push({ name: 'recommendations', query: { farmId } })
}
onMounted(fetchFarms)
</script>
<template>
  <div class="space-y-8" :aria-busy="loading">
    <PageHeader
      :title="t('farmer.dashboard.title')"
      :description="t('farmer.dashboard.description')"
    >
      <template #actions
        ><RouterLink
          :to="{ name: 'farm-manager' }"
          class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-gradient-to-br from-moss-500 to-moss-600 px-5 py-3 text-sm font-medium text-white transition-all duration-300 hover:scale-[1.02] hover:from-moss-600 hover:to-moss-700 motion-reduce:transform-none"
          >{{ t('farmer.dashboard.manage_farms') }}
          <ArrowUpRightIcon class="h-4 w-4" aria-hidden="true" /></RouterLink
      ></template>
    </PageHeader>
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
      <StatCard
        :label="t('farmer.dashboard.stat_farms')"
        :value="error || loading ? null : farms.length"
        :loading="loading"
        ><template #icon><Squares2X2Icon class="h-5 w-5" /></template
      ></StatCard>
      <StatCard
        :label="t('farmer.dashboard.stat_plots')"
        :value="error || loading ? null : totalPlots"
        :loading="loading"
        ><template #icon><MapIcon class="h-5 w-5" /></template
      ></StatCard>
      <AppCard variant="dew"
        ><SparklesIcon class="h-5 w-5 text-dew-700" aria-hidden="true" />
        <h2 class="mt-3 font-serif text-xl font-bold text-stone-900">
          {{ t('farmer.dashboard.advisor_title') }}
        </h2>
        <p class="mt-2 text-sm leading-relaxed text-stone-600">
          {{ t('farmer.dashboard.advisor_text') }}
        </p>
        <RouterLink
          :to="{ name: 'recommendations' }"
          class="mt-4 inline-flex min-h-11 items-center gap-2 rounded-xl text-sm font-medium text-dew-700 transition-colors hover:text-dew-900"
          >{{ t('farmer.dashboard.view_recommendations') }}
          <ArrowUpRightIcon class="h-4 w-4" aria-hidden="true" /></RouterLink
      ></AppCard>
    </div>
    <section aria-labelledby="farms-heading" class="space-y-5">
      <div class="flex items-center justify-between gap-4">
        <h2 id="farms-heading" class="font-serif text-2xl font-bold text-stone-900">
          {{ t('farmer.dashboard.your_farms') }}
        </h2>
        <span v-if="!loading && !error" class="text-sm text-stone-600">{{
          t('farmer.dashboard.registered_count', { count: farms.length })
        }}</span>
      </div>
      <LoadingState v-if="loading" :label="t('farmer.farms.loading')"
        ><div class="grid gap-5 lg:grid-cols-2">
          <SkeletonCard v-for="index in SKELETON_COUNT" :key="index" with-avatar with-action /></div
      ></LoadingState>
      <AppCard v-else-if="error" role="alert"
        ><h3 class="font-serif text-xl font-bold text-stone-900">{{ error }}</h3>
        <p class="mt-2 text-sm text-stone-600">
          {{ t('farmer.dashboard.retry_hint') }}
        </p>
        <AppButton variant="secondary" class="mt-4" @click="fetchFarms">{{
          t('shell.retry')
        }}</AppButton></AppCard
      >
      <EmptyState
        v-else-if="!farms.length"
        :title="t('farmer.common.no_farms')"
        :description="t('farmer.dashboard.empty_description')"
        ><template #action
          ><RouterLink
            :to="{ name: 'farm-manager' }"
            class="inline-flex min-h-11 items-center rounded-xl bg-moss-600 px-5 py-3 text-sm font-medium text-white transition-colors hover:bg-moss-700"
            >{{ t('farmer.dashboard.empty_action') }}</RouterLink
          ></template
        ></EmptyState
      >
      <div v-else class="grid gap-5 lg:grid-cols-2">
        <FarmCard
          v-for="farm in farms"
          :key="farm.id"
          :farm="farm"
          @view-plots="viewPlots"
          @view-recommendations="viewRecommendations"
        />
      </div>
    </section>
  </div>
</template>
