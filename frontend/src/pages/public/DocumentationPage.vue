<script setup>
import { computed } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { USER_GUIDES, getGuide, GUIDE_GROUPS } from '@/constants/userGuides'
import { usePublicPageHead } from '@/composables/usePublicPageHead'
import DocumentationNavigation from '@/components/organisms/DocumentationNavigation.vue'
import DocumentationArticle from '@/components/organisms/DocumentationArticle.vue'
import AppCard from '@/components/atoms/AppCard.vue'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const auth = useAuthStore()
const { t } = useI18n()
const guide = computed(() => getGuide(route.params.topic))
const isIndex = computed(() => !route.params.topic)
const title = computed(() =>
  guide.value ? t(guide.value.titleKey) : t(isIndex.value ? 'guideUi.title' : 'guideUi.not_found'),
)
usePublicPageHead({
  title: computed(() => `${title.value} | YieldGrid`),
  description: computed(() => (guide.value ? t(guide.value.summaryKey) : t('guideUi.intro'))),
})
</script>

<template>
  <div class="break-words bg-stone-50 px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
    <div class="mx-auto max-w-7xl">
      <RouterLink
        v-if="!isIndex"
        to="/documentation"
        class="mb-8 inline-flex min-h-11 items-center rounded-xl text-sm font-medium text-moss-700 transition-colors hover:text-moss-900"
        >{{ t('guideUi.back') }}</RouterLink
      >
      <div class="grid min-w-0 gap-10 lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-16">
        <aside class="min-w-0">
          <div class="hidden lg:sticky lg:top-28 lg:block">
            <DocumentationNavigation :guides="USER_GUIDES" :active-topic="guide?.id" />
          </div>
          <details class="rounded-2xl border border-stone-200 bg-white p-4 shadow-soft lg:hidden">
            <summary class="min-h-11 cursor-pointer py-2 font-medium text-soil-700">
              {{ t('guideUi.topics') }}
            </summary>
            <div class="mt-4">
              <DocumentationNavigation :guides="USER_GUIDES" :active-topic="guide?.id" />
            </div>
          </details>
        </aside>
        <DocumentationArticle v-if="guide" :guide="guide" />
        <div v-else-if="isIndex" class="min-w-0">
          <p class="mb-3 text-sm font-semibold uppercase tracking-widest text-moss-700">
            {{ t('guideUi.eyebrow') }}
          </p>
          <h1
            id="guide-heading"
            tabindex="-1"
            class="font-serif text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl"
          >
            {{ t('guideUi.title') }}
          </h1>
          <p class="mt-4 max-w-2xl text-base leading-relaxed text-stone-600">
            {{ t('guideUi.intro') }}
          </p>
          <section
            v-for="group in GUIDE_GROUPS.filter((item) =>
              USER_GUIDES.some((itemGuide) => itemGuide.groupId === item.id),
            )"
            :key="group.id"
            class="mt-10"
          >
            <h2 class="font-serif text-2xl font-bold text-stone-900">{{ t(group.titleKey) }}</h2>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
              <AppCard
                v-for="item in USER_GUIDES.filter((itemGuide) => itemGuide.groupId === group.id)"
                :key="item.id"
              >
                <h3 class="font-serif text-xl font-semibold text-stone-900">
                  <RouterLink
                    :to="{ name: 'documentation-topic', params: { topic: item.id } }"
                    class="inline-flex min-h-11 min-w-0 max-w-full items-center rounded-xl transition-colors hover:text-moss-700"
                    >{{ t(item.titleKey) }}</RouterLink
                  >
                </h3>
                <p class="mt-2 text-base leading-relaxed text-stone-600">
                  {{ t(item.summaryKey) }}
                </p>
              </AppCard>
            </div>
          </section>
          <RouterLink
            to="/contact"
            class="mt-8 inline-flex min-h-11 items-center rounded-xl text-moss-700 underline underline-offset-4 transition-colors hover:text-moss-900"
            >{{ t('guideUi.contact') }}</RouterLink
          >
        </div>
        <div v-else class="min-w-0">
          <h1
            id="guide-heading"
            tabindex="-1"
            class="font-serif text-3xl font-bold text-stone-900 sm:text-4xl"
          >
            {{ t('guideUi.not_found') }}
          </h1>
          <p class="mt-4 text-base leading-relaxed text-stone-600">
            {{ t('guideUi.not_found_desc') }}
          </p>
        </div>
      </div>
      <div
        v-if="!auth.isAuthenticated"
        class="mt-10 flex flex-wrap gap-5 border-t border-stone-200 pt-6"
      >
        <RouterLink
          to="/auth/register"
          class="inline-flex min-h-11 items-center rounded-xl font-medium text-moss-700 transition-colors hover:text-moss-900"
          >{{ t('common.auth.get_started') }}</RouterLink
        >
        <RouterLink
          to="/auth/login"
          class="inline-flex min-h-11 items-center rounded-xl text-stone-600 transition-colors hover:text-moss-700"
          >{{ t('common.auth.login') }}</RouterLink
        >
      </div>
      <RouterLink
        v-else-if="guide?.action && guide.action.role === auth.userRole"
        :to="guide.action.to"
        class="mt-10 inline-flex min-h-11 items-center rounded-xl font-medium text-moss-700 transition-colors hover:text-moss-900"
        >{{ t(guide.action.labelKey) }}</RouterLink
      >
    </div>
  </div>
</template>
