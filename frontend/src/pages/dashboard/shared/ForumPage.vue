<script setup>
import { onMounted, onUnmounted, ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useForumStore } from '@/stores/forumStore'
import { useConfirmModal } from '@/composables/useConfirmModal'
import { FORUM_CONSTANTS, THREAD_SORT_OPTIONS } from '@/constants/forum'
import { PAGINATION_DIRECTION } from '@/constants/pagination'
import ThreadCard from '@/components/molecules/ThreadCard.vue'
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
const route = useRoute()
const router = useRouter()
const showComposer = ref(false)
const composerError = ref('')
const selectedCategory = ref(route.query.category || '')
const currentSort = ref(route.query.sort || DEFAULT_SORT)
const searchQuery = ref(
  String(route.query.search || '').slice(0, FORUM_CONSTANTS.SEARCH_MAX_LENGTH),
)
const { isOpen, isExecuting, config, confirm, execute, cancel } = useConfirmModal()
const allDiscussions = computed(() => ({
  name: 'All Discussions',
  slug: '',
  description: 'Everything in one place',
  threads_count: forumStore.categories.reduce(
    (total, category) => total + (category.threads_count || 0),
    0,
  ),
}))
const title = computed(
  () =>
    forumStore.categories.find((category) => category.slug === selectedCategory.value)?.name ||
    'Community Forum',
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
      title: 'Post this discussion?',
      message: 'Your discussion will be shared with the community.',
      confirmText: 'Post discussion',
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
    composerError.value =
      error.response?.data?.message || 'Failed to post discussion. Please retry.'
  }
}
function handleVote(id, value) {
  confirm(
    {
      title: 'Update your vote?',
      message: 'Your vote on this discussion will be updated.',
      confirmText: 'Vote',
    },
    () => forumStore.voteThread(id, value),
  )
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
    <PageHeader :title="title" description="Share practical knowledge with your farming community."
      ><template #actions
        ><AppButton @click="openComposer">New Discussion</AppButton></template
      ></PageHeader
    >
    <div class="grid gap-4 sm:grid-cols-2">
      <SearchInput
        id="forum-search"
        aria-label="Search discussions"
        v-model="searchQuery"
        minlength="0"
        :maxlength="FORUM_CONSTANTS.SEARCH_MAX_LENGTH"
        placeholder="Search discussions..."
        @update:model-value="handleSearch"
      />
      <AppSelect
        id="forum-sort"
        label="Sort discussions"
        v-model="currentSort"
        :options="THREAD_SORT_OPTIONS"
      />
    </div>
    <div class="grid gap-6 lg:grid-cols-[16rem_minmax(0,1fr)]">
      <aside>
        <AppCard padding="p-4"
          ><h2 class="font-serif text-2xl font-bold text-stone-900 mb-4">Categories</h2>
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
      <section aria-label="Discussions" class="min-w-0 space-y-4">
        <LoadingState v-if="forumStore.isLoading" label="Loading discussions"
          ><div class="space-y-4">
            <SkeletonCard v-for="number in THREAD_SKELETON_COUNT" :key="number" with-avatar /></div
        ></LoadingState>
        <AppCard v-else-if="forumStore.fetchError" role="alert"
          ><p class="mb-3">{{ forumStore.fetchError }}</p>
          <AppButton variant="outline" @click="fetchThreads">Retry discussions</AppButton></AppCard
        >
        <template v-else-if="forumStore.threads.length"
          ><ThreadCard
            v-for="thread in forumStore.threads"
            :key="thread.id"
            :thread="thread"
            @vote="handleVote"
          />
          <nav
            v-if="forumStore.pagination.lastPage > 1"
            aria-label="Discussion pages"
            class="flex justify-center gap-3"
          >
            <AppButton
              variant="outline"
              :disabled="forumStore.pagination.currentPage === 1"
              @click="changePage(PAGINATION_DIRECTION.PREVIOUS)"
              >Previous</AppButton
            ><AppButton
              variant="outline"
              :disabled="forumStore.pagination.currentPage === forumStore.pagination.lastPage"
              @click="changePage(PAGINATION_DIRECTION.NEXT)"
              >Next</AppButton
            >
          </nav></template
        >
        <EmptyState
          v-else
          title="No discussions found"
          description="Try changing your search or category."
          ><template #action
            ><AppButton variant="outline" @click="clearFilters">Clear Filters</AppButton></template
          ></EmptyState
        >
      </section>
    </div>
    <AppModal
      title="New community post"
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
