<template>
  <div v-if="weather" class="weather-widget flex items-center gap-4 bg-white p-4 rounded-xl shadow-sm border border-gray-100">
    <div class="weather-icon text-3xl">
      {{ getWeatherIcon(weather.weather[0]?.main) }}
    </div>
    <div>
      <div class="text-xl font-bold text-gray-800">{{ Math.round(weather.main?.temp) }}°C</div>
      <div class="text-sm text-gray-500 capitalize">{{ weather.weather[0]?.description }}</div>
    </div>
    <div class="ml-auto flex items-center gap-2 text-sm text-gray-500 border-l border-gray-200 pl-4">
      <div>💧 {{ weather.main?.humidity }}%</div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  weather: {
    type: Object,
    required: false,
    default: null
  }
});

const getWeatherIcon = (main) => {
  const icons = {
    'Clear': '☀️',
    'Clouds': '☁️',
    'Rain': '🌧️',
    'Drizzle': '🌦️',
    'Thunderstorm': '⛈️',
    'Snow': '❄️',
  };
  return icons[main] || '🌡️';
};
</script>
