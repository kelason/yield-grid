<template>
  <div class="recommendations-page max-w-5xl mx-auto py-8 px-4">
    <div class="mb-6">
      <router-link 
        to="/dashboard" 
        class="inline-flex items-center text-sm font-medium text-green-700 hover:text-green-800 transition-colors"
      >
        ← Back to Farm Plots
      </router-link>
    </div>

    <header class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <div class="flex flex-wrap items-center gap-3 mb-2">
          <h1 class="text-3xl font-extrabold text-gray-900">
            {{ store.meta.plot_name ? `${store.meta.plot_name} Recommendations` : 'Crop Recommendations' }}
          </h1>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 text-sm text-gray-600 mt-2">
          <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-gray-100 rounded-lg text-gray-700 font-medium border border-gray-200">
            📐 Area: <strong class="text-gray-900">{{ store.meta.calculated_area ? Number(store.meta.calculated_area).toFixed(2) : '0.00' }} ha</strong>
          </span>
          <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-gray-100 rounded-lg text-gray-700 font-medium border border-gray-200">
            🌱 Soil: <strong class="text-gray-900 capitalize">{{ store.meta.soil_type || 'Unspecified' }}</strong>
          </span>
          <span v-if="locationLabel" class="inline-flex items-center gap-1.5 px-3 py-1 bg-green-50 text-green-800 rounded-lg font-medium border border-green-200">
            📍 {{ locationLabel }}
          </span>
        </div>
      </div>
      <div>
        <button
          @click="triggerAnalysis"
          :disabled="store.isAnalyzing || store.isLoading"
          class="inline-flex items-center gap-2 px-5 py-2.5 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white font-semibold rounded-xl shadow-sm transition-all whitespace-nowrap"
        >
          <span>🌱</span>
          <span v-if="store.isAnalyzing">Analyzing Plot...</span>
          <span v-else>{{ store.recommendations.length > 0 ? 'Re-analyze Plot' : 'Analyze This Plot' }}</span>
        </button>
      </div>
    </header>

    <div v-if="store.errorMessage" class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-sm flex items-center justify-between shadow-sm">
      <div class="flex items-center gap-2.5">
        <span class="text-xl">⚠️</span>
        <span>{{ store.errorMessage }}</span>
      </div>
      <button @click="store.errorMessage = ''" class="text-amber-600 hover:text-amber-800 font-bold ml-4">✕</button>
    </div>

    <div v-if="store.isAnalyzing" class="mb-12">
      <AnalysisProgress :location="locationLabel" />
    </div>

    <div v-else>
      <div v-if="store.recommendations.length > 0" class="space-y-6">
        <RecommendationCard 
          v-for="rec in store.recommendations" 
          :key="rec.id" 
          :recommendation="rec"
          @accept="handleAccept"
          @reject="handleReject"
        />
      </div>
      <div v-else-if="!store.isLoading" class="text-center py-16 px-4 bg-gray-50 rounded-2xl border border-gray-200">
        <div class="text-4xl mb-3">🌾</div>
        <h3 class="text-lg font-semibold text-gray-800 mb-1">No recommendations generated yet</h3>
        <p class="text-gray-500 max-w-md mx-auto mb-6 text-sm">
          Run our AI advisor to inspect soil conditions and weather patterns to get optimized crop recommendations for this {{ store.meta.calculated_area ? Number(store.meta.calculated_area).toFixed(2) + ' ha' : '' }} plot.
        </p>
        <button 
          @click="triggerAnalysis"
          :disabled="store.isAnalyzing"
          class="inline-flex items-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl shadow-sm transition-colors"
        >
          <span>🌱</span> Run AI Analysis Now
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, watch, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import { useRecommendationStore } from '../stores/recommendationStore';
import { useWebSocket } from '../composables/useWebSocket';
import AnalysisProgress from '../components/atoms/AnalysisProgress.vue';
import RecommendationCard from '../components/molecules/RecommendationCard.vue';

const route = useRoute();
const store = useRecommendationStore();
const { listenToPlot, leavePlot } = useWebSocket();

const plotId = computed(() => route.params.id);

const locationLabel = computed(() => {
  const parts = [store.meta?.city, store.meta?.state, store.meta?.country].filter(Boolean);
  return parts.join(', ');
});

const triggerAnalysis = async () => {
  if (plotId.value) {
    await store.analyzePlot(plotId.value);
  }
};

const loadPlotData = async (id) => {
  if (!id) return;
  listenToPlot(id, () => {
    store.isAnalyzing = false;
    store.fetchRecommendations(id);
  });

  await store.fetchRecommendations(id);

  if (route.query.analyze === 'true' || store.recommendations.length === 0) {
    await triggerAnalysis();
  }
};

watch(
  () => route.params.id,
  async (newId, oldId) => {
    if (oldId) {
      leavePlot(oldId);
    }
    if (newId) {
      store.recommendations = [];
      await loadPlotData(newId);
    }
  }
);

onMounted(async () => {
  if (plotId.value) {
    await loadPlotData(plotId.value);
  }
});

onUnmounted(() => {
  if (plotId.value) {
    leavePlot(plotId.value);
  }
});

const handleAccept = async (id) => {
  await store.updateStatus(id, 'accepted');
};

const handleReject = async (id) => {
  if (confirm('Are you sure you want to reject this recommendation?')) {
    await store.updateStatus(id, 'rejected');
  }
};
</script>
