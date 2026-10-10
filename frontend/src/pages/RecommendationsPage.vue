<script setup>
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useRecommendationAnalysis } from '@/composables/useRecommendationAnalysis'
import AppButton from '@/components/atoms/AppButton.vue'
import AppAlert from '@/components/atoms/AppAlert.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import SkeletonCard from '@/components/atoms/SkeletonCard.vue'
import PageHeader from '@/components/molecules/PageHeader.vue'
import LoadingState from '@/components/molecules/LoadingState.vue'
import EmptyState from '@/components/molecules/EmptyState.vue'
import RecommendationCard from '@/components/molecules/RecommendationCard.vue'
import AppModal from '@/components/molecules/AppModal.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import PublishContractForm from '@/components/organisms/PublishContractForm.vue'
import RecommendationAnalysisPanel from '@/components/organisms/RecommendationAnalysisPanel.vue'
const RECOMMENDATION_SKELETON_COUNT = 3
const { t } = useI18n()
const {
  store,
  farmingStore,
  marketStore,
  activePlotId,
  currentPlot,
  isLoadingPlots,
  plotsError,
  plotDisplayName,
  plotArea,
  plotSoilType,
  locationLabel,
  analysisPrefs,
  retryPlots,
  retryRecommendations,
  showPublishModal,
  selectedRecommendation,
  publishErrors,
  closePublishModal,
  handlePublishContract,
  handlePlotChange,
  triggerAnalysis,
  handleAccept,
  handleReject,
  isOpen,
  isExecuting,
  config,
  execute,
  cancel,
} = useRecommendationAnalysis({ route: useRoute(), router: useRouter() })
</script>
<template>
  <div class="space-y-6">
    <PageHeader
      :title="
        activePlotId
          ? t('farmer.recommendations.plot_title', { name: plotDisplayName })
          : t('farmer.recommendations.title')
      "
      :description="t('farmer.recommendations.description')"
    >
      <template #actions
        ><router-link
          :to="{ name: 'farm-manager' }"
          class="inline-flex min-h-11 items-center rounded-xl px-4 text-sm font-medium text-moss-700 transition-colors hover:bg-moss-50"
          >{{ t('farmer.recommendations.back_farms') }}</router-link
        ></template
      >
    </PageHeader>
    <AppSelect
      v-if="farmingStore.allPlots.length > 1"
      id="plot-selector"
      :label="t('farmer.recommendations.plot_label')"
      :model-value="activePlotId"
      @update:model-value="handlePlotChange(Number($event))"
    >
      <option v-for="plot in farmingStore.allPlots" :key="plot.id" :value="plot.id">
        {{
          t('farmer.recommendations.option', {
            name: plot.name,
            farm: plot.farm_name || t('farmer.recommendations.farm_fallback'),
            area: plot.calculated_area ? Number(plot.calculated_area).toFixed(2) : 0,
          })
        }}
      </option>
    </AppSelect>
    <LoadingState v-if="isLoadingPlots" :label="t('farmer.plots.loading')" class="space-y-5"
      ><SkeletonCard v-for="n in RECOMMENDATION_SKELETON_COUNT" :key="n" withAvatar withAction
    /></LoadingState>
    <div v-else-if="plotsError" class="space-y-3">
      <AppAlert type="error">{{ plotsError }}</AppAlert
      ><AppButton @click="retryPlots">{{ t('shell.retry') }}</AppButton>
    </div>
    <EmptyState
      v-else-if="farmingStore.allPlots.length === 0"
      :title="t('farmer.recommendations.no_plots_title')"
      :description="t('farmer.recommendations.no_plots_desc')"
    />
    <template v-else>
      <p class="text-sm text-stone-600">
        {{ plotArea }} ha · {{ plotSoilType
        }}<span v-if="locationLabel"> · {{ locationLabel }}</span
        ><span v-if="currentPlot?.farm_name"> · {{ currentPlot.farm_name }}</span>
      </p>
      <RecommendationAnalysisPanel
        :analyzing="store.isAnalyzing"
        :taxonomy="store.taxonomy"
        :location="locationLabel"
        :error="store.errorMessage"
        @update:preferences="analysisPrefs = $event"
        @request-analysis="triggerAnalysis"
        @dismiss-error="store.errorMessage = ''"
      />
      <template v-if="!store.isAnalyzing">
        <LoadingState
          v-if="store.isLoading"
          :label="t('farmer.recommendations.loading')"
          class="space-y-5"
          ><SkeletonCard v-for="n in RECOMMENDATION_SKELETON_COUNT" :key="n" withAvatar withAction
        /></LoadingState>
        <div v-else-if="store.fetchError" class="space-y-3">
          <AppAlert type="error">{{ store.fetchError }}</AppAlert
          ><AppButton @click="retryRecommendations">{{
            t('farmer.recommendations.retry_button')
          }}</AppButton>
        </div>
        <div v-else-if="store.recommendations.length > 0" class="space-y-5">
          <div v-if="store.availableTypes.length > 0" class="flex flex-wrap gap-2">
            <AppButton
              size="sm"
              :variant="store.typeFilter === '' ? 'primary' : 'outline'"
              :aria-pressed="store.typeFilter === ''"
              @click="store.typeFilter = ''"
              >{{ t('farmer.recommendations.all_types') }}</AppButton
            >
            <AppButton
              v-for="type in store.availableTypes"
              :key="type"
              size="sm"
              :variant="store.typeFilter === type ? 'primary' : 'outline'"
              :aria-pressed="store.typeFilter === type"
              @click="store.typeFilter = type"
              >{{ store.typeLabel(type) }}</AppButton
            >
          </div>
          <p
            v-if="store.typeFilter && store.filteredRecommendations.length === 0"
            data-test="filter-empty-hint"
            class="text-center text-sm text-stone-600 py-6"
          >
            {{ t('farmer.recommendations.filter_empty') }}
          </p>
          <RecommendationCard
            v-for="recommendation in store.filteredRecommendations"
            :key="recommendation.id"
            :recommendation="recommendation"
            @accept="handleAccept"
            @reject="handleReject"
          />
        </div>
        <EmptyState
          v-else
          :title="t('farmer.recommendations.empty_title')"
          :description="t('farmer.recommendations.empty_desc')"
        >
          <template #action
            ><AppButton :disabled="!activePlotId" @click="triggerAnalysis()">{{
              t('farmer.recommendations.run_analysis')
            }}</AppButton></template
          >
        </EmptyState>
      </template>
    </template>
    <AppModal
      :title="t('farmer.recommendations.publish_title')"
      size="lg"
      :is-open="showPublishModal"
      :busy="isExecuting"
      @close="closePublishModal"
    >
      <PublishContractForm
        v-if="selectedRecommendation"
        :recommendation="selectedRecommendation"
        :loading="marketStore.loading?.publish || isExecuting"
        :errors="publishErrors"
        @publish="handlePublishContract"
        @cancel="closePublishModal"
        @clear-errors="publishErrors = {}"
      />
    </AppModal>
    <ConfirmModal
      :is-open="isOpen"
      :title="config.title"
      :message="config.message"
      :confirm-text="config.confirmText"
      :type="config.type"
      :loading="isExecuting"
      @confirm="execute"
      @cancel="cancel"
    />
  </div>
</template>
