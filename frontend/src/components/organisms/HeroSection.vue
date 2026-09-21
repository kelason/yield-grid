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
    class="relative bg-gradient-to-br from-stone-100 via-white to-moss-50 overflow-hidden min-h-[90vh] flex items-center"
  >
    <!-- Organic background blobs -->
    <div
      class="absolute top-0 right-0 w-[700px] h-[700px] bg-moss-200/20 rounded-full -translate-y-1/3 translate-x-1/3 blur-3xl pointer-events-none"
      aria-hidden="true"
    ></div>
    <div
      class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-harvest-200/20 rounded-full translate-y-1/3 -translate-x-1/3 blur-3xl pointer-events-none"
      aria-hidden="true"
    ></div>
    <div
      class="absolute top-1/2 right-1/4 w-[300px] h-[300px] bg-soil-200/10 rounded-full blur-2xl pointer-events-none"
      aria-hidden="true"
    ></div>

    <div class="max-w-7xl mx-auto w-full relative z-10 px-4 sm:px-6 lg:px-8">
      <div class="grid lg:grid-cols-2 gap-12 items-center py-16 lg:py-24">
        <!-- Left: Copy -->
        <div>
          <!-- Live badge — harvest amber for warmth -->
          <div
            class="inline-flex items-center gap-2 bg-harvest-100 text-harvest-700 text-xs font-semibold px-3 py-1.5 rounded-full border border-harvest-200 mb-6"
          >
            <span class="w-1.5 h-1.5 rounded-full bg-harvest-500 animate-pulse"></span>
            AI-Powered Crop Intelligence
          </div>

          <!-- Lora serif headline for organic authority -->
          <h1
            class="font-serif text-5xl tracking-tight font-bold text-stone-900 sm:text-6xl leading-tight mb-4"
          >
            <span class="block">Smarter farming,</span>
            <span
              class="block text-transparent bg-clip-text bg-gradient-to-r from-moss-600 to-moss-400"
            >
              better harvest.
            </span>
          </h1>

          <p class="text-lg text-stone-600 font-light leading-relaxed max-w-xl mb-8">
            Connect with local buyers, plan your crops with AI, and manage your farm's success — all
            in one premium platform built for the modern farmer.
          </p>

          <!-- CTA row -->
          <div class="flex flex-wrap gap-4">
            <AppButton
              variant="primary"
              size="lg"
              rounded="full"
              @click="$router.push('/auth/register')"
            >
              Get Started Today →
            </AppButton>
            <AppButton variant="outline" size="lg" rounded="full" @click="$router.push('/about')">
              Learn More
            </AppButton>
          </div>

          <!-- Social proof -->
          <div class="mt-10 flex flex-wrap items-center gap-6 text-sm text-stone-500">
            <div class="flex items-center gap-1.5">
              <span class="text-moss-500 font-bold">✓</span> No credit card required
            </div>
            <div class="flex items-center gap-1.5">
              <span class="text-moss-500 font-bold">✓</span> Free to start
            </div>
            <div class="flex items-center gap-1.5">
              <span class="text-moss-500 font-bold">✓</span> Trusted by farmers
            </div>
          </div>
        </div>

        <!-- Right: Feature card grid — organic glassmorphism -->
        <div class="hidden lg:grid grid-cols-2 gap-4">
          <div
            v-for="(feature, i) in features"
            :key="i"
            :class="[
              'bg-white/80 backdrop-blur-sm border border-stone-100 rounded-2xl p-5 shadow-soft hover:shadow-organic hover:-translate-y-1 transition-all duration-300',
              i === 0 && 'col-span-2',
            ]"
          >
            <div class="text-2xl mb-2">{{ feature.icon }}</div>
            <h3 class="font-semibold text-stone-900 text-sm mb-1 font-serif">
              {{ feature.title }}
            </h3>
            <p class="text-xs text-stone-500 leading-relaxed">{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Organic wave divider at the bottom -->
    <div class="absolute bottom-0 left-0 right-0 overflow-hidden leading-none pointer-events-none">
      <svg
        viewBox="0 0 1440 60"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        preserveAspectRatio="none"
        class="w-full h-12 sm:h-16"
      >
        <path
          d="M0 60L60 50C120 40 240 20 360 15C480 10 600 20 720 25C840 30 960 30 1080 25C1200 20 1320 10 1380 5L1440 0V60H0Z"
          fill="white"
          fill-opacity="0.6"
        />
      </svg>
    </div>
  </div>
</template>
