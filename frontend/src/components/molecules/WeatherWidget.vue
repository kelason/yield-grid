<template>
  <div
    v-if="weather"
    class="weather-widget flex items-center gap-4 bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl shadow-lg px-5 py-4"
  >
    <!-- Icon + Temp -->
    <div class="flex items-center gap-3">
      <div class="text-4xl leading-none">
        {{ getWeatherIcon(weather.weather[0]?.main) }}
      </div>
      <div>
        <div class="text-2xl font-extrabold text-gray-900 leading-tight">
          {{ Math.round(weather.main?.temp)
          }}<span class="text-lg font-semibold text-gray-500">°C</span>
        </div>
        <div class="text-xs text-gray-500 capitalize mt-0.5">
          {{ weather.weather[0]?.description }}
        </div>
      </div>
    </div>

    <!-- Divider -->
    <div class="h-10 w-px bg-gray-200 flex-shrink-0"></div>

    <!-- Stats -->
    <div class="flex items-center gap-4 text-sm text-gray-600">
      <div class="flex flex-col items-center">
        <span class="text-base leading-none">💧</span>
        <span class="font-semibold text-gray-800 mt-0.5">{{ weather.main?.humidity }}%</span>
        <span class="text-[10px] text-gray-400 uppercase tracking-wide">Humidity</span>
      </div>
      <div v-if="weather.wind?.speed" class="flex flex-col items-center">
        <span class="text-base leading-none">🌬️</span>
        <span class="font-semibold text-gray-800 mt-0.5">{{ Math.round(weather.wind.speed) }}</span>
        <span class="text-[10px] text-gray-400 uppercase tracking-wide">km/h</span>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  weather: {
    type: Object,
    required: false,
    default: null,
  },
})

const getWeatherIcon = (main) => {
  const icons = {
    Clear: '☀️',
    Clouds: '☁️',
    Rain: '🌧️',
    Drizzle: '🌦️',
    Thunderstorm: '⛈️',
    Snow: '❄️',
    Mist: '🌫️',
    Fog: '🌫️',
    Haze: '🌫️',
  }
  return icons[main] || '🌡️'
}
</script>
