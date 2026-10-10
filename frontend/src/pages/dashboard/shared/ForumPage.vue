<script setup>
import { onMounted, onUnmounted, ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useForumStore } from '@/stores/forumStore'
import { useAuthStore } from '@/stores/auth'
import { useConfirmModal } from '@/composables/useConfirmModal'
import { useContentReport } from '@/composables/useContentReport'
import { FORUM_CONSTANTS, THREAD_SORT_OPTIONS } from '@/constants/forum'
import { PAGINATION_DIRECTION } from '@/constants/pagination'
import ThreadCard from '@/components/molecules/ThreadCard.vue'
import ContentReportForm from '@/components/organisms/ContentReportForm.vue'
import CategoryCard from '@/components/molecules/CategoryCard.vue'
import ThreadComposer from '@/components/molecules/ThreadComposer.vue'
import PageHeader from '@/components/molecules/PageHeader.vue'
import LoadingState from '@/components/molecules/LoadingState.vue'
import EmptyState from '@/components/molecules/EmptyState.vue'
import SearchInput from '@/components/molecules/SearchInput.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import AppCard from '@/components/atoms/AppCard.vue'
import SkeletonCard from '@/components/atoms/SkeletonCard.vue'
import AppModal from '@/components/molecules/AppModal.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
const THREAD_SKELETON_COUNT = 3
const SEARCH_DEBOUNCE_MS = 500
const FIRST_PAGE = 1
const DEFAULT_SORT = 'latest'
const forumStore = useForumStore()
const authStore = useAuthStore()
const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const showComposer = ref(false)
const composerError = ref('')
const showReportModal = ref(false)
const {
  target: reportTarget,
  reason: reportReason,
  description: reportDescription,
  busy: reportBusy,
  error: reportError,
  fieldError: reportFieldError,
  openReport,
  closeReport,
  validate: validateReport,
  submit: submitContentReport,
} = useContentReport()
const reportAccess = computed(() => {
  if (!authStore.isAuthenticated) return 'signin'
  if (!authStore.isEmailVerified) return 'verify'
  return 'ok'
})
const selectedCategory = ref(route.query.category || '')
const currentSort = ref(route.query.sort || DEFAULT_SORT)
const searchQuery = ref(
  String(route.query.search || '').slice(0, FORUM_CONSTANTS.SEARCH_MAX_LENGTH),
)
const { isOpen, isExecuting, config, confirm, execute, cancel } = useConfirmModal()
const allDiscussions = computed(() => ({
  name: t('community.forum.all_name'),
  slug: '',
  description: t('community.forum.all_desc'),
  threads_count: forumStore.categories.reduce(
    (total, category) => total + (category.threads_count || 0),
    0,
  ),
}))
const title = computed(
  () =>
    forumStore.categories.find((category) => category.slug === selectedCategory.value)?.name ||
    t('community.forum.title'),
)
const sortOptions = computed(() =>
  THREAD_SORT_OPTIONS.map((option) => ({ value: option.value, label: t(option.labelKey) })),
)
let searchTimeout
onMounted(() => Promise.all([forumStore.fetchCategories(), forumStore.fetchTags(), fetchThreads()]))
onUnmounted(() => clearTimeout(searchTimeout))
async function fetchThreads() {
  if (searchQuery.value.length > FORUM_CONSTANTS.SEARCH_MAX_LENGTH) return
  await forumStore.fetchThreads({
    category: selectedCategory.value,
    sort: currentSort.value,
    search: searchQuery.value,
    page: forumStore.pagination.currentPage,
  })
}
watch([selectedCategory, currentSort], () => {
  router.replace({
    query: {
      ...route.query,
      category: selectedCategory.value || undefined,
      sort: currentSort.value !== DEFAULT_SORT ? currentSort.value : undefined,
    },
  })
  forumStore.pagination.currentPage = FIRST_PAGE
  fetchThreads()
})
function handleSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    router.replace({ query: { ...route.query, search: searchQuery.value || undefined } })
    forumStore.pagination.currentPage = FIRST_PAGE
    fetchThreads()
  }, SEARCH_DEBOUNCE_MS)
}
function requestCreate(data) {
  const draft = { ...data, tag_ids: [...data.tag_ids] }
  confirm(
    {
      title: t('community.forum.post_title'),
      message: t('community.forum.post_msg'),
      confirmText: t('community.forum.post_ok'),
    },
    () => createThread(draft),
  )
}
async function createThread(data) {
  composerError.value = ''
  try {
    await forumStore.createThread(data)
    showComposer.value = false
    await Promise.all([forumStore.fetchCategories(), fetchThreads()])
  } catch (error) {
    composerError.value = error.response?.data?.message || t('community.forum.post_failed')
  }
}
function handleVote(id, value) {
  confirm(
    {
      title: t('community.forum.vote_title'),
      message: t('community.forum.vote_msg'),
      confirmText: t('community.forum.vote_ok'),
    },
    () => forumStore.voteThread(id, value),
  )
}
function openReportModal(payload) {
  openReport(payload)
  showReportModal.value = true
}
function closeReportModal() {
  if (reportBusy.value || isExecuting.value) return
  showReportModal.value = false
  closeReport()
}
function requestReportSubmit() {
  if (!reportTarget.value || reportBusy.value) return
  if (!validateReport()) return
  confirm(
    {
      title: t('market.browse.report_confirm_title'),
      message: t('market.browse.report_confirm_msg'),
      confirmText: t('market.browse.report_confirm_ok'),
    },
    submitReport,
  )
}
async function submitReport() {
  try {
    await submitContentReport()
    showReportModal.value = false
  } catch {
    // The inline form error and draft stay visible; the modal remains open.
  }
}
function goLogin() {
  closeReportModal()
  router.push({ name: 'login' })
}
function openComposer() {
  composerError.value = ''
  showComposer.value = true
}
function closeComposer() {
  if (!isExecuting.value) showComposer.value = false
}
function clearFilters() {
  selectedCategory.value = ''
  currentSort.value = DEFAULT_SORT
  searchQuery.value = ''
  handleSearch()
}
async function changePage(delta) {
  const nextPage = forumStore.pagination.currentPage + delta
  if (nextPage < FIRST_PAGE || nextPage > forumStore.pagination.lastPage) return
  forumStore.pagination.currentPage = nextPage
  await fetchThreads()
}
</script>
<template>
  <div class="space-y-6">
    <PageHeader :title="title" :description="t('community.forum.description')"
      ><template #actions
        ><AppButton @click="openComposer">{{
          t('community.forum.new_discussion')
        }}</AppButton></template
      ></PageHeader
    >
    <div class="grid items-end gap-4 sm:grid-cols-2">
      <SearchInput
        id="forum-search"
        :aria-label="t('community.forum.search_aria')"
        v-model="searchQuery"
        :minlength="FORUM_CONSTANTS.SEARCH_MIN_LENGTH"
        :maxlength="FORUM_CONSTANTS.SEARCH_MAX_LENGTH"
        :placeholder="t('community.forum.search_ph')"
        @update:model-value="handleSearch"
      />
      <AppSelect
        id="forum-sort"
        :label="t('community.forum.sort_label')"
        v-model="currentSort"
        :options="sortOptions"
      />
    </div>
    <div class="grid gap-6 lg:grid-cols-[16rem_minmax(0,1fr)]">
      <aside>
        <AppCard padding="p-4"
          ><h2 class="font-serif text-2xl font-bold text-stone-900 mb-4">
            {{ t('community.forum.categories') }}
          </h2>
          <CategoryCard
            :category="allDiscussions"
            :is-selected="selectedCategory === ''"
            @select="selectedCategory = ''" /><CategoryCard
            v-for="category in forumStore.categories"
            :key="category.id"
            :category="category"
            :is-selected="selectedCategory === category.slug"
            @select="selectedCategory = $event"
        /></AppCard>
      </aside>
      <section :aria-label="t('community.forum.discussions_aria')" class="min-w-0 space-y-4">
        <LoadingState v-if="forumStore.isLoading" :label="t('community.forum.loading')"
          ><div class="space-y-4">
            <SkeletonCard v-for="number in THREAD_SKELETON_COUNT" :key="number" with-avatar /></div
        ></LoadingState>
        <AppCard v-else-if="forumStore.fetchError" role="alert"
          ><p class="mb-3">{{ forumStore.fetchError }}</p>
          <AppButton variant="outline" @click="fetchThreads">{{
            t('community.forum.retry')
          }}</AppButton></AppCard
        >
        <template v-else-if="forumStore.threads.length"
          ><ThreadCard
            v-for="thread in forumStore.threads"
            :key="thread.id"
            :thread="thread"
            @vote="handleVote"
            @report="openReportModal"
          />
          <nav
            v-if="forumStore.pagination.lastPage > 1"
            :aria-label="t('community.forum.pages_aria')"
            class="flex justify-center gap-3"
          >
            <AppButton
              variant="outline"
              :disabled="forumStore.pagination.currentPage === 1"
              @click="changePage(PAGINATION_DIRECTION.PREVIOUS)"
              >{{ t('community.forum.prev') }}</AppButton
            ><AppButton
              variant="outline"
              :disabled="forumStore.pagination.currentPage === forumStore.pagination.lastPage"
              @click="changePage(PAGINATION_DIRECTION.NEXT)"
              >{{ t('community.forum.next') }}</AppButton
            >
          </nav></template
        >
        <EmptyState
          v-else
          :title="t('community.forum.empty_title')"
          :description="t('community.forum.empty_desc')"
          ><template #action
            ><AppButton variant="outline" @click="clearFilters">{{
              t('community.forum.clear')
            }}</AppButton></template
          ></EmptyState
        >
      </section>
    </div>
    <AppModal
      :title="t('community.forum.composer_title')"
      :is-open="showComposer"
      :busy="isExecuting"
      @close="closeComposer"
      ><ThreadComposer
        :categories="forumStore.categories"
        :tags="forumStore.tags"
        :is-submitting="isExecuting"
        :error="composerError"
        @submit="requestCreate"
        @cancel="closeComposer"
    /></AppModal>
    <AppModal
      :title="t('market.browse.report_title')"
      :is-open="showReportModal"
      :busy="reportBusy || isExecuting"
      @close="closeReportModal"
    >
      <ContentReportForm
        v-if="reportAccess === 'ok' && reportTarget"
        :target="reportTarget"
        :reason="reportReason"
        :description="reportDescription"
        :busy="reportBusy || isExecuting"
        :error="reportError"
        :field-error="reportFieldError"
        @update:reason="reportReason = $event"
        @update:description="reportDescription = $event"
        @submit="requestReportSubmit"
        @cancel="closeReportModal"
      />
      <div v-else-if="reportAccess === 'signin'" class="space-y-4">
        <p class="text-base leading-relaxed text-stone-600">
          {{ t('market.browse.signin_msg') }}
        </p>
        <div class="flex justify-end gap-3">
          <AppButton variant="secondary" @click="closeReportModal">{{
            t('shell.cancel')
          }}</AppButton>
          <AppButton variant="primary" @click="goLogin">{{ t('market.browse.signin') }}</AppButton>
        </div>
      </div>
      <div v-else class="space-y-4">
        <p class="text-base leading-relaxed text-stone-600">
          {{ t('market.browse.verify_msg') }}
        </p>
        <div class="flex justify-end">
          <AppButton variant="secondary" @click="closeReportModal">{{
            t('shell.close')
          }}</AppButton>
        </div>
      </div>
    </AppModal>
    <ConfirmModal
      :is-open="isOpen"
      :title="config.title"
      :message="config.message"
      :confirm-text="config.confirmText"
      :loading="isExecuting"
      @confirm="execute"
      @cancel="cancel"
    />
  </div>
</template>
