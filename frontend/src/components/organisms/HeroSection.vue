<script setup>
import AppButton from '../atoms/AppButton.vue'

const features = [
  {
    icon: '🤖',
    title: 'AI Crop Advisor',
    description:
      'Personalised crop recommendations powered by GPT-4, tuned to your soil and climate.',
  },
  {
    icon: '🗺️',
    title: 'Plot Mapping',
    description: 'Draw and manage farm plots with precise geo-boundary tools.',
  },
  {
    icon: '📈',
    title: 'Market Insights',
    description: 'Connect with verified buyers and lock in forward contracts.',
  },
  {
    icon: '🌤️',
    title: 'Live Weather',
    description: 'Real-time weather data integrated into every recommendation.',
  },
]
</script>

<template>
  <div
    class="relative bg-gradient-to-br from-earth-50 via-white to-farm-50 overflow-hidden min-h-[90vh] flex items-center"
  >
    <!-- Decorative blobs -->
    <div
      class="absolute top-0 right-0 w-[600px] h-[600px] bg-farm-200/20 rounded-full -translate-y-1/3 translate-x-1/3 blur-3xl pointer-events-none"
      aria-hidden="true"
    ></div>
    <div
      class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-earth-200/20 rounded-full translate-y-1/3 -translate-x-1/3 blur-3xl pointer-events-none"
      aria-hidden="true"
    ></div>
    <div
      class="absolute inset-y-0 right-0 w-1/2 bg-gradient-to-l from-farm-100/40 to-transparent transform skew-x-12 hidden lg:block pointer-events-none"
      aria-hidden="true"
    ></div>

    <div class="max-w-7xl mx-auto w-full relative z-10 px-4 sm:px-6 lg:px-8">
      <div class="grid lg:grid-cols-2 gap-12 items-center py-16 lg:py-24">
        <!-- Left: Copy -->
        <div>
          <!-- Live badge -->
          <div
            class="inline-flex items-center gap-2 bg-farm-100 text-farm-700 text-xs font-semibold px-3 py-1.5 rounded-full border border-farm-200 mb-6"
          >
            <span class="w-1.5 h-1.5 rounded-full bg-farm-500 animate-pulse"></span>
            AI-Powered Crop Intelligence
          </div>

          <h1
            class="text-5xl tracking-tight font-extrabold text-gray-900 sm:text-6xl leading-tight mb-4"
          >
            <span class="block">Smarter farming</span>
            <span
              class="block text-transparent bg-clip-text bg-gradient-to-r from-farm-500 to-farm-700"
            >
              for a better harvest
            </span>
          </h1>

          <p class="text-lg text-gray-600 font-light leading-relaxed max-w-xl mb-8">
            Connect with local buyers, plan your crops with AI, and manage your farm's success — all
            in one premium platform built for the modern farmer.
          </p>

          <!-- CTA row -->
          <div class="flex flex-wrap gap-4">
            <div class="transform hover:-translate-y-1 transition-all duration-300">
              <AppButton
                variant="primary"
                size="lg"
                rounded="full"
                @click="$router.push('/auth/register')"
              >
                Get Started Today →
              </AppButton>
            </div>
            <div class="transform hover:-translate-y-1 transition-all duration-300">
              <AppButton variant="outline" size="lg" rounded="full" @click="$router.push('/about')">
                Learn More
              </AppButton>
            </div>
          </div>

          <!-- Social proof -->
          <div class="mt-10 flex flex-wrap items-center gap-6 text-sm text-gray-500">
            <div class="flex items-center gap-1.5">
              <span class="text-farm-500 font-bold">✓</span> No credit card required
            </div>
            <div class="flex items-center gap-1.5">
              <span class="text-farm-500 font-bold">✓</span> Free to start
            </div>
          </div>
        </div>

        <!-- Right: Feature card grid -->
        <div class="hidden lg:grid grid-cols-2 gap-4">
          <div
            v-for="(feature, i) in features"
            :key="i"
            :class="[
              'bg-white/80 backdrop-blur-sm border border-gray-100 rounded-2xl p-5 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-200',
              i === 0 && 'col-span-2',
            ]"
          >
            <div class="text-2xl mb-2">{{ feature.icon }}</div>
            <h3 class="font-semibold text-gray-900 text-sm mb-1">{{ feature.title }}</h3>
            <p class="text-xs text-gray-500 leading-relaxed">{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
