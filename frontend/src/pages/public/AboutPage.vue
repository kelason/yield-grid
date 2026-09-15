<template>
  <div class="bg-white">

    <!-- Hero -->
    <div class="relative bg-gradient-to-br from-earth-50 via-white to-farm-50 py-20 overflow-hidden">
      <div class="absolute top-0 right-0 w-96 h-96 bg-farm-200/20 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl" aria-hidden="true"></div>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <span class="inline-flex items-center gap-1.5 bg-farm-100 text-farm-700 text-xs font-semibold px-3 py-1.5 rounded-full border border-farm-200 mb-6">
          <span class="w-1.5 h-1.5 rounded-full bg-farm-500 animate-pulse"></span>
          Our Story
        </span>
        <h1 class="text-5xl font-extrabold text-gray-900 tracking-tight mb-6">
          Empowering the<br />
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-farm-500 to-farm-700">Modern Farmer</span>
        </h1>
        <p class="text-lg text-gray-600 font-light max-w-2xl mx-auto leading-relaxed">
          YieldGrid was founded with a single mission: to bring cutting-edge AI and market analytics
          to farmers of all sizes. By bridging the gap between local growers and high-demand buyers,
          we help create sustainable, profitable agricultural ecosystems.
        </p>
      </div>
    </div>

    <!-- Stats strip -->
    <div class="bg-farm-600 text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
          <div v-for="stat in stats" :key="stat.label">
            <div class="text-3xl font-extrabold mb-1">{{ stat.value }}</div>
            <div class="text-sm text-farm-200">{{ stat.label }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Features grid -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
      <div class="text-center mb-14">
        <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight mb-3">
          Built for every stage of farming
        </h2>
        <p class="text-gray-500 max-w-xl mx-auto text-base">
          From planning to harvest, YieldGrid gives you the tools to work smarter.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="feature in features"
          :key="feature.title"
          class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 hover:border-farm-200 transition-all duration-200"
        >
          <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-farm-50 to-farm-100 flex items-center justify-center text-2xl mb-4 border border-farm-100">
            {{ feature.icon }}
          </div>
          <h3 class="text-base font-bold text-gray-900 mb-2">{{ feature.title }}</h3>
          <p class="text-sm text-gray-500 leading-relaxed">{{ feature.description }}</p>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
const stats = [
  { value: '10,000+', label: 'Farmers onboarded' },
  { value: '50,000+', label: 'Plots analysed' },
  { value: '95%', label: 'Recommendation accuracy' },
  { value: '40+', label: 'Countries served' },
]

const features = [
  {
    icon: '🤖',
    title: 'AI Crop Advisor',
    description: 'GPT-powered recommendations tuned to your soil composition, local climate patterns, and current market demand.',
  },
  {
    icon: '🗺️',
    title: 'Interactive Plot Mapping',
    description: 'Draw precise farm plot boundaries on a live map with PostGIS-backed geometry for accurate area calculations.',
  },
  {
    icon: '📈',
    title: 'Market Connectivity',
    description: 'List your crops and connect with verified buyers. Lock in forward contracts before you even plant a seed.',
  },
  {
    icon: '🌤️',
    title: 'Live Weather Integration',
    description: 'Real-time weather data from OpenWeatherMap, integrated into every AI recommendation for your location.',
  },
  {
    icon: '📊',
    title: 'Yield Analytics',
    description: 'Track projected vs actual yields per plot over time to optimise future crop planning.',
  },
  {
    icon: '🔔',
    title: 'Real-Time Alerts',
    description: 'Instant WebSocket notifications when your AI analysis completes or a buyer responds to your listing.',
  },
]
</script>
