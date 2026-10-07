<script setup>
import { useI18n } from 'vue-i18n'
import AppCard from '@/components/atoms/AppCard.vue'
import EmptyState from '@/components/molecules/EmptyState.vue'

defineProps({
  offices: { type: Array, required: true },
})

const { t } = useI18n()
</script>

<template>
  <AppCard>
    <h2 class="font-serif text-2xl font-bold text-stone-900">{{ t('insurance.offices.title') }}</h2>
    <p class="mt-1 text-base text-stone-600 font-normal leading-relaxed">
      {{ t('insurance.offices.subtitle') }}
    </p>
    <div v-if="offices.length === 0" data-testid="offices-empty" class="mt-4">
      <EmptyState
        :title="t('insurance.offices.title')"
        :description="t('insurance.offices.empty')"
      />
    </div>
    <div v-else class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-5">
      <article
        v-for="office in offices"
        :key="office.region_code || 'head'"
        data-testid="office-card"
        class="rounded-2xl border p-5 transition-all duration-300"
        :class="
          office.is_serving_region
            ? 'border-moss-300 bg-moss-50 shadow-soft'
            : 'border-stone-200 bg-white shadow-soft'
        "
      >
        <div class="flex flex-wrap items-center gap-2">
          <h3 class="font-serif text-lg font-bold text-stone-900">{{ office.name }}</h3>
          <span
            v-if="office.is_serving_region"
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-moss-100 text-moss-800"
          >
            {{ t('insurance.offices.serving_region') }}
          </span>
          <span
            v-else-if="office.is_head_office"
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-stone-100 text-stone-800"
          >
            {{ t('insurance.offices.head_office') }}
          </span>
        </div>
        <p v-if="office.city" class="mt-1 text-sm font-medium text-soil-700">{{ office.city }}</p>
        <p v-if="office.address" class="mt-1 text-base text-stone-600 font-normal leading-relaxed">
          {{ office.address }}
        </p>
        <p v-if="office.phone" class="mt-1 text-base text-stone-600 font-normal">
          {{ office.phone }}
        </p>
        <p
          v-if="!office.phone && !office.address"
          class="mt-2 text-sm text-stone-600 font-normal italic"
        >
          {{ t('insurance.offices.no_contact') }}
        </p>
      </article>
    </div>
  </AppCard>
</template>
