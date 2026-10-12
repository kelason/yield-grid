<script setup>
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import { GUIDE_GROUPS } from '@/constants/userGuides'

defineProps({
  guides: { type: Array, required: true },
  activeTopic: { type: String, default: null },
})
const { t } = useI18n()
</script>

<template>
  <nav :aria-label="t('guideUi.topics')" class="space-y-7">
    <div
      v-for="group in GUIDE_GROUPS.filter((item) =>
        guides.some((guide) => guide.groupId === item.id),
      )"
      :key="group.id"
    >
      <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-soil-700">
        {{ t(group.titleKey) }}
      </p>
      <ul class="space-y-1">
        <li v-for="guide in guides.filter((item) => item.groupId === group.id)" :key="guide.id">
          <RouterLink
            :to="{ name: 'documentation-topic', params: { topic: guide.id } }"
            :aria-current="activeTopic === guide.id ? 'page' : undefined"
            class="flex min-h-11 items-center rounded-xl px-3 py-2 text-sm leading-relaxed transition-colors"
            :class="
              activeTopic === guide.id
                ? 'bg-moss-100 font-semibold text-moss-800'
                : 'text-stone-600 hover:bg-stone-100 hover:text-moss-800'
            "
          >
            <span class="min-w-0">{{ t(guide.titleKey) }}</span>
          </RouterLink>
        </li>
      </ul>
    </div>
  </nav>
</template>
