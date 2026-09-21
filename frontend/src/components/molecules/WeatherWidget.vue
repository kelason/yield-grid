<template>
  <div
    v-if="weather"
    class="weather-widget flex items-center gap-4 bg-gradient-to-br from-dew-50 to-dew-100 border border-dew-200 rounded-2xl shadow-soft px-5 py-4"
  >
    <!-- Icon + Temp -->
    <div class="flex items-center gap-3">
      <div class="text-4xl leading-none">
        {{ getWeatherIcon(weather.weather[0]?.main) }}
      </div>
      <div>
        <div class="text-2xl font-extrabold text-dew-900 leading-tight">
          {{ Math.round(weather.main?.temp)
          }}<span class="text-lg font-semibold text-dew-600">°C</span>
        </div>
        <div class="text-xs text-dew-600 capitalize mt-0.5">
          {{ weather.weather[0]?.description }}
        </div>
      </div>
    </div>

    <!-- Divider -->
    <div class="h-10 w-px bg-dew-200 flex-shrink-0"></div>

    <!-- Stats -->
    <div class="flex items-center gap-4 text-sm text-dew-700">
      <div class="flex flex-col items-center">
        <span class="text-base leading-none">💧</span>
        <span class="font-semibold text-dew-900 mt-0.5">{{ weather.main?.humidity }}%</span>
        <span class="text-[10px] text-dew-500 uppercase tracking-wide">Humidity</span>
      </div>
      <div v-if="weather.wind?.speed" class="flex flex-col items-center">
        <span class="text-base leading-none">🌬️</span>
        <span class="font-semibold text-dew-900 mt-0.5">{{ Math.round(weather.wind.speed) }}</span>
        <span class="text-[10px] text-dew-500 uppercase tracking-wide">km/h</span>
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
